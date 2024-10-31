<?php

require_once  __DIR__ . '/../../../lib/class-qas-markdown.php';

function parse_markdown( $file ) {
    $content = file_get_contents( $file );
    $meta = array();

    if ( strncmp( $content, '---', 3 ) === 0 ) {
        $pos = strpos( $content, '---', 3 );
        if ( $pos !== false ) {
            //$yaml = substr( $content, 3, $pos - 3 ); // You may want to parse yaml part
            $content = substr( $content, $pos + 3);
        }
    }

    $parser = new QAS_Markdown();
    $result = $parser->transform( $content );

    print_r( $result );
}

parse_markdown( __DIR__ . '/math-quiz.md' );
