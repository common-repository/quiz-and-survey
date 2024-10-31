<?php

use cebe\markdown\GithubMarkdown;

spl_autoload_register( function( $class_name ) {
    $file = $class_name;

    $prefix = "cebe\\markdown\\";
    $prefix_pos = strpos( $file, $prefix );
    if( $prefix_pos !== false ) {
        $file = substr( $file, $prefix_pos + strlen( $prefix ) );
    }

    $file = "..\\" . $file . '.php';
    require_once $file;
});

/*
 * Pare markdown to questions array
 */
function parse_markdown( $file ) {
    $content = file_get_contents( $file );
    $meta = array();

    if ( strncmp( $content, '---', 3 ) === 0  ) {
        $pos = strpos( $content, '---', 3 );
        if ( $pos !== false ) {
            //$yaml = substr( $content, 0, $pos + 3 );
            //$meta = $yaml; // Parse yaml here
            $content = substr( $content, $pos + 3);
        }
    }

    $parser = new GithubMarkdown();
    $content = $parser->parse( $content );

    return array(
       'meta' => $meta,
       'content' => $content
    );
}

$result = parse_markdown( 'math-quiz.md' );
var_dump( $result );
file_put_contents( 'math-quiz-cebe.html', $result['content'] );
