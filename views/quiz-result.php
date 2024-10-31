<?php
/**
 * View for quiz results
 */

if ( !defined( "ABSPATH" ) ) {
    exit;
}

if ( empty( $_GET['qas_quiz_id'] ) ) {
    wp_die( __( 'Wrong URL!', 'quiz-and-survey' ) );
}

global $wpdb;

$qas_quiz_id = intval( $_GET['qas_quiz_id'] );

$qr_table = QAS_QUIZ_RESULT;
$ur_table = $wpdb->users;
$results = $wpdb->get_results(
    "SELECT R.id as id, display_name as user_name, score, submit_date
         FROM $qr_table AS R LEFT JOIN $ur_table AS U
         ON R.user_id = U.ID
         WHERE R.qas_quiz_id = $qas_quiz_id
         ORDER BY submit_date DESC"
);

$tq_table = QAS_QUIZ;
$qas_quiz = $wpdb->get_row(
    "SELECT Q.quiz_id as quiz_id, P.post_title as pname
     FROM  $tq_table as Q, $wpdb->posts as P
     WHERE Q.id = $qas_quiz_id AND P.ID = Q.quiz_id"
);

$quiz_id = $qas_quiz->quiz_id;
$quiz_name = $qas_quiz->pname;

$settings = get_post_meta( $quiz_id, 'settings', true );
$pass_score = $settings['passScore'];

$visitor_text = __( 'Visitor', 'quiz-and-survey' );
$delete_text = __( 'Delete', 'quiz-and-survey' );

?>

<div class="container">
    <h4><?php _e( 'Quiz Result', 'quiz-and-survey'); ?></h4>
    <div id="qas-message-container">
        <div id="qas-message" class="alert alert-dismissible" style="display:none"></div>
    </div>
    <ol class="breadcrumb">
        <li><a href="<?php echo QAS_QUIZZES_PAGE_URL; ?>"><?php _e( 'Quizzes', 'quiz-and-survey'); ?></a></li>
        <li class="active"><?php echo $quiz_name; ?></li>
    </ol>
    <div class="panel panel-primary">
        <div class="panel-heading"><?php _e( 'Quiz Result', 'quiz-and-survey'); ?></div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-hover" id="qas-quiz-result-table">
                <thead>
                    <tr>
                        <th style="width: 10%;"><?php _e( 'No.', 'quiz-and-survey'); ?></th>
                        <th style="width: 30%;"><?php _ex( 'Name', 'name', 'quiz-and-survey'); ?></th>
                        <th style="width: 20%;"><?php _e( 'Score', 'quiz-and-survey'); ?></th>
                        <th style="width: 20%;"><?php _e( 'Date', 'quiz-and-survey'); ?></th>
                        <th style="width: 20%;"><?php _e( 'Action', 'quiz-and-survey'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($results as $idx => $result) {
                        $class = 'text-'. ( $result->score >= $pass_score ? 'success' : 'danger' );
                        ?>
                        <tr id="qas-quiz-result-<?php echo $result->id; ?>">
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo is_null( $result->user_name ) ? $visitor_text : $result->user_name; ?></td>
                            <td class="<?php echo $class . '">' . $result->score; ?></td>
                            <td><?php echo $result->submit_date; ?> </td>
                            <td>
                                <div>
                                    <a class="btn btn-danger" href="javascript:void(0);" data-id="<?php echo $result->id; ?>" onclick="QASAdmin.deleteQuizResult(this)"><?php echo $delete_text; ?></a>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>