<?php
/*
 * Plugin Name: Markdown Quiz and Survey
 * Plugin URI:  https://www.gloomycorner.com/quizandsurvey/
 * Description: A super easy-to-use plugin for creating quizzes/surveys quickly from markdown or csv files.
 * Version:     1.3.1
 * Author:      Gloomic
 * Author URI:  https://www.gloomycorner.com/
 * License:     GPL2
 *
 * Text Domain: quiz-and-survey
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'QuizAndSurvey' ) ) {
    exit;
}

global $wpdb;
define( 'QAS_QUIZ', $wpdb->prefix . 'qas_quiz' );
define( 'QAS_QUIZ_RESULT', $wpdb->prefix . 'qas_quiz_result' );
define( 'QAS_SURVEY', $wpdb->prefix . 'qas_survey' );
define( 'QAS_SURVEY_RESULT', $wpdb->prefix . 'qas_survey_result' );
define( 'QAS_SURVEY_RESULT_ITEM', $wpdb->prefix . 'qas_survey_result_item' );

define ( 'QAS_VERSION', '1.0' );

define ( 'QAS_PLUGIN_FILE', __FILE__);
define ( 'QAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define ( 'QAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define ( 'QAS_MENU_SLUG', 'qas_quizzes' );

define ( 'QAS_BASE_PAGE_URL', 'admin.php?' );
define ( 'QAS_QUIZZES_PAGE_URL', QAS_BASE_PAGE_URL . 'page=' . QAS_MENU_SLUG );
define ( 'QAS_SURVEYS_PAGE_URL', QAS_BASE_PAGE_URL . 'page=qas_surveys' );
define ( 'QAS_QUIZ_RESULT_PAGE_URL', QAS_BASE_PAGE_URL . 'page=qas_quiz_result' );
define ( 'QAS_SURVEY_RESULT_PAGE_URL', QAS_BASE_PAGE_URL . 'page=qas_survey_result' );
define ( 'QAS_IMPORT_QUIZ_PAGE_URL', QAS_BASE_PAGE_URL . 'page=qas_import_quiz' );
define ( 'QAS_EDIT_QUIZ_PAGE_URL', QAS_BASE_PAGE_URL . 'page=qas_edit_quiz' );
define ( 'QAS_SETTINGS_PAGE_URL', QAS_BASE_PAGE_URL . 'page=qas_settings' );

define ( 'QAS_SURVEY_MAX_OPTION_COUNT', 10 ); // For survey results display

class QuizAndSurvey {
    protected static $instance = null;
    public $quiz;
    public $quiz_admin;

    /**
     * Plugin url
     * @var string
     */
    private $plugin_url = null;

    /**
     * Plugin path
     * @var string
     */
    private $plugin_dir = null;

    private function __construct() {
        $this->plugin_url = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/';
        $this->plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/';

        $this->include();

        $this->quiz = new QAS_Quiz;
        $this->quiz_admin = new QAS_Quiz_Admin;

        // Add submenu after menu has been registered in quiz_admin instance.
        add_action( 'admin_menu', array( $this->quiz, 'register_import_quiz_page' ) );

        // Initialize translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 99 );

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        add_action( 'after_switch_theme', array( $this, 'activate' ) );
    }

    public function activate() {
        $this->quiz->register_post_type();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new QuizAndSurvey;
        }

        return self::$instance;
    }

    /**
     * You cannot clone this class.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'You cannot clone this class', QAS_VERSION );
    }

    /**
     * You cannot unserialize instances of this class.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'You cannot unserialize instances of this class', QAS_VERSION );
    }

    /**
     * Initialise translations
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'quiz-and-survey', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    private function include() {
        spl_autoload_register( array( $this, 'autoload' ) );
    }

    public function autoload( $class ) {
        $dir = $this->plugin_dir . 'inc' . DIRECTORY_SEPARATOR;
        $class_file_name = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

        if ( file_exists( $dir . $class_file_name ) ) {
            require $dir . $class_file_name;
        }
    }

    //------------------------------------- hooks and general funcitons

    public function get_plugin_dir() {
        return $this->plugin_dir;
    }

    public function get_plugin_url() {
        return $this->plugin_url;
    }

    public function get_quiz() {
        return $this->quiz;
    }

    public function get_quiz_admin() {
        return $this->quiz_admin;
    }

    public static function init_database() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $wpdb-> show_errors();
        $charset_collate = $wpdb->get_charset_collate();

        if( $wpdb->get_var( "SHOW TABLES LIKE '" . QAS_QUIZ . "'" ) != QAS_QUIZ ) {
            $sql = "CREATE TABLE `" . QAS_QUIZ . "`(
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id int(11) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta( $sql );
        }

        if( $wpdb->get_var( "SHOW TABLES LIKE '" . QAS_QUIZ_RESULT . "'" ) != QAS_QUIZ_RESULT ) {
            $sql = "CREATE TABLE `" . QAS_QUIZ_RESULT . "`(
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                qas_quiz_id int(11) NOT NULL,
                user_id int(11) NOT NULL,
                score int unsigned NOT NULL DEFAULT 0,
                submit_date date NOT NULL DEFAULT '0000-00-00',
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta( $sql );
        }

        if( $wpdb->get_var( "SHOW TABLES LIKE '" . QAS_SURVEY . "'" ) != QAS_SURVEY ) {
            $sql = "CREATE TABLE `" . QAS_SURVEY . "`(
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id int(11) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta( $sql );
        }

        if( $wpdb->get_var( "SHOW TABLES LIKE '" . QAS_SURVEY_RESULT . "'" ) != QAS_SURVEY_RESULT ) {
            $sql = "CREATE TABLE `" . QAS_SURVEY_RESULT . "`(
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                qas_quiz_id int(11) NOT NULL,
                user_id int(11) NOT NULL DEFAULT 0,
                submit_date date NOT NULL DEFAULT '0000-00-00',
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta( $sql );
        }

        if( $wpdb->get_var( "SHOW TABLES LIKE '" . QAS_SURVEY_RESULT_ITEM . "'" ) != QAS_SURVEY_RESULT_ITEM ) {
            $sql = "CREATE TABLE `" . QAS_SURVEY_RESULT_ITEM . "`(
                quiz_result_id int(11) NOT NULL,
                question_no int(11) NOT NULL,
                option_no int(11) NOT NULL
            ) $charset_collate;";
            dbDelta( $sql );
        }
    }

}

function qas_get_plugin_instance() {
    return QuizAndSurvey::get_instance();
}

add_action( 'plugins_loaded', 'qas_get_plugin_instance', 10 );
register_activation_hook( __FILE__, array( 'QuizAndSurvey', 'init_database' ) );
