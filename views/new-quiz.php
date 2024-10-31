<?php
/**
 * View for new quiz/survey.
 */
if ( !defined( "ABSPATH" ) ) {
    exit;
}

if ( empty( $_GET['quiz_type'] ) ) {
    wp_die( __( 'Wrong URL!', 'quiz-and-survey' ) );
}

$quiz_type = sanitize_key( $_GET['quiz_type'] );

$title;
$import_title;
$parent_title;
$parent_url;
if ( $quiz_type == 'quiz' ) {
    $title = __( 'New Quiz', 'quiz-and-survey' );
    $import_title = __( 'New Quiz From a CSV File', 'quiz-and-survey' );
    $parent_title = __( 'All Quizzes', 'quiz-and-survey' );
    $parent_url = QAS_QUIZZES_PAGE_URL;
} else {
    $title = __( 'New Survey', 'quiz-and-survey' );
    $import_title = __( 'New Survey From a CSV File', 'quiz-and-survey' );
    $parent_title = __( 'All Surveys', 'quiz-and-survey' );
    $parent_url = QAS_SURVEYS_PAGE_URL;
}
?>
<div class="container">
    <h4><?php echo $title; ?></h4>
    <div id="qas-message-container">
        <div id="qas-message" class="alert alert-dismissible" style="display:none"></div>
    </div>
    <ol class="breadcrumb">
        <li><a href="<?php echo $parent_url; ?>"><?php echo $parent_title; ?></a></li>
        <li class=" active"><?php echo $title; ?></li>
    </ol>

    <div class="panel panel-primary">
        <div class="panel-heading"><?php echo $import_title; ?></div>
        <div class="panel-body">
            <form action="#" method="post" id="qas-import-quiz-form" enctype="multipart/form-data" class="form-horizontal">
                <input type="hidden" id="qas-action" value="qas_new_quiz">
                <input type="hidden" id="qas-quiz-type" value="<?php echo $quiz_type; ?>">
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Quiz File', 'quiz-and-survey' ); ?><span class="text-red">*</span> :</label>
                    <div class="col-sm-10">
                        <input type="file" id="qas-quiz-file" name="quiz-file" accept=".csv,.md" aria-describedby="file-help-info" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <span id="file-help-info" class="text-danger"><strong><?php _e( 'Note: Upload file name will be used as the quiz/survey name if it is not set in file.', 'quiz-and-survey' ); ?></strong></span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <?php
                            $markdown_demo_file = ( $quiz_type == 'quiz' ) ? 'demo/math-quiz.md' : 'demo/how-do-you-think-about-this-plugin.md';
                            $csv_demo_file = ( $quiz_type == 'quiz' ) ? 'demo/math-quiz.csv' : 'demo/how-do-you-think-about-this-plugin.csv';
                        ?>
                        <a href="<?php echo QAS_PLUGIN_URL . $markdown_demo_file; ?>"><?php _e( 'Download demo markdown', 'quiz-and-survey' ); ?></a>
                        <a href="<?php echo QAS_PLUGIN_URL . $csv_demo_file; ?>" style="margin-left: 1rem;padding-left: 1rem;border-left: 1px solid #333;"><?php _e( 'Download demo CSV', 'quiz-and-survey' ); ?></a>
                        <a target="_blank" href="https://www.gloomycorner.com/quizandsurvey/#how-to-use" style="margin-left: 1rem;padding-left: 1rem;border-left: 1px solid #333;"><?php _e( 'How to create a markdown/CSV file for a quiz/survey', 'quiz-and-survey' ); ?></a>
                    </div>
                </div>

                <?php
                if ( $quiz_type == 'quiz' ) {
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Pass Score', 'quiz-and-survey' ); ?> :</label>
                    <div class="col-sm-2">
                        <input type="number" class="form-control" id="qas-quiz-pass-score" name="pass-score" placeholder="60" min="0" max="100" aria-describedby="score-help-info">
                    </div>
                    <span id="score-help-info" class="help-block"><?php _e( 'Input a number in range of 0~100', 'quiz-and-survey' ); ?></span>
                </div>
                <?php
                }
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Permission', 'quiz-and-survey' ); ?> :</label>
                    <div class="col-sm-4 checkbox">
                        <input type="checkbox" id="qas-quiz-anyone-can-respond" name="quiz-anyone-can-respond" style="margin-left: 0;border-radius: 0;">
                        <label class="form-check-label" for="qas-quiz-anyone-can-respond"><?php _e( 'Anyone can respond', 'quiz-and-survey' ); ?></label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <input type="submit" id="qas-import-quiz" class="btn btn-primary" value="<?php esc_attr_e( 'Submit', 'quiz-and-survey' ); ?>">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>