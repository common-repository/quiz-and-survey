<?php
/**
 * View for a quiz/survey.
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

$type = get_post_meta( $id, 'type', true );
$is_quiz;
$option_data_name;
$qz_table;
if( $type == 'quiz' ) {
    $is_quiz = true;
    $option_data_name = 'isAnswer';
    $qz_table = QAS_QUIZ;
} else {
    $is_quiz = false;
    $option_data_name = 'value';
    $qz_table = QAS_SURVEY;
}

global $wpdb;

// Check whether $id and $qas_quiz_id are matched
$row = $wpdb->get_row( $wpdb->prepare(
    "
     SELECT * FROM $qz_table
     WHERE id = %d AND quiz_id = %d
    ",
    $qas_quiz_id,
    $id
));
if ( ! $row ) {
    echo '<div class="alert alert-warning">' . __( 'The quiz/survey does not exist!', 'quiz-and-survey') . '[' . $id . ']</div>';
    return;
}

$settings = get_post_meta( $id, 'settings', true );
$anyone_can_respond = isset( $settings['anyoneCanRespond'] ) ? $settings['anyoneCanRespond'] : 0;
$user = wp_get_current_user();

// Check whether the user need to login
if( ! $user->exists() ) {
    if ( ! $anyone_can_respond ) {
        $location = wp_unslash( $_SERVER['REQUEST_URI'] );
        echo '<div class="alert alert-warning">' . __( "You need to login to take this quiz/survey.", 'quiz-and-survey' ) .
        ' <a href="' . esc_url( wp_login_url( $location ) ) . '">' . __( 'Log in', 'quiz-and-survey' ) . '</a></div>';
        return;
    }

} else {
    // Check wether the logged in user has completed the quiz/survey
    $user_id = $user->ID;
    $qr_table = $is_quiz ? QAS_QUIZ_RESULT : QAS_SURVEY_RESULT;
    $result_count = $wpdb->get_var( $wpdb->prepare(
        "
         SELECT COUNT(id) FROM $qr_table
         WHERE qas_quiz_id = %d AND user_id = %d
        ",
        $qas_quiz_id,
        $user_id
    ));
    if( intval( $result_count ) > 0) {
        echo '<div class="alert alert-success">' . __( 'You have already completed the quiz/survey!', 'quiz-and-survey' ) . '</div>';
        return;
    }
}

echo $content;
return;
?>
