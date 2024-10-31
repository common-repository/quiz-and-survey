<?php
/**
 * A CSV parser for parsing questions from a CSV file or content.
 * 
 * @date 2019-09-07
 */

require_once dirname( __FILE__ ) . '/qas-csv-util.php';

 /**
  * A CSV parser to transform a CSV file to a question array.
  */
class QAS_CSV {
    const QUESTION_MIN_COL = 3; // At least three columns: id, type and title

    public static function transform_file ( $file, $quiz_type = 'quiz' ) {
        $content = file_get_contents( $file );
        return self::transform( $content, $quiz_type );
    }

    public static function transform( $content, $quiz_type = 'quiz' ) {
        $is_quiz;
        $option_data_name;
        $option_data_spec;
        if( $quiz_type === 'quiz' ) {
            $is_quiz = true;
            $option_data_name = 'isAnswer';
            $option_data_spec =  __( 'This value must be 1 or 0', 'quiz-and-survey' );
        } else {
            $is_quiz = false;
            $option_data_name = 'value';
            $option_data_spec = __( 'This value must be a number', 'quiz-and-survey' );
        }

        $csv_array = qas_get_csv_array( $content );
        if( $csv_array['error'] !== false ) {
            return $csv_array['error'];
        }
        $data = $csv_array['data'];

        $qt_count = count( $data );
        $questions = array();
        for( $i = 1; $i < $qt_count; ++$i ) { // ignore the first title line
            $qt = $data[$i];

            $count = count($qt);
            if($count < self::QUESTION_MIN_COL) {
                return __( 'There are too few columns', 'quiz-and-survey' );
            }

            $question = array();
            $question['id'] = $i;
            $question['type'] = strtoupper( $qt[1] );
            $question['title'] = $qt[2];

            if( strpos( $question['title'], "\\" ) !== false ) {
                $question['title'] = str_replace(  "\\", "\\\\", $question['title'] );
            }

            // check question type
            if( !in_array( $question['type'], array( 'MC', 'SC', 'FB' ) ) ) {
                return sprintf( __( 'Error occured in line %d: "%s".<br>%s', 'quiz-and-survey' ),
                    ($i + 1), implode( ',', $qt ), __( 'The type must be MC, SC, or FB.', 'quiz-and-survey' ) );
            }

            // Fill in the blank question
            if( $question['type'] == 'FB' ) {
                $answer = trim( $qt[self::QUESTION_MIN_COL] );
                if( empty( $answer ) ) {
                    return sprintf( __( 'Error occured in line %d: Answer is not set after the question title', 'quiz-and-survey' ),
                        $i + 1 );
                }
                if( $quiz_type == 'survey' ) {
                    return sprintf( __( 'Error occured in line %d: Filling in the blanks is not supported for a survey', 'quiz-and-survey' ),
                        $i + 1 );
                }
                $question['answer'] = $answer;
            } else { // Single cholice or multiple choices question
                // check column count
                if( ( $count - self::QUESTION_MIN_COL ) % 2 != 0 ) {
                    return sprintf( __( 'Error occured in line %d: "%s".<br>%s', 'quiz-and-survey' ),
                        $i + 1, implode( ',', $qt ), __( 'Please check the columns count.', 'quiz-and-survey' ) );
                }

                $question['options'] = array();
                $answer = array();
                for( $j = self::QUESTION_MIN_COL; $j < $count; ++$j ) {
                    // if the option title is empty, then the question reading is done.
                    $t = trim( $qt[$j] );
                    if ( empty( $t ) ) {
                        break;
                    }
                    if( strpos( $t, "\\" ) !== false ) {
                        $t = str_replace(  "\\", "\\\\", $t );
                    }

                    // check the isAnswer/value for the option
                    $v = $qt[$j + 1];
                    if( !is_numeric( $v ) ) {
                        return sprintf( __( 'Error occured in line %d column %d: "%s".<br>%s', 'quiz-and-survey' ),
                            $i + 1, $j + 2, $v, $option_data_spec );
                    }

                    $v = intval( $v );
                    if( $is_quiz ) {
                        if( $v != 1 && $v != 0 ) {
                            return sprintf( __( 'Error occured in line %d column %d: "%s".<br>%s', 'quiz-and-survey' ),
                                $i + 1, $j + 2, $v, $option_data_spec );
                        }
                        if( $v == 1 ) {
                            $answer[] = count( $question['options'] ); // Remember to use option number.
                        }
                    }

                    $question['options'][] = array(
                        'title' =>  $t,
                        $option_data_name => $v
                    );

                    ++$j;
                }

                // Only a quiz question needs "answer" field.
                if ( $is_quiz ) {
                    if ( !count( $answer) ) {
                        return sprintf( __( 'Error occured in line %d: No option is set as an answer', 'quiz-and-survey' ), $i + 1 );
                    }
                    $question['answer'] = ( $question['type'] == 'SC' ) ? $answer[0] : $answer;
                }
            }
            $questions[] = $question;
        }

        return $questions;
    }
}
