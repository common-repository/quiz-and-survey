<?php
/**
 * Markdown parser for QAS flavored markdown
 *
 * @date 2020-09-03
 */

require __DIR__  . '/../vendor/cebe-markdown/block/CodeTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/FencedCodeTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/HeadlineTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/HtmlTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/ListTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/QuoteTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/RuleTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/block/TableTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/inline/CodeTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/inline/EmphStrongTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/inline/LinkTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/inline/StrikeoutTrait.php';
require __DIR__  . '/../vendor/cebe-markdown/inline/UrlLinkTrait.php';

require __DIR__  . '/../vendor/cebe-markdown/Parser.php';
require __DIR__  . '/../vendor/cebe-markdown/Markdown.php';
require __DIR__  . '/../vendor/cebe-markdown/GithubMarkdown.php';

 /**
  * A markdown parser to transform QAS flavored markdown to a question array.
  */
class QAS_Markdown extends cebe\markdown\GithubMarkdown {

    private $quiz_type;
    private $questions;
    private $current_question;
    private $current_quesiton_state;

    protected function prepare() {
        $this->questions = [];
        $this->current_question = -1; // Make the first question's index is 0 instead of 1.
        $this->current_question_state = null;
    }

    /**
     * Transform markdown file to a question array.
     *
     * @param string $file Markdown file.
     * @param string $quiz_type Indicates the quiz type of the markdown. It can be 'quiz' or 'survey'
     * @return array|string Return a question array if the transform is successful, return an error message otherwist.
     */
    public function transform_file( $file, $quiz_type = 'quiz' ) {
        $text = file_get_contents( $file );
        return [$this->transform( $text, $quiz_type = 'quiz' ), $meta];
    }

    /**
     * Transform markdown content to a question array.
     *
     * @param string $text Markdown content.
     * @param string $quiz_type Indicates the quiz type of the markdown. It can be 'quiz' or 'survey'
     * @return array|string Return a question array if the transform is successful, return an error message otherwist.
     */
    public function transform( $text, $quiz_type = 'quiz' ) {
        $this->quiz_type = $quiz_type;
        $this->prepare();

        if ( ltrim( $text ) === '' ) {
            return __( 'There is not any question in the imported file!', 'quiz-and-survey' );
        }

        $text = str_replace( ["\r\n", "\n\r", "\r"], "\n", $text );
        $this->prepareMarkers( $text );

        $blocks = $this->parseBlocks( explode( "\n", $text ) );

        $result = false;
        // Build questions from blocks
        foreach( $blocks as $i => $block ) {
            $type = $block[0];

            array_unshift( $this->context, $type );

            if( false ) {
                echo $this->current_question_state . "\t" . $type . "\t" . $this->{'render' . $block[0]}($block) . PHP_EOL;
            }
            if( $type === 'headline' ) {
                $result = $this->handleHeadline( $block );
            } else if ( $type === 'taskList' ) {
                $result = $this->handleTaskList( $block );
            } else {
                $result = $this->handleContent( $block );
            }

            if( $result !== true ) {
                return $result;
            }

            array_shift( $this->context );
        }

        $this->cleanup();

        return $this->questions;
    }

    protected function handleHeadline( $block ) {
        $level = $block['level'];
        if( $level === 2 ) { // A new question starts.
            $this->current_question++;
            $title_blocks = $block['content']; // block['content'] is a block list //isset( $title_blocks[0] ) && isset( $title_blocks[0][1] ) && $title_blocks[0][0] === 'text' && strlen( $title_blocks[0][1] ) == 2 ) ) {
            if( isset( $title_blocks[0] ) && $title_blocks[0][0] === 'text' ) {
                $question_type = strtoupper( trim( $title_blocks[0][1] ) );
                if( $question_type === 'SC' || $question_type === 'MC' || $question_type === 'FB') {
                    // Note: check question state at here to detect errors
                    if( $this->current_question_state !== null && $this->current_question_state !== 'end' ) {
                        return sprintf( __('Question %d: It is not complete.', 'quiz-and-survey' ), $this->current_question + 1 );
                    }

                    // Note: first plus 1 then use, because the question is not complete yet.
                    //$this->current_question++;
                    $question = [
                        'id' => $this->current_question + 1,  // question index + 1
                        'type' => $question_type,
                        'title' => '',
                        'answer'=> null
                    ];
                    if( $question['type'] !== 'FB' ) {
                        $question['options'] = [];
                    }

                    $this->questions[$this->current_question] = $question;
                    $this->current_question_state = 'type';

                    return true;
                } else {
                    return sprintf( __( 'Question %d: Wrong question type "%s".', 'quiz-and-survey' ),
                        $this->current_question + 1, $question_type );
                }
            } else {
                return sprintf( __( 'Question %d: Wrong question type: "%s". ', 'quiz-and-survey' ),
                    $this->current_question + 1, $question_type );
            }
        } // level === 2

        return $this->handleContent( $block );
    }

    protected function handleTaskList( $block ) {
        if( $this->current_question_state !== 'title' ) {
            // Drop it.
            return true;
        }

        $current_option = 0;
        $question = $this->questions[$this->current_question];
        foreach( $block['items'] as $i => $item_blocks ) {
            $this->current_question_state = 'end';

            $content = null;
            $item_block = $item_blocks[0];
            if( $item_block[0] === 'text' && count( $item_blocks ) === 1 ) {
                $content = $item_block[1];
            }

            if( $question['type'] === 'FB' ) {
                if( empty( $content ) ) {
                    return sprintf( __( 'Question %d: Answer is not set or not set correctly.', 'quiz-and-survey' ), $this->current_question );
                }
                if( $this->quiz_type !== 'quiz' ) {
                    return sprintf( __( 'Quesiton %d: Type "FB" is not supported for a survey.', 'quiz-and-survey' ), $this->current_question );
                }
                $this->questions[$this->current_question]['answer'] = trim( $content );
                break;
            } else {
                $title = is_null( $content ) ? $this->renderAbsy( $item_blocks ) : $content;
                if( $this->quiz_type === 'quiz' ) {
                    $checked = $block['values'][$i] === 'x' ? 1 : 0;
                    $this->questions[$this->current_question]['options'][$current_option] = [
                        'title' => $title,
                        'isAnswer' => $checked
                    ];
                    if( $checked) {
                        if( $question['type'] === 'SC' ) {
                            $this->questions[$this->current_question]['answer'] = $current_option;
                        } else {
                            $this->questions[$this->current_question]['answer'][] = $current_option;
                        }
                    }
                } else {  // survey
                    $value =  $block['values'][$i];
                    $value = ( $value === " " || $value === "x" ) ? 1 : intval( $value );
                    $this->questions[$this->current_question]['options'][$current_option] = [
                        'title' => $title,
                        'value' => $value
                    ];
                }

                $current_option++;
            } // 'SC' or 'MC' question

        } // foreach

        return true;
    }

    protected function handleContent( $block ) {
        if( $this->current_question_state === 'type' || $this->current_question_state === 'title' ) {
            $this->questions[$this->current_question]['title'] .= $this->{'render' . $block[0]}( $block );
            $this->current_question_state = 'title';
        } // Otherwise drop this block

        return true;
    }

    /**
     * Parse a special unordered list  as a task list.
	 * Override parent's consumeUl() function to make some prior process check whether it is a task list,
     * because a task list can also be identified as a list.
	 */
    protected function consumeBUl($lines, $current) {
		if( preg_match( '/^( {0,3})[\-\+\*] \[[ x\d]\][ \t]/',  $lines[$current], $matches ) ) {
            $block = [
                'taskList',
                'items' => [],
                'values' => [], // The content warped by "[]" for each item.
            ];

            $lead_space_count = strlen( $matches[1] ) + 1;
            return $this->consumeTaskList( $lines, $current, $block, $lead_space_count );
        }

		$block = [
			'list',
			'list' => 'ul',
			'items' => [],
		];
		return $this->consumeList($lines, $current, $block, 'ul');
    }

    protected function consumeTaskList( $lines, $current, $block, $leadSpace ) {
        $item = 0;
		$indent = '';
		$len = 0;
		$lastLineEmpty = false;
		// track the indentation of list markers, if indented more than previous element
        // a list marker is considered to be long to a lower level
        $marker = ltrim( $lines[$current] )[0];
		$pattern = '/^( {0,' . $leadSpace . '})\\' . $marker . ' \[([ x\d])\][ \t]+/';
		for ($i = $current, $count = count($lines); $i < $count; $i++) {
			$line = $lines[$i];
			// match list marker on the beginning of the line
			if (preg_match($pattern, $line, $matches)) {
				if (($len = substr_count($matches[0], "\t")) > 0) {
					$indent = str_repeat("\t", $len);
					$line = substr($line, strlen($matches[0]));
				} else {
					$len = strlen($matches[0]);
					$indent = str_repeat(' ', $len);
					$line = substr($line, $len);
				}

                $block['items'][++$item][] = $line;
                $block['values'][$item] = $matches[2];
				$block['lazyItems'][$item] = $lastLineEmpty;
				$lastLineEmpty = false;
			} elseif (ltrim($line) === '') {
				// line is empty, may be a lazy list
				$lastLineEmpty = true;

				// two empty lines will end the list
				if (!isset($lines[$i + 1][0])) {
					break;

				// next item is the continuation of this list -> lazy list
				} elseif (preg_match($pattern, $lines[$i + 1])) {
					$block['items'][$item][] = $line;
					$block['lazyItems'][$item] = true;

				// next item is indented as much as this list -> lazy list if it is not a reference
				} elseif (strncmp($lines[$i + 1], $indent, $len) === 0 || !empty($lines[$i + 1]) && $lines[$i + 1][0] == "\t") {
					$block['items'][$item][] = $line;
					$nextLine = $lines[$i + 1][0] === "\t" ? substr($lines[$i + 1], 1) : substr($lines[$i + 1], $len);
					$block['lazyItems'][$item] = empty($nextLine) || !method_exists($this, 'identifyReference') || !$this->identifyReference($nextLine);

				// everything else ends the list
				} else {
					break;
				}
			} else {
				if ($line[0] === "\t") {
					$line = substr($line, 1);
				} elseif (strncmp($line, $indent, $len) === 0) {
					$line = substr($line, $len);
				}
				$block['items'][$item][] = $line;
				$lastLineEmpty = false;
			}

			// if next line is <hr>, end the list
			if (!empty($lines[$i + 1]) && method_exists($this, 'identifyHr') && $this->identifyHr($lines[$i + 1])) {
				break;
			}
		}

		foreach($block['items'] as $itemId => $itemLines) {
			$content = [];
			if (!$block['lazyItems'][$itemId]) {
				$firstPar = [];
				while (!empty($itemLines) && rtrim($itemLines[0]) !== '' && $this->detectLineType($itemLines, 0) === 'paragraph') {
					$firstPar[] = array_shift($itemLines);
				}
				$content = $this->parseInline(implode("\n", $firstPar));
			}
			if (!empty($itemLines)) {
				$content = array_merge($content, $this->parseBlocks($itemLines));
			}
			$block['items'][$itemId] = $content;
		}

		return [$block, $i];
    }

    /**
     * Define this function to avoid errors when such content occurs in non-first level blocks.
     */
    protected function renderTaskList() {
        $output = "<$type>\n";
        foreach ($block['items'] as $item => $itemLines ) {
			$output .= '<li>' . $this->renderAbsy($itemLines). "</li>\n";
		}
		return $output . "</ul>\n";
    }

    /**
     * Parses a formula indicated by `$`.
     * @marker $
     */
    protected function parseLatex( $text ) {
        if ( preg_match('/^\$(.+)\$/', $text, $matches ) ) {
            return [
                [
                    'latex',
                    str_replace( "\\", "\\\\", $matches[0] ) // !important Otherwise, when the content enters database,
                                                             // "\\\\" (double backslashes) would become "\\".
                ],
                strlen($matches[0])
            ];
        }
        return [['text', $markdown[0]], 1];
    }

    protected function renderLatex( $block ) {
        return $block[1];
    }

}
