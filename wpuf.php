<?php
/*
Plugin Name: WP User Frontend
Plugin URI: http://wedevs.com/wp-user-frontend-pro/
Description: Post, Edit, Delete posts and edit profile without coming to backend
Author: Tareq Hasan
Version: 2.0
Author URI: http://tareq.weDevs.com
*/

require_once dirname( __FILE__ ) . '/wpuf-functions.php';
require_once dirname( __FILE__ ) . '/admin/settings-options.php';
require_once dirname( __FILE__ ) . '/lib/gateway/paypal.php';

// add reCaptcha library if not found
if ( !function_exists( 'recaptcha_get_html' ) ) {
    require_once dirname( __FILE__ ) . '/lib/recaptchalib.php';
}

if ( !is_admin() ) {
    require_once dirname( __FILE__ ) . '/wpuf-edit-user.php';
}

/**
 * Autoload class files on demand
 *
 * `WPUF_Form_Posting` becomes => form-posting.php
 * `WPUF_Dashboard` becomes => dashboard.php
 *
 * @param string $class requested class name
 */
function wpuf_autoload( $class ) {
    $class = str_replace( 'WPUF_', '', $class );
    $class = explode( '_', $class );

    $class_name = implode( '-', $class);
    $filename = dirname( __FILE__ ) . '/class/' . strtolower( $class_name ) . '.php';

    if ( file_exists( $filename ) ) {
        require_once $filename;
    }
}

spl_autoload_register( 'wpuf_autoload' );

/**
 * Main bootstrap class for WP User Frontend
 *
 * @package WP User Frontend
 */
class WP_User_Frontend {

    function __construct() {

        $this->instantiate();

        // register_activation_hook( __FILE__, array($this, 'install') );
        register_deactivation_hook( __FILE__, array($this, 'uninstall') );

        add_action( 'admin_init', array($this, 'block_admin_access') );

        add_action( 'init', array($this, 'load_textdomain') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
    }

    /**
     * Create tables on plugin activation
     *
     * @global object $wpdb
     */
    function install() {
        global $wpdb;

        flush_rewrite_rules( false );

        $sql_subscription = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpuf_subscription (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `count` int(5) DEFAULT '0',
        `duration` int(5) NOT NULL DEFAULT '0',
        `cost` float NOT NULL DEFAULT '0',
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

        $sql_transaction = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpuf_transaction (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `user_id` bigint(20) DEFAULT NULL,
        `status` varchar(255) NOT NULL DEFAULT 'pending_payment',
        `cost` varchar(255) DEFAULT '',
        `post_id` bigint(20) DEFAULT NULL,
        `pack_id` bigint(20) DEFAULT NULL,
        `payer_first_name` longtext,
        `payer_last_name` longtext,
        `payer_email` longtext,
        `payment_type` longtext,
        `payer_address` longtext,
        `transaction_id` longtext,
        `created` datetime NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

        $wpdb->query( $sql_subscription );
        $wpdb->query( $sql_transaction );
    }

    /**
     * Manage task on plugin deactivation
     *
     * @return void
     */
    function uninstall() {

    }

    /**
     * Instantiate the classes
     *
     * @return void
     */
    function instantiate() {

        new WPUF_Upload();
        new WPUF_Frontend_Form_Post(); // requires for form preview
        new WPUF_Payment();
        new WPUF_Subscription();

        if (is_admin()) {
            new WPUF_Admin_Settings();
            new WPUF_Admin_Form();
            new WPUF_Admin_Posting();
        } else {
            new WPUF_Frontend_Form_Profile();
            new WPUF_Frontend_Dashboard();
        }
    }

    /**
     * Enqueues Styles and Scripts when the shortcodes are used only
     *
     * @uses has_shortcode()
     * @since 0.2
     */
    function enqueue_scripts() {
        $path = plugins_url( '', __FILE__ );

        // wp_enqueue_style( 'wpuf', $path . '/css/wpuf.css' );
        wp_enqueue_style( 'wpuf-css', $path . '/css/frontend-forms.css' );
        wp_enqueue_style( 'jquery-ui', $path . '/css/jquery-ui-1.9.1.custom.css' );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-slider' );
        wp_enqueue_script( 'jquery-ui-timepicker', $path . '/js/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker') );
        wp_enqueue_script( 'wpuf-form', $path . '/js/frontend-form.js', array('jquery', 'plupload-handlers') );

        wp_localize_script( 'wpuf-form', 'wpuf_frontend', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'error_message' => __( 'Please fix the errors to proceed', 'wpuf' )
        ) );
    }

    /**
     * Block user access to admin panel for specific roles
     *
     * @global string $pagenow
     */
    function block_admin_access() {
        global $pagenow;

        $access_level = wpuf_get_option( 'admin_access' );
        $valid_pages = array('admin-ajax.php', 'async-upload.php', 'media-upload.php');

        if ( !current_user_can( $access_level ) && !in_array( $pagenow, $valid_pages ) ) {
            wp_die( __( 'Access Denied. Your site administrator has blocked your access to the WordPress back-office.', 'wpuf' ) );
        }
    }

    /**
     * Load the translation file for current language.
     *
     * @since version 0.7
     * @author Tareq Hasan
     */
    function load_textdomain() {
        load_plugin_textdomain( 'wpuf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/log.txt' );
        }
    }

}

$wpuf = new WP_User_Frontend();
