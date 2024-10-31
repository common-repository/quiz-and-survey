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
    <h4><?php _e( 'All Surveys', 'quiz-and-survey'); ?></h4>
    <div id="qas-message-container">
        <div id="qas-message" class="alert alert-dismissible" style="display:none"></div>
    </div>
    <ol class="breadcrumb">
        <li><?php _e( 'Surveys', 'quiz-and-survey'); ?></li>
    </ol>

    <!-- ----------------surveys------------------------ -->

    <div class="panel panel-primary">
        <div class="pull-right">
            <a class="btn btn-success" href="<?php echo add_query_arg( 'quiz_type', 'survey', QAS_IMPORT_QUIZ_PAGE_URL ); ?>"><?php _e( 'New Survey', 'quiz-and-survey' ); ?></a>
        </div>
        <div class="panel-heading"><?php _e( 'Surveys', 'quiz-and-survey' ); ?></div>
        <div class="panel-body">
            <table id="qas-surveys-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%;"><?php _e( 'No.', 'quiz-and-survey' ); ?></th>
                        <th style="width: 45%;"><?php _ex( 'Name', 'title', 'quiz-and-survey' ); ?></th>
                        <th style="width: 20%;"><?php _e( 'Shortcode', 'quiz-and-survey' ); ?></th>
                        <th style="width: 10%;"><?php _e( 'Taken', 'quiz-and-survey' ); ?></th>
                        <th style="width: 20%;"><?php _e( 'Action', 'quiz-and-survey' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    $sv_table = QAS_SURVEY;
                    $svr_table = QAS_SURVEY_RESULT;

                    $surveys = $wpdb->get_results(
                        "
                        SELECT Q.id as id, Q.quiz_id as quiz_id, P.post_title as quiz_name, count(R.id) as taken_count
                        FROM $sv_table AS Q LEFT JOIN  $svr_table AS R ON Q.id = R.qas_quiz_id
                        JOIN $wpdb->posts AS P ON Q.quiz_id = P.ID
                        GROUP BY(Q.id)
                        ORDER BY Q.id DESC
                        "
                    );

                    foreach ($surveys as $idx => $survey) {
                        $survey_url = add_query_arg( 'qas_quiz_id', $survey->id, get_post_permalink($survey->quiz_id) );
                        ?>
                        <tr id="qas-survey-<?php echo $survey->id; ?>">
                            <td><?php echo $idx + 1; ?></td>
                            <td><a target="_blank" href="<?php echo $survey_url; ?>"><?php echo $survey->quiz_name; ?></a></td>
                            <td><?php echo "[qas id=$survey->quiz_id quiz_id=$survey->id]"; ?></td>
                            <td><a target="_blank" href="<?php echo QAS_SURVEY_RESULT_PAGE_URL . '&qas_quiz_id=' . $survey->id;?>"><?php echo $survey->taken_count; ?></a></td>
                            <td>
                                <div>
                                    <a class="btn btn-success" target="_blank" href="<?php echo QAS_EDIT_QUIZ_PAGE_URL . '&qas_id=' . $survey->quiz_id . '&quiz_type=survey';?>"><?php echo $edit_text; ?></a>
                                    <a class="btn btn-danger" href="javascript:void(0);" data-id="<?php echo $survey->id; ?>" data-type="survey" onclick="QASAdmin.deleteQuiz(this)"><?php echo $delete_text; ?></a>
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