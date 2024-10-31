<?php
/**
 * This class registers a custom post type of qas_quiz and handle its creation, display and results submission.
 *
 * @date 2018-06-15
 */

require_once __DIR__ . '/class-qas-quiz-helper.php';
require_once __DIR__ . '/../lib/class-qas-csv.php';
require_once __DIR__ . '/../lib/class-qas-markdown.php';

class QAS_Quiz {
    const DEBUG = false;
    const PASS_SCORE = 60;

    private $import_url = null;

    public function __construct() {

        // custom post type : qas_quiz
        add_action( 'init', array( $this, 'register_post_type' ) );

        // shortcode
        add_shortcode( 'qas', array( $this, 'parse_shortcode' ) );

        // backend ajax handler
        add_action( 'wp_ajax_qas_submit_quiz_result', array( $this, 'submit_quiz_result') );
        add_action( 'wp_ajax_nopriv_qas_submit_quiz_result', array( $this, 'submit_quiz_result') );
        add_action( 'wp_ajax_qas_submit_survey_result', array( $this, 'submit_survey_result') );
        add_action( 'wp_ajax_nopriv_qas_submit_survey_result', array( $this, 'submit_survey_result') );
        add_action( 'wp_ajax_qas_new_quiz', array( $this, 'new_quiz' ) );
        add_action( 'wp_ajax_qas_save_quiz', array( $this, 'save_quiz' ) );

        // use template instead
        add_filter( 'the_content', array( $this, 'show_quiz_page' ) );
    }

    public function register_post_type() {

        $labels = array(
            'name'               => 'Quiz', // general name for the post type.
            'menu_name'          => 'Quiz and Survey',
            'singular_name'      => 'Quiz',
            'all_items'          => '',
            'search_items'       => 'Search Quizzes',
            'add_new'            => '',
            'add_new_item'       => 'Add New Quiz',
            'new_item'           => 'New Quiz',
            'view_item'          => 'View Quiz',
            'edit_item'          => 'Edit Quiz',
            'not_found'          => 'No Quizzes Found.',
            'not_found_in_trash' => 'Quiz not found in Trash.',
            'parent_item_colon'  => 'Parent Quiz',
        );

        $args = array(
            'labels'             => $labels,
            'description'        => 'Quiz and Survey.',
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-welcome-learn-more', // the url to icon to be used for theis menu.
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => false, // bool, whether to generate and allow a UI for manaing this post type in the admin. Default: $public
            'show_in_menu'       => false, // bool, whether to show post type in admin menu. Default: $show_ui
            'query_var'          => true,  // (string|bool) Sets the query_var key for this post type.
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => array( 'title'),
        );

        register_post_type( 'qas_quiz', $args );
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );

        function wpse_wpautop_nobr( $content ) {
            return wpautop( $content, false );
        }

        add_filter( 'the_content', 'wpse_wpautop_nobr' );
    }

    //-------------------------------------------------- submenu: add new quiz page

    public function register_import_quiz_page() {
        add_submenu_page(
            QAS_MENU_SLUG,
            '',
            '',
            'manage_options',
            'qas_import_quiz',
            array( $this, 'show_new_quiz_page' )
        );

        add_submenu_page(
            QAS_MENU_SLUG,
            '',
            '',
            'manage_options',
            'qas_edit_quiz',
            array( $this, 'show_edit_quiz_page' )
        );
    }

    //-------------------------------------------------- view pages

    public function show_new_quiz_page() {
        include_once( QAS_PLUGIN_DIR . 'views/new-quiz.php' );
    }

    public function show_edit_quiz_page() {
        include_once( QAS_PLUGIN_DIR . 'views/edit-quiz.php' );
    }

    public function show_quiz_page( $content ) {

        global $post;

        if ( 'qas_quiz' !== $post->post_type ) {
            return $content;
        }

        if ( ! is_single() ) {
            return $content;
        }

        $this->enqueue_scripts();

        $id = $post->ID;
        $qas_quiz_id = isset( $_REQUEST['qas_quiz_id'] ) ? intval( $_REQUEST['qas_quiz_id'] ) : -1;
        if( $qas_quiz_id <= 0 ) {
           return '<div class="alert alert-warning">' . __( 'Wrong URL!', 'quiz-and-survey') . '</div>';
        }

        return $this->get_front_quiz_content( $id, $qas_quiz_id, $content );
    }

    /**
     * Get html to be displayed in front.
     */
    public function get_front_quiz_content( $id, $qas_quiz_id, $content ) {
        ob_start();
        include( QAS_PLUGIN_DIR . 'views/show-quiz.php' ); // Note: use include
        return ob_get_clean();
    }

    /**
     * Generate quiz content to be saved in wp_post table.
     */
    public function generate_quiz_content( $id, $qas_quiz_id, $type, $questions ) {
        ob_start();
        include( QAS_PLUGIN_DIR . 'views/generate-quiz-content.php' ); // Note: use include
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'jquery.valiate.js', QAS_PLUGIN_URL . 'assets/js/jquery.validate.min.js', array( 'jquery' ), 'v1.17.0', true );
        wp_enqueue_script( 'qas-main.js', QAS_PLUGIN_URL . 'assets/js/qas-main.js', array( 'jquery' ), QAS_VERSION, true );

        $quiz_nonce = wp_create_nonce( 'qas-quiz-nonce' );
        wp_localize_script( 'qas-main.js', 'qas_quiz_ajax_obj', array(
            'need_answer'     => __( 'You haven\'t answer this question!', 'quiz-and-survey' ),
            'wrong_id'        => __( 'The quiz/survey does not exist! Please check the URL.', 'quiz-and-survey'),
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => $quiz_nonce
        ) );

        wp_enqueue_style( 'qas-main.css', QAS_PLUGIN_URL . 'assets/css/qas-main.css', false, QAS_VERSION );
    }

    public function parse_shortcode( $atts ) {
        if ( ! isset( $atts['id'] ) || ! isset( $atts['quiz_id'] ) ) {
            return '';
        }

        $this->enqueue_scripts();

        $id = intval( $atts['id'] );
        $post = get_post( $id );
        if ( ! $post || 'qas_quiz' !== $post->post_type ) {
            return '<div class="alert alert-warning">' . __( 'The quiz/survey does not exist!', 'quiz-and-survey' ) . '</div>';
        }

        $qas_quiz_id = intval( $atts['quiz_id'] );
        $title = '<h2>' . $post->post_title . '</h2>';
        $content = apply_filters( 'the_content', $post->post_content ); // important! Enable content preprocess like latex, etc.
        return $title . $this->get_front_quiz_content( $id, $qas_quiz_id, $content );
    }

    //-------------------------------------------------- ajax handler for new quiz

    public function new_quiz() {
        check_ajax_referer( 'qas-admin-nonce' );

        if ( empty( $_POST['quiz_type'] ) || empty( $_FILES['quiz_file'] ) ) {
            QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        $quiz_type = sanitize_key( $_POST['quiz_type'] );
        $status = $this->import_quiz( $quiz_type );
        if ( $status === true ) {
            $location = ( $quiz_type == 'quiz' ? QAS_QUIZZES_PAGE_URL : QAS_SURVEYS_PAGE_URL );
            QAS_Quiz_Admin::set_response( 0, '', $location );
        } else {
            QAS_Quiz_Admin::set_response( 1, $status );
        }
    }

    /**
     * Get a proper title from a file name.
     * It trims spaces, replaces hyphen with whitespaces, capitalize the first character.
     */
    private function make_title( $file_name ) {
        $title = trim( $file_name );
        if( strpos( $title, ' ' ) === false ) {
            $title = str_replace( '-', ' ', $title );
        }
        return ucfirst( $title );
    }

    /**
     * Handle imported file.
     * @param upload_file Uploaded file.
     * @param quiz_type Quiz type, value can be 'quiz' or 'survey'
     * @return string|array Return an error message if some error occurs, otherwise reutrn an array
     * with the first element being a meta arary, and the second being a question array.
     */
    private function handle_upload( $upload_file, $quiz_type ) {
        if ( $upload_file['size'] > 100000 ) { // Maz size limit: 100k
            QAS_Quiz_Admin::set_response( 1, __( 'Exceeded filesize limit!', 'quiz-and-survey' ) );
        }
        $sanitized_name = sanitize_file_name( $upload_file['name'] );
        $file_type = wp_check_filetype( $sanitized_name, ['csv' => 'text/csv', 'md' => 'text/markdown'] );
        $file_url;
        if ( $file_type['ext'] === 'csv' || $file_type['ext'] === 'md' ) {
            $upload_dir = wp_upload_dir();

            $file_url = $upload_dir['basedir'] . '/' . $sanitized_name;
            if ( ! move_uploaded_file( $upload_file['tmp_name'],  $file_url ) ) {
                $response = sprintf( __( 'Failed to move the imported file: %s', 'quiz-and-survey' ),
                    esc_html( $upload_file['name'] ) );
                QAS_Quiz_Admin::set_response( 1, $response );
            }
        } else {
            $response = sprintf( __( 'Wrong file type: %s', 'quiz-and-survey'), esc_html( $upload_file['name'] ) );
            QAS_Quiz_Admin::set_response( 1, $response );
        }

        // Transform file to meta and questions

        $result;
        $meta = null;
        if( $file_type['ext'] === 'csv') {
            $result = QAS_CSV::transform_file( $file_url, $quiz_type );
        } else {
            $content = file_get_contents( $file_url );

            // Parser YAML part if it exist.
            if ( strncmp( $content, '---', 3 ) === 0 ) {
                $pos = strpos( $content, '---', 3 );
                if ( $pos !== false ) {
                    $yaml = substr( $content, 3, $pos - 3 );
                    if ( !function_exists( 'spyc_load' ) ) {
                        require_once __DIR__ . '/../vendor/mustangostang-spyc/Spyc.php';
                    }
                    $meta = spyc_load( $yaml );
                    $content = substr( $content, $pos + 3);
                }
            }

            $parser = new QAS_Markdown();
            $result = $parser->transform( $content, $quiz_type );
        }

        unlink( $file_url ); // !important Remember to remove it.
        if( !is_array( $result) ) {
            return $result;
        }
        $questions = $result;
        if ( !isset( $meta ) ) {
            $meta = [];
        }
        // Make quiz name from file name if 'post_title' cannot be got from file YAML part.
        if ( !isset( $meta['post_title'] ) ) {
            $quiz_name = pathinfo( sanitize_text_field( $upload_file['name'] ) )['filename']; // Use original name rather name in upload dir
            $meta['post_title'] = $this->make_title( $quiz_name );
        }

        // Check questions
        if( empty( $questions ) ) {
            return __( 'There is not any question in the imported file!', 'quiz-and-survey' );
        }

        if( false ) {
            return '[questions]:' . serialize( $questions );
        }

        return [$meta, $questions];
    }

    /**
     * Import a quiz/suvery from a CSV file.
     * @param quiz_type, value can be 'quiz' or 'survey'
     * @return bool|string, Return ture if it successed or an error message if an error occured.
     */
    private function import_quiz( $quiz_type = 'quiz' ) {
        global $wpdb;

        $is_quiz;
        $table;
        $settings = array(
            'type' => $quiz_type
        );

        if( $quiz_type === 'quiz' ) {
            $is_quiz = true;
            $table = QAS_QUIZ;

            $settings['passScore'] = isset( $_POST['quiz_pass_score'] ) ? intval( $_POST['quiz_pass_score'] ) : 60;
            $settings['timeLimit'] = isset( $_POST['quiz_time_limit'] ) ? intval( $_POST['quiz_time_limit'] ) : 60;
        } else {
            $is_quiz = false;
            $table = QAS_SURVEY;
        }
        $settings['anyoneCanRespond'] = isset( $_POST['quiz_anyone_can_respond'] ) ? intval( $_POST['quiz_anyone_can_respond'] ) : 0;

        // Parse meta and questions from the upload file.
        $result = $this->handle_upload( $_FILES['quiz_file'], $quiz_type );
        if ( !is_array( $result ) ) {
            return $result;
        }
        $meta = $result[0];
        $questions = $result[1];

        $quiz_name = $meta['post_title'];
        $post_id = wp_insert_post( array(
            'post_content'   => '',
            'post_name'      => $quiz_name,
            'post_title'     => $quiz_name,
            'post_status'    => 'publish',
            'post_type'      => 'qas_quiz'
        ) );

        // If the csv file is not utf-8 encoded (such as it includes Chinese characters and
        // it is saved as a csv file in Excel app), the below update_post_meta would be failed.
        // Note only delete the post on failure when creating a new quiz. Because if the record
        // has already existed, update_post_meta may fail if the new value is the same as the old one.
        $result = update_post_meta( $post_id, 'questions', $questions ) ;
        if( $result === false ) {
            wp_delete_post( $post_id );
            return esc_html__( 'The csv file is not UTF-8 encoded.', 'quiz-and-survey' );
        }

        update_post_meta( $post_id, 'settings', $settings );
        update_post_meta( $post_id, 'type', $quiz_type );

        $wpdb->insert(
            $table,
            array(
                'quiz_id' => $post_id
            ),
            array(
                '%d'
            )
        );
        $qas_quiz_id = $wpdb->insert_id;

        $content = $this->generate_quiz_content( $post_id, $qas_quiz_id, $quiz_type, $questions );
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_content'   => $content,
            ),
            true
        );

        if ( false ) {
            $content = get_post_meta(  $post_id, 'questions', true );
            return 'post-id:'. $post_id . 'result:' . $result. 'questions' . serialize( $questions );
        }

        return true;
    }

    public function save_quiz() {
        check_ajax_referer( 'qas-admin-nonce' );

        if ( empty( $_POST['qas_id'] ) || empty( $_POST['quiz_type'] ) ) {
            QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        $quiz_type = sanitize_key( $_POST['quiz_type'] );
        $id = intval( $_POST['qas_id'] );
        $qas_quiz_id = QAS_Quiz_Helper::get_qas_quiz_id( $quiz_type, $id );
        if ( !$qas_quiz_id ) {
            QAS_Quiz_Admin::set_response( 1, __( 'The quiz/survey does not exist!', 'quiz-and-survey' ) );
        }

        // Handle upload file
        $content = null;
        if ( isset( $_FILES['quiz_file'] ) ) {
            if ( !QAS_Quiz_Helper::is_upload_allowed( $quiz_type, $id, $qas_quiz_id ) ) {
                QAS_Quiz_Admin::set_response( 1, __( 'Note: This survey has been taken by someone, it is not able to reimport questions from a file.', 'quiz-and-survey' ) );
            }

            $result = $this->handle_upload( $_FILES['quiz_file'], $quiz_type );
            if ( !is_array( $result ) ) {
                QAS_Quiz_Admin::set_response( 1, $result );
            }

            $questions = $result[1];
            update_post_meta( $id, 'questions', $questions );
            $content = $this->generate_quiz_content( $id, $qas_quiz_id, $quiz_type, $questions );
        }

        $settings = get_post_meta( $id, 'settings', true );
        $back_location;
        if( $quiz_type === 'quiz' ) {
            if ( isset( $_POST['quiz_pass_score'] ) ) {
                $settings['passScore'] = intval( $_POST['quiz_pass_score'] );
            }

            $back_location = QAS_QUIZZES_PAGE_URL;
        } else {
            $back_location = QAS_SURVEYS_PAGE_URL;
        }
        if ( isset( $_POST['quiz_anyone_can_respond'] ) ) {
            $settings['anyoneCanRespond'] = intval( $_POST['quiz_anyone_can_respond'] );
        }
        update_post_meta( $id, 'settings', $settings );

        $args = [];
        if ( !empty( $_POST['quiz_title'] ) ) {
            $args['post_title'] = sanitize_text_field( $_POST['quiz_title'] );
        }
        if ( $content ) {
            $args['post_content'] = $content;
        }
        if ( !empty( $args ) ) {
            $args['ID'] = $id;
            $result = wp_update_post( $args, true );
            if ( is_wp_error( $result ) ) {
                QAS_Quiz_Admin::set_response( 1, $result );
            }
        }

        QAS_Quiz_Admin::set_response( 0, '', $back_location );
    }

    //------------------------------------------------------ ajax handlers for quiz results submission

    public function submit_quiz_result() {
        check_ajax_referer('qas-quiz-nonce');

        if ( empty( $_POST['data'] ) || ! is_array( $_POST['data'] ) ) {
            QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        $data = $_POST['data'];
        global $wpdb;

        // check whether qas_quiz_id is valid

        $qas_quiz_id = isset( $data['qas_quiz_id'] ) ? intval( $data['qas_quiz_id'] ) : 0;
        $result = NULL;
        if( $qas_quiz_id > 0 ) {
            $tq_table = QAS_QUIZ;
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $tq_table WHERE id = %d",
                $qas_quiz_id
            ));
        }
        if( $result === NULL ) {
            $msg = '<div class="alert alert-warning">'
                . __( 'The quiz/survey does not exist! Please check the URL.', 'quiz-and-survey' ) . '</div>';
            QAS_Quiz_Admin::set_response( 1, $msg );
        }

        $post_id = isset( $data['post_id'] ) ? intval( $data['post_id'] ) : 0;
        $html = '';
        $questions = get_post_meta( $post_id, 'questions', true );
        if ( empty( $questions ) || empty ( $_POST['data']['results'] ) || ! is_array( $_POST['data']['results'] ) ) {
            QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }
        $results = $_POST['data']['results'];
        $wrong_count = 0;
        foreach ( $questions as $qtk => $question ) {
            if ( ! isset( $results[$qtk] ) ) {
                QAS_Quiz_Admin::set_response( 1, sprintf(
                    __( 'Missing answer for question %d.', 'quiz-and-survey' ), $qtk + 1 ) );
            }
            if( $question['answer'] != $results[$qtk] ) {
                $wrong_count++;
            }
        }

        $html = '';
        $total_count = count( $questions );
        $points = 0;
        if($total_count > 0) {
            $points = number_format( ( $total_count - $wrong_count ) / $total_count * 100, 0 );
        }

        $wpdb->insert(
            QAS_QUIZ_RESULT,
            array(
                'qas_quiz_id'   => $qas_quiz_id,
                'user_id'       => get_current_user_id(),
                'score'         => $points,
                'submit_date'   => date( 'Y-m-d' )
            ),
            array(
                '%d',
                '%d',
                '%d',
                '%s'
            )
        );

        $settings = get_post_meta( $post_id, 'settings', true );
        $pass_score = isset( $settings['passScore'] ) ? $settings['passScore'] : self::PASS_SCORE;

        $msg_text;
        if( $points >= $pass_score ) {
            $msg_text = sprintf( __( 'Congratulations! You passed the quiz. You got %d points!', 'quiz-and-survey' ), $points );
        } else {
            $msg_text = sprintf( __( 'Sorry, you did not pass the quiz. You got %d points!', 'quiz-and-survey'), $points );
        }
        if( false ) print_r( $results );

        $msg = '<div class="alert alert-success">' . $msg_text . '</div>';
        QAS_Quiz_Admin::set_response( 0, $msg );
    }

    public function submit_survey_result() {
        check_ajax_referer('qas-quiz-nonce');

        if ( empty( $_POST['data'] ) || ! is_array( $_POST['data'] ) ) {
            QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        global $wpdb;
        $data = $_POST['data']; // validated before

        // check whether qas_quiz_id is valid

        $qas_quiz_id = isset( $data['qas_quiz_id'] ) ? intval( $data['qas_quiz_id'] ) : 0;
        $tq_table = QAS_SURVEY;
        $result = NULL;
        if ( $qas_quiz_id > 0 ) {
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $tq_table WHERE id = %d",
                $qas_quiz_id
            ) );
        }
        if( $result === NULL ) {
            $msg = '<div class="alert alert-warning">' . __( 'The quiz/survey does not exist! Please check the URL.', 'quiz-and-survey' ) . '</div>';
            QAS_Quiz_Admin::set_response( 1, $msg );
            return;
        }

        $wpdb->insert(
            QAS_SURVEY_RESULT,
            array(
                'qas_quiz_id' => $qas_quiz_id,
                'user_id'     => get_current_user_id(),
                'submit_date' => date( 'Y-m-d' )
            ),
            array(
                '%d',
                '%d',
                '%s'
            )
        );
        $result_id = $wpdb->insert_id;

        // result items

        if ( empty( $_POST['data']['results'] ) || ! is_array( $_POST['data']['results'] ) ) {
            QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }
        $results = $_POST['data']['results']; // validated before
        $values = array();
        foreach( $results as $result ) {
            if ( ! isset( $result['question_no'] ) || ! isset( $result['option_no'] ) ) {
                QAS_Quiz_Admin::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
            }
            $values[] = $wpdb->prepare( '(%d, %d, %d)', $result_id, intval( $result['question_no'] ), intval( $result['option_no'] ) );
        }

        $sri_table = QAS_SURVEY_RESULT_ITEM;
        $sql = "INSERT INTO $sri_table (quiz_result_id, question_no, option_no) VALUES ";
        $sql .= implode( ",\n", $values );
        $wpdb->query( $sql );

        QAS_Quiz_Admin::set_response( 0,
            '<div class="alert alert-success">' . __('Thank you for taking this survey!', 'quiz-and-survey') . '</div>' );
    }
}
