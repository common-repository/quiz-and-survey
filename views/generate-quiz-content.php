<?php
/**
 * Generate quiz content.
 */

?>

<div id="qas-quiz">
    <div id="qas-questions">
        <form action="" method="post" role="form" id="qas-respond-quiz-form">
<?php

foreach ( $questions as $qtk => $question ) {
    // Do not use style="display: none;" here for js could not show it in sarafi browser.
?>
            <div class="form-group qas-question" id="qas-question-<?php echo $qtk; ?>" style="display: none;">
                <h4><?php echo ($qtk + 1) . '. ' . __( 'Question', 'quiz-and-survey' ); ?></h4>
                <?php echo $question['title']; ?>
                <div>
<?php
    if ( $question['type'] != 'FB' ) {
        $option_type = $question['type'] == 'MC' ? 'checkbox' : 'radio';
        foreach ( $question['options'] as $otk => $option ) {
            $ot_id = $qtk . '-' . $otk;
?>
                    <div class="<?php echo $option_type; ?>">
                        <label>
                            <input type="<?php echo $option_type;?>" id="<?php echo $ot_id; ?>" name="<?php echo $qtk; ?>" class="option"><?php echo $option['title']; ?>
                        </label>
                    </div>
<?php
        }

    } else {
?>
                    <input type="text" id="qas-answer-<?php echo $qtk; ?>" name="qas-answer" maxlength="100" data-type="text">
<?php
    }
?>
                </div>
                <input id="qas-question-type-<?php echo $qtk; ?>" value="<?php echo $question['type']; ?>" type="hidden">
            </div>
<?php
} // foreach
?>
            <p><?php echo __( 'Question', 'quiz-and-survey') . ' '; ?><span id="qas-current-question">1</span>/<span id="qas-question-count"><?php echo count($questions); ?></span></p>
            <div>
              <?php /* Put hidden button in front to avoid the first visible button being displayed bigger */ ?>
              <input id="qas-post-id" type="hidden" value="<?php echo $id; ?>">
              <input id="qas-quiz-id" type="hidden" value="<?php echo $qas_quiz_id; ?>">
              <input id="qas-quiz-type" type="hidden" value="<?php echo $type; ?>">
              <input id="qas-prev-question" value="<?php _e( 'Previous', 'quiz-and-survey' ); ?>" type="button">
              <input id="qas-next-question" value="<?php _e( 'Next', 'quiz-and-survey' ); ?>" type="button">
              <input id="qas-action-submit" name="submit" value="<?php esc_attr_e( 'Submit', 'quiz-and-survey' ); ?>" type="submit" style="display:none;">
              <input id="qas-quiz-start-time" name="start-time" type="hidden" value="<?php echo current_time('mysql'); ?>">
            </div>
        </form>
    </div>
</div>
