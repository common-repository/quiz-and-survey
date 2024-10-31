<?php
/**
 * View for survey results.
 */

if ( ! defined( "ABSPATH" ) ){
    exit;
}

if ( empty( $_GET['qas_quiz_id'] ) ) {
    wp_die( __( 'Wrong URL!', 'quiz-and-survey' ) );
}

global $wpdb;

// retrive the quiz info

$qas_quiz_id = intval( $_GET['qas_quiz_id'] );
$tq_table = QAS_SURVEY;
$qas_quiz = $wpdb->get_row(
    "SELECT Q.quiz_id as quiz_id, P.post_title as quiz_name
     FROM  $tq_table as Q, $wpdb->posts as P
     WHERE Q.id = $qas_quiz_id AND P.ID = Q.quiz_id"
);

$quiz_id = $qas_quiz->quiz_id;
$quiz_name = $qas_quiz->quiz_name;

// retrive result

$sr_table = QAS_SURVEY_RESULT;
$sri_table = QAS_SURVEY_RESULT_ITEM;
$results = $wpdb->get_results(
    "SELECT question_no, option_no, count(quiz_result_id) as number
     FROM $sr_table AS R JOIN $sri_table AS I ON R.id = I.quiz_result_id
     WHERE R.qas_quiz_id = $qas_quiz_id
     GROUP BY question_no, option_no"
);

// retrive metadata of the quiz

$questions = get_post_meta( $quiz_id, 'questions', true );
$settings = get_post_meta( $quiz_id, 'settings', true );
$type = get_post_meta( $quiz_id, 'type', true );
if( $type != 'survey' ) {
    wp_die( __( 'Wrong URL!', 'quiz-and-survey' ) );
}

$stat = array();
$qcount = count( $questions );

if( $qcount == 0 ) {
    exit;
}

$options = $questions[0]['options'];
$fixed_option_count = QAS_SURVEY_MAX_OPTION_COUNT;

// initialize the $stat
for( $i = 0; $i < $qcount; ++$i ) {
    $stat[$i] = array();
    for( $j = 0; $j < $fixed_option_count; ++$j ) {
        $stat[$i][$j] = 0;
    }
}

foreach( $results as $result ) {
    $stat[$result->question_no - 1][$result->option_no - 1] = $result->number;
}
?>
<div class="container">
    <h4><?php _e( 'Survey Results', 'quiz-and-survey' ); ?></h4>
    <div id="qas-message-container">
        <div id="qas-message" class="alert alert-dismissible" style="display:none"></div>
    </div>
    <ol class="breadcrumb">
        <li><a href="<?php echo QAS_SURVEYS_PAGE_URL; ?>"><?php _e( 'Surveys', 'quiz-and-survey'); ?></a></li>
        <li class="active"><?php echo $quiz_name; ?></li>
    </ol>
    <div class="panel panel-primary">
        <div class="panel-heading"><?php _e( 'Survey Results Overview', 'quiz-and-survey' ); ?></div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-hover" id="qas-survey-result-table">
                <thead>
                    <tr>
                        <th style="width: 10%;"><?php _e( 'No.', 'quiz-and-survey' ); ?></th>
                        <th style="width: 30%;"><?php _e( 'Question', 'quiz-and-survey' ); ?></th>
                    <?php
                    // titles of columns: No., Question, option_no, option_no,  ..., Average
                    for( $i = 1; $i <= $fixed_option_count; ++$i ) {
                        echo '<th>' . $i . '</th>';
                    }
                    ?>
                        <th style="width: 10%;"><?php _e( 'Average', 'quiz-and-survey' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // result statistics for each question
                $total_score = 0;
                $i = 0;
                foreach( $questions as $question ) {
                    $score_sum = 0;
                    $user_count = 0;
                ?>
                    <tr>
                        <td><?php echo ( $i + 1 ); ?></td>
                        <td><?php echo $question['title'] ?></td>
                <?php
                    $options = $question['options'];
                    $j = 0;
                    foreach( $options as $option ) {
                        $score_sum += ( $stat[$i][$j] * $option['value'] );
                        $user_count += $stat[$i][$j];
                ?>
                        <td><?php echo $stat[$i][$j]; ?></td>
                <?php
                        $j++;
                        if( $j >= $fixed_option_count ) {
                            break;
                        }
                    }

                    $cell_count = $fixed_option_count - $j;
                    while( $cell_count-- > 0 ) {
                        echo '<td>/</td>';
                    }

                    $average = ($user_count == 0) ? 0 : ( $score_sum / $user_count );
                    $total_score += $average;
                    $average = number_format( $average, 2 );
                ?>
                        <td><?php echo $average ?></td>
                    </tr>
                <?php
                    $i++;
                } // end of foreach
                ?>
                </tbody>
            </table>
            <div>
                <strong><?php _e( 'Total Score', 'quiz-and-survey' ); ?>: <?php echo number_format( $total_score, 2) ; ?></strong>
            </div>
        </div>
    </div>
    <div class="panel panel-primary">
        <div class="panel-heading"><?php _e( 'Survey Results Details', 'quiz-and-survey' ); ?></div>
        <div class="panel-body">
            <div>
                <p><?php _e( 'Results details for each question:', 'quiz-and-survey' ); ?></p>
            </div>
        <?php
        $i = 0;
        foreach( $questions as $question ) {
            ?>
            <div class="title-bg-primary">
                <?php echo ( $i + 1 ) . '. ' .$question['title']; ?>
            </div>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 5em;"><?php _e( 'No.', 'quiz-and-survey' ); ?></td>
                        <th><?php _e( 'Option', 'quiz-and-survey' ); ?></td>
                        <th style="width: 5em;"><?php _e( 'Count', 'quiz-and-survey' ); ?></td>
                    </tr>
                </thead>
                <tbody>
                <?php
                $j = 0;
                $options = $question['options'];
                foreach( $options as $option ) {
                ?>
                    <tr>
                        <td><?php echo $j + 1;?></td>
                        <td><?php echo $option['title']; ?></td>
                        <td><?php echo $stat[$i][$j]; ?></td>
                    </tr>
                <?php
                    ++$j;
                    if( $j >= $fixed_option_count ) {
                        break;
                    }
                }
                ?>
                </tbody>
            </table>
        <?php
           ++$i;
        }
        ?>
        </div>
    </div>
</div>