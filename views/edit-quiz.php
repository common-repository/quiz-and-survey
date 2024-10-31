<?php
/**
 * View for new quiz/survey.
 */
if ( ! defined( "ABSPATH" ) ) {
    exit;
}

if ( empty( $_GET['qas_id'] ) || empty( $_GET['quiz_type'] ) ) {
    wp_die( __( 'Wrong URL!', 'quiz-and-survey' ) );
}

$id = intval( $_GET['qas_id'] );
$quiz_type = sanitize_key( $_GET['quiz_type'] );
$qas_quiz_id = QAS_Quiz_Helper::get_qas_quiz_id( $quiz_type, $id );

$title;
$import_title;
$parent_title;
$parent_url;

$post = get_post( $id );
if ( ! $post || 'qas_quiz' !== $post->post_type || ! $qas_quiz_id ) {
    echo '<div class="alert alert-warning">' . __( 'The page does not exist!', 'quiz-and-survey' ) . '</div>';
}

$settings = get_post_meta( $id, 'settings', true );
if ( $quiz_type == 'quiz' ) {
    $title = __( 'Edit Quiz', 'quiz-and-survey' );
    $import_title = __( 'New Quiz From a CSV File', 'quiz-and-survey' );
    $parent_title = __( 'All Quizzes', 'quiz-and-survey' );
    $parent_url = QAS_QUIZZES_PAGE_URL;


} else {
    $title = __( 'Edit Survey', 'quiz-and-survey' );
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
        <div class="panel-heading"><?php echo $title; ?></div>
        <div class="panel-body">
            <form action="#" method="post" id="qas-edit-quiz-form" enctype="multipart/form-data" class="form-horizontal">
                <input type="hidden" id="qas-action" value="qas_save_quiz">
                <input type="hidden" id="qas-id" value="<?php echo $id; ?>">
                <input type="hidden" id="qas-quiz-type" value="<?php echo $quiz_type; ?>">
                <?php
                if ( QAS_Quiz_Helper::is_upload_allowed( $quiz_type, $id, $qas_quiz_id ) ) {
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Quiz File', 'quiz-and-survey' ); ?> :</label>
                    <div class="col-sm-10">
                        <input type="file" id="qas-quiz-file" name="quiz-file" accept=".csv,.md">
                    </div>
                </div>
                <?php
                } else {
                ?>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <span class="text-danger"><strong><?php _e( 'Note: This survey has been taken by someone, it is not able to reimport questions from a file.', 'quiz-and-survey' ); ?></strong></span>
                    </div>
                </div>
                <?php
                }
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Quiz Title', 'quiz-and-survey' ); ?><span class="text-red">*</span> :</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="qas-quiz-title" name="quiz-title" value="<?php echo $post->post_title; ?>" maxlength="100" required>
                    </div>
                </div>

                <?php
                if ( $quiz_type == 'quiz' ) {
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Pass Score', 'quiz-and-survey' ); ?> :</label>
                    <div class="col-sm-2">
                        <input type="number" class="form-control" id="qas-quiz-pass-score" name="pass-score" value="<?php echo $settings['passScore']; ?>" placeholder="60" min="0" max="100" aria-describedby="score-help-info">
                    </div>
                    <span id="score-help-info" class="help-block"><?php _e( 'Input a number in range of 0~100', 'quiz-and-survey' ); ?></span>
                </div>
                <?php
                }

                $anyone_can_respond = isset( $settings['anyoneCanRespond'] ) ? $settings['anyoneCanRespond'] : 0;
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label"><?php _e( 'Permission', 'quiz-and-survey' ); ?> :</label>
                    <div class="col-sm-4 checkbox">
                        <input type="checkbox" id="qas-quiz-anyone-can-respond" name="quiz-anyone-can-respond" <?php if( $anyone_can_respond ) echo "checked"; ?> style="margin-left: 0;border-radius: 0;">
                        <label class="form-check-label" for="qas-quiz-anyone-can-respond"><?php _e( 'Anyone can respond', 'quiz-and-survey' ); ?></label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <input type="submit" id="qas-import-quiz" class="btn btn-primary" value="<?php esc_attr_e( 'Save', 'quiz-and-survey' ); ?>">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
