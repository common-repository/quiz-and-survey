<?php
/**
 * Helper functions.
 *
 * @date 2020-09-10
 */

class QAS_Quiz_Helper {

    /**
     * Check whether a survey has been taken by someone.
     *
     * @return bool Return true if it is taken, otherwise false.
     */
    public static function is_survey_taken( $qas_id = 0, $qas_quiz_id = 0 ) {
        global $wpdb;

        if ( $qas_id === 0 && $qas_quiz_id === 0 ) {
            return false;
        }

        $qr_table = QAS_SURVEY_RESULT;
        if ( $qas_quiz_id ) {
            $result = $wpdb->get_var( $wpdb->prepare(
                "
                SELECT id
                FROM  $qr_table
                WHERE qas_quiz_id = %d
                LIMIT 1
                ",
                $qas_quiz_id
            ));
        } else {
            $q_table = QAS_SURVEY;
            $result = $wpdb->get_var( $wpdb->prepare(
                "
                SELECT R.id
                FROM  $q_table AS Q, $qr_table AS R
                WHERE Q.id = R.qas_quiz_id AND Q.quiz_id = %d
                LIMIT 1
                ",
                $qas_id
            ));
        }

        return $result ? true : false;
    }

    public static function is_upload_allowed( $quiz_type, $qas_id = 0, $qas_quiz_id = 0 ) {
        return (  $quiz_type === 'quiz' ) ? true : !(self::is_survey_taken( $qas_id, $qas_quiz_id ) );
    }

    public static function get_qas_quiz_id( $quiz_type, $qas_id ) {
        global $wpdb;

        $q_table = ( $quiz_type === 'quiz' ) ? QAS_QUIZ : QAS_SURVEY;
        $result = $wpdb->get_var( $wpdb->prepare(
            "
            SELECT id
            FROM  $q_table
            WHERE quiz_id = %d
            LIMIT 1
            ",
            $qas_id
        ));

        return $result;
    }

    public static function is_quiz_exist( $quiz_type, $qas_id = 0, $qas_quiz_id = 0 ) {
        if ( $qas_id === 0 && $qas_quiz_id === 0 ) {
            return false;
        }

        $quiz_id = self::get_qas_quiz_id( $quiz_type, $qas_id );
        return $qas_quiz_id ? ( $quiz_id === $qas_quiz_id ) : isset( $quiz_id );
    }
}
