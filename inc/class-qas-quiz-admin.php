<?php
/**
 * A class for quizzes administration.
 */

class QAS_Quiz_Admin {

    public function __construct() {
        // sub menu
        add_action( 'admin_menu', array( $this, 'register_pages' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // ajax handler
        add_action( 'wp_ajax_qas_delete_quiz', array( $this, 'delete_quiz' ) );
        add_action( 'wp_ajax_qas_delete_quiz_result', array( $this, 'delete_quiz_result' ) );
        add_action( 'wp_ajax_qas_submit_settings_data_delete', array( $this, 'submit_settings_data_delete' ) );
    }

    /**
     * Submenu: Quiz and Survey
     */
    public function register_pages() {
        $user = wp_get_current_user();
        $user_role = $user->roles[0];

        if( $user_role != 'administrator' ) {
            return;
        }

        add_menu_page(
            'Quiz and Survey',             // page title
            'Quiz and Survey',             // menu title
            'manage_options',              // capability
            QAS_MENU_SLUG,                 // menu slug
            '',                            // callback
            'dashicons-welcome-learn-more', // icon
            5
        );

        $parent = QAS_MENU_SLUG;

        add_submenu_page(
            $parent,
            esc_html__( 'Quizzes', 'quiz-and-survey' ),
            esc_html__( 'Quizzes', 'quiz-and-survey' ),
            'manage_options',
            QAS_MENU_SLUG,
            array( $this, 'show_quizzes_page' )
        );

        add_submenu_page(
            $parent,
            esc_html__( 'Surveys', 'quiz-and-survey' ),
            esc_html__( 'Surveys', 'quiz-and-survey' ),
            'manage_options',
            'qas_surveys',
            array( $this, 'show_surveys_page' )
        );

        add_submenu_page(
            $parent,
            esc_html__( 'Settings', 'quiz-and-survey' ),
            esc_html__( 'Settings', 'quiz-and-survey' ),
            'manage_options',
            'qas_settings',
            array( $this, 'show_settings_page' )
        );

        add_submenu_page(
            $parent,
            '',
            '',
            'manage_options',
            'qas_quiz_result',
            array( $this, 'show_quiz_result_page' )
        );

        add_submenu_page(
            $parent,
            '',
            '',
            'manage_options',
            'qas_survey_result',
            array( $this, 'show_survey_result_page' )
        );
    }

    public function show_quizzes_page() {
        include_once( QAS_PLUGIN_DIR . 'views/quizzes.php');
    }

    public function show_surveys_page() {
        include_once( QAS_PLUGIN_DIR . 'views/surveys.php');
    }

    public function show_quiz_result_page() {
        include_once( QAS_PLUGIN_DIR . 'views/quiz-result.php' );
    }

    public function show_survey_result_page() {
        include_once( QAS_PLUGIN_DIR . 'views/survey-result.php' );
    }

    public function show_settings_page() {
        include_once( QAS_PLUGIN_DIR . 'views/settings.php' );
    }

    public function enqueue_scripts( $hook_suffix ) {
        if ( strpos( $hook_suffix, 'qas_' ) === false ) {
            return;
        }

        wp_enqueue_script( 'jquery.valiate.js', QAS_PLUGIN_URL . 'assets/js/jquery.validate.min.js', array('jquery'), 'v1.17.0', true );
        wp_enqueue_script( 'jquery.dataTables.js', QAS_PLUGIN_URL . 'assets/js/jquery.dataTables.min.js', '', 'v1.10.18', true );
        wp_enqueue_script( 'bootstrap.js', QAS_PLUGIN_URL . 'assets/js/bootstrap.min.v3.js', '', 'v3.3.7', true );
        wp_enqueue_script( 'qas-admin.js', QAS_PLUGIN_URL . 'assets/js/qas-admin.js', array('jquery'), QAS_VERSION, true );

        $quiz_nonce = wp_create_nonce( 'qas-admin-nonce' );
        wp_localize_script(
            'qas-admin.js',
            'qas_admin_ajax_obj',
            array(
                'submit'          => __( 'Submit', 'quiz-and-survey' ),
                'save'            => __( 'Save', 'quiz-and-survey' ),
                'confirm_delete'  => __( 'Are you sure to delete?', 'quiz-and-survey' ),
                'confirm_delete_quiz' => esc_html__( 'This will delete all results associated with it!', 'quiz-and-survey' )
                                          . __( 'Are you sure to delete?', 'quiz-and-survey' ),
                'confirm_delete_when_uninstall' => __( 'Are you sure to delete the stored data when deleting the plugin?', 'quiz-and-survey' ),
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'nonce'           => $quiz_nonce
            )
        );

        wp_enqueue_style( 'bootstrap.css', QAS_PLUGIN_URL . 'assets/css/bootstrap.min.v3.css', '', 'v3.3.7' );
        wp_enqueue_style( 'jquery.dataTables.css', QAS_PLUGIN_URL . 'assets/css/jquery.dataTables.min.css', '', 'v1.10.18' );
        wp_enqueue_style( 'qas-admin.css', QAS_PLUGIN_URL . 'assets/css/qas-admin.css', '', QAS_VERSION );
    }

    function delete_quiz() {
        check_ajax_referer( 'qas-admin-nonce' );

        if ( empty( $_POST['qas_quiz_id'] ) || empty( $_POST['quiz_type'] ) ) {
            self::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        global $wpdb;

        $qas_quiz_id = intval( $_POST['qas_quiz_id'] );
        $quiz_type = sanitize_key ( $_POST['quiz_type'] );

        $msg;
        $tq_table;
        $qr_table;
        if( $quiz_type == 'quiz' ) {
            $tq_table = QAS_QUIZ;
            $qr_table = QAS_QUIZ_RESULT;
            $msg = __( 'Quiz deleted!', 'quiz-and-survey' );
        } else {
            $tq_table = QAS_SURVEY;
            $qr_table = QAS_SURVEY_RESULT;
            $msg = __( 'Survey deleted!', 'quiz-and-survey' );

            // delete result item
            $sri_table = QAS_SURVEY_RESULT_ITEM;
            $wpdb->query( $wpdb->prepare(
                "
                 DELETE FROM $sri_table
                 WHERE quiz_result_id IN (SELECT id FROM $qr_table WHERE qas_quiz_id = %d)
                ",
                $qas_quiz_id
            ) );
        }

        $wpdb->query( $wpdb->prepare(
            "
             DELETE FROM $qr_table
             WHERE qas_quiz_id = %d
            ",
            $qas_quiz_id
        ) );

        // important! get quiz id before delete
        $quiz_id = $wpdb->get_var( $wpdb->prepare(
            "
            SELECT quiz_id FROM $tq_table
            WHERE id = %d
            ",
            $qas_quiz_id
        ) );

        $wpdb->query( $wpdb->prepare(
            "
             DELETE FROM $tq_table
             WHERE id = %d
            ",
            $qas_quiz_id
        ) );

        $this->delete_quiz_post_single( $quiz_id, $quiz_type );

        self::set_response( 0, $msg );
    }

    /**
     * Delete all posts whose type is quiz or survey.
     */
    function delete_quiz_post_all( $quiz_type ) {
        global $wpdb;

        $tq_table = ( $quiz_type == 'quiz' ) ? QAS_QUIZ : QAS_SURVEY;
        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT quiz_id FROM $tq_table"
        ) );

        $msg = '';
        foreach( $posts as $post ) {
            $msg .= $post->quiz_id . ',';
            wp_delete_post( $post->quiz_id, true );
        }

        return $msg;
    }

    /**
     * Delete a single post if it is not referenced by other quizzes or surveys.
     */
    function delete_quiz_post_single( $quiz_id, $quiz_type ) {
        global $wpdb;

        $tq_table = ( $quiz_type == 'quiz' ) ? QAS_QUIZ : QAS_SURVEY;
        if( $quiz_id ) {
            $count = $wpdb->get_var(
                "
                SELECT count(id) FROM $tq_table
                WHERE quiz_id = $quiz_id
                "
            );

            if( intval( $count ) == 0 ) {
                wp_delete_post( $quiz_id, true );
            }
        }
    }

    function delete_quiz_result() {
        check_ajax_referer( 'qas-admin-nonce' );

        if ( empty( $_POST['quiz_result_id'] ) ) {
            self::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        global $wpdb;

        $quiz_result_id = intval( $_POST['quiz_result_id'] );
        $qz_table = QAS_QUIZ_RESULT;
        $wpdb->query( $wpdb->prepare(
            "
             DELETE FROM $qz_table
             WHERE id = %d
            ",
            $quiz_result_id
        ));

        self::set_response( 0, __( 'Record deleted!', 'quiz-and-survey') );
    }

    function submit_settings_data_delete() {
        check_ajax_referer( 'qas-admin-nonce' );

        if ( ! isset( $_POST['data_delete'] ) ) {
            self::set_response( 1, __( 'Missing or invalid parameters', 'quiz-and-survey' ) );
        }

        $data_delete = sanitize_key( $_POST['data_delete'] ) == 'yes' ? 'yes' : 'no';
        $result = update_option( 'qas_data_delete', $data_delete );
        if( $result ) {
            self::set_response( 0, __( 'Saved!', 'quiz-and-survey') );
        } else {
            self::set_response( 1, __( 'Settings is not saved!', 'quiz-and-survey') );
        }
    }

    /**
     * Response to the client with a message and exit the execution.
     */
    public static function set_response( $code, $msg, $data = array() ) {
        $response = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        );

        wp_send_json( $response );
    }
} 