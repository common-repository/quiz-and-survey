<?php
/**
 * View for quizzes.
 */

if ( !defined( "ABSPATH" ) ) {
    exit;
}

global $wpdb;

$delete_text =  __( 'Delete', 'quiz-and-survey' );
$edit_text = __( 'Edit', 'quiz-and-survey' );
?>
<div class="container">
    <h4><?php _e( 'All Quizzes', 'quiz-and-survey'); ?></h4>
    <div id="qas-message-container">
        <div id="qas-message" class="alert alert-dismissible" style="display:none"></div>
    </div>
    <ol class="breadcrumb">
        <li><?php _e( 'Quizzes', 'quiz-and-survey'); ?></li>
    </ol>

    <!-- -------------------quizzes-------------------- -->
    <div class="panel panel-primary">
        <div class="pull-right">
            <a class="btn btn-success" href="<?php echo add_query_arg( 'quiz_type', 'quiz', QAS_IMPORT_QUIZ_PAGE_URL ); ?>"><?php _e( 'New Quiz', 'quiz-and-survey'); ?></a>
        </div>

        <div class="panel-heading"><?php _e( 'Quizzes', 'quiz-and-survey'); ?></div>
        <div class="panel-body">
            <table id="qas-quizzes-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%;"><?php _e( 'No.', 'quiz-and-survey'); ?></th>
                        <th style="width: 45%;"><?php _ex( 'Name', 'title', 'quiz-and-survey'); ?></th>
                        <th style="width: 20%;"><?php _e( 'Shortcode', 'quiz-and-survey'); ?></th>
                        <th style="width: 10%;"><?php _e( 'Taken', 'quiz-and-survey'); ?></th>
                        <th style="width: 20%;"><?php _e( 'Action', 'quiz-and-survey'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    $qz_table = QAS_QUIZ;
                    $qzr_table = QAS_QUIZ_RESULT;

                    $quizzes = $wpdb->get_results(
                        "
                        SELECT Q.id as id, Q.quiz_id as quiz_id, P.post_title as quiz_name, COUNT(R.id) as taken_count
                        FROM $qz_table AS Q LEFT JOIN  $qzr_table AS R ON Q.id = R.qas_quiz_id
                        LEFT JOIN $wpdb->posts AS P ON Q.quiz_id = P.ID
                        GROUP BY(Q.id)
                        ORDER BY Q.id DESC
                        "
                    );

                    foreach ($quizzes as $idx => $quiz) {
                        $quiz_url = add_query_arg( 'qas_quiz_id', $quiz->id , get_post_permalink($quiz->quiz_id) );
                        ?>
                        <tr id="qas-quiz-<?php echo $quiz->id; ?>">
                            <td><?php echo $idx + 1; ?></td>
                            <td><a target="_blank" href="<?php echo $quiz_url; ?>"><?php echo $quiz->quiz_name; ?></a></td>
                            <td><?php echo "[qas id=$quiz->quiz_id quiz_id=$quiz->id]"; ?></td>
                            <td><a target="_blank" href="<?php echo QAS_QUIZ_RESULT_PAGE_URL . "&qas_quiz_id=" . $quiz->id; ?>"><?php echo $quiz->taken_count; ?></a></td>
                            <td>
                                <div>
                                    <a target="_blank" href="<?php echo QAS_EDIT_QUIZ_PAGE_URL . "&qas_id=" . $quiz->quiz_id . '&quiz_type=quiz'; ?>" class="btn btn-success"><?php echo $edit_text; ?></a>
                                    <a href="javascript:void(0);" data-id="<?php echo $quiz->id; ?>" data-type="quiz" onclick="QASAdmin.deleteQuiz(this)" class="btn btn-danger"><?php echo $delete_text; ?></a>
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