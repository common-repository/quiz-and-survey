<?php
/**
 * Functions for parsing a CSV file.
 * 
 * @date 2019-03-15
 */

/**
 * Remove the bom mark from a string if it has.
 */
function qas_remove_bom( $str ) {
    $has_bom = strncmp( $str, pack( 'CCC', 0XEF, 0XBB, 0XBF ), 3 ) === 0;
    if ( $has_bom ) {
           return substr( $str, 3 );
    }
    return $str;
}

function qas_get_csv_array_from_file ( $file ) {
    $content = file_get_contents( $file );
    return qas_get_csv_array( $content );
}

/**
 * Parse a csv file into a an array.
 */
function qas_get_csv_array( $content ) {
    $content = qas_remove_bom( $content );

    // important! use "\n" not '\n'
    $lines = str_getcsv( $content, "\n" );
    $data = array();
    $i = 0;
    foreach( $lines as $line ) {
        $csv_array = qas_str_getcsv_cust( $line );
        if( $csv_array['error'] != false ) {
            return array( 'error' => $csv_array['error'] );
        }
        $data[$i++] = $csv_array['data'];
    }

    return array( 'error' => false, 'data' => $data );
}

/**
 * Parse a string into an array. It is a cucstomized str_getcsv function.
 * It works fine for unicode string while str_getcsv does not.
 */
function qas_str_getcsv_cust( $str, $delimiter = ',', $enclosure = '"' ) {
    $str = trim( $str );

    $quoted_begin = false;
    $escaped = false; // indicates wether an item contains "" between " and "
    $begin = 0;
    $n = strlen( $str );
    $data = array();
    for( $i = 0; $i < $n; ++$i ) {
        $c = $str[$i];
        switch( $c ) {
            case '"':
                {
                    if( $quoted_begin ) {
                        if( $i + 1 >= $n ) {
                            break;
                        }

                        $next = $str[ $i + 1];
                        if( $next == '"' ) {
                            $escaped = true;
                            $i++;
                        } else if( $next == ',' ) {
                            $quoted_begin = false;
                            $s = substr( $str, $begin, $i - $begin );
                            if( $escaped ) {
                                $s = str_replace( '""', '"', $s );
                            }
                            $data[] = $s;

                            $i++;
                            $begin = $i + 1;
                        } else {
                            $message = sprintf( __( 'Wrong format: %d.', 'quiz-and-survey'), $i );
                            return array( 'error' => $message );
                        }
                    } else {
                        if( $begin == $i ) {
                            $quoted_begin = true;
                            $escaped = false;
                            $begin = $i + 1;
                        } else {
                            $message = sprintf( __( 'Wrong format: %d.', 'quiz-and-survey'), $i );
                            return array( 'error' => $message );
                        }
                    }
                    break;
                }
            case ',':
                {
                    if( !$quoted_begin ) {
                        $data[] = substr( $str, $begin, $i - $begin );
                        $begin = $i + 1;
                    }

                    break;

                }
        } // switch
    } // for

    if( $quoted_begin ) {
        $i--;
        $s = substr( $str, $begin, $i - $begin );
        if( $escaped ) {
            $s = str_replace( '""', '"', $s );
        }

        $data[] = $s;
    }
    else {
        $data[] = substr( $str, $begin, $i - $begin );
    }

    return array( 'error' => false, 'data' => $data );
}
