<?php
if( !defined( 'WP_UNINSTALL_PLUGIN' ) || !WP_UNINSTALL_PLUGIN ) {
    exit;
}

global $wpdb;

// delete options

$data_delete = get_option( 'qas_data_delete' );
delete_option( 'qas_data_delete' );

if( $data_delete == 1 ) {
    // delete tables

    $wpdb->query ("DROP TABLE IF EXISTS {$wpdb->prefix}qas_quiz" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qas_quiz_result" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qas_survey" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qas_survey_result" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qas_survey_result_item" );

    // delete posts

    $args = array(
        'post_type' => 'qas_quiz'
    );

    $query = new WP_Query( $args );
    while ($query->have_posts()) {
        $query->the_post();
        if ( get_post_type() == 'qas_quiz') {
            $post_id = get_the_ID();
            wp_delete_post( $post_id, true );
        }
    }
    wp_reset_postdata();
}