<?php
/*
Plugin Name: WP User Frontend Pro
Plugin URI: http://wedevs.com/wp-user-frontend-pro/
Description: Create, edit, delete, manages your post, pages or custom post types from frontend. Create registration forms, frontend profile and more...
Author: Tareq Hasan
Version: 2.1
Author URI: http://tareq.weDevs.com
*/

require_once dirname( __FILE__ ) . '/wpuf-functions.php';
require_once dirname( __FILE__ ) . '/admin/settings-options.php';

// add reCaptcha library if not found
if ( !function_exists( 'recaptcha_get_html' ) ) {
    require_once dirname( __FILE__ ) . '/lib/recaptchalib.php';
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

    private $plugin_slug = 'wp-user-frontend-pro';
    
    function __construct() {

        $this->instantiate();

        add_action( 'admin_init', array($this, 'block_admin_access') );
        
        add_action( 'admin_notices', array($this, 'update_notification') );

        add_action( 'init', array($this, 'load_textdomain') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );

        add_filter( 'register', array( $this, 'override_registration') );
        add_filter( 'tml_action_url', array( $this, 'override_registration_tml'), 10, 2 );
    }


    /**
     * Instantiate the classes
     *
     * @return void
     */
    function instantiate() {

        new WPUF_Upload();
        new WPUF_Frontend_Form_Post(); // requires for form preview
        new WPUF_Frontend_Form_Profile();
        
        if (is_admin()) {
            new WPUF_Settings();
            new WPUF_Admin_Form();
            new WPUF_Admin_Posting();
            new WPUF_Admin_Posting_Profile();
            new WPUF_Updates();
        } else {

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
            'error_message' => __( 'Please fix the errors to proceed', 'wpuf' ),
            'nonce' => wp_create_nonce( 'wpuf_nonce' )
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

    function override_registration( $link ) {
        if ( wpuf_get_option( 'register_link_override' ) != 'on' ) {
            return $link;
        }

        return sprintf('<li><a href="%s">%s</a></li>', get_permalink( wpuf_get_option( 'reg_override_page' ) ), __('Register') );
    }

    function override_registration_tml( $url, $action ) {
        if ( wpuf_get_option( 'register_link_override' ) != 'on' ) {
            return $url;
        }

        if ($action == 'register') {
            return get_permalink( wpuf_get_option( 'reg_override_page' ) );
        }
    }
    
    
    /**
     * Check if any updates found of this plugin
     *
     * @global string $wp_version
     * @return bool
     */
    function update_check() {
        global $wp_version, $wpdb;

        require_once ABSPATH . '/wp-admin/includes/plugin.php';

        $plugin_data = get_plugin_data( __FILE__ );

        $plugin_name = $plugin_data['Name'];
        $plugin_version = $plugin_data['Version'];

        $version = get_transient( $this->plugin_slug . '_update_plugin' );
        $duration = 60 * 60 * 12; //every 12 hours

        if ( $version === false ) {

            if ( is_multisite() ) {
                $user_count = get_user_count();
                $num_blogs = get_blog_count();
                $wp_install = network_site_url();
                $multisite_enabled = 1;
            } else {
                $user_count = count_users();
                $multisite_enabled = 0;
                $num_blogs = 1;
                $wp_install = home_url( '/' );
            }

            $locale = apply_filters( 'core_version_check_locale', get_locale() );

            if ( method_exists( $wpdb, 'db_version' ) )
                $mysql_version = preg_replace( '/[^0-9.].*/', '', $wpdb->db_version() );
            else
                $mysql_version = 'N/A';

            $params = array(
                'timeout' => ( ( defined( 'DOING_CRON' ) && DOING_CRON ) ? 30 : 3 ),
                'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
                'body' => array(
                    'name' => $plugin_name,
                    'slug' => $this->plugin_slug,
                    'type' => 'plugin',
                    'version' => $plugin_version,
                    'wp_version' => $wp_version,
                    'php_version' => phpversion(),
                    'action' => 'theme_check',
                    'locale' => $locale,
                    'mysql' => $mysql_version,
                    'blogs' => $num_blogs,
                    'users' => $user_count['total_users'],
                    'multisite_enabled' => $multisite_enabled,
                    'site_url' => $wp_install
                )
            );

            $url = 'http://wedevs.com/?action=wedevs_update_check';
            $response = wp_remote_post( $url, $params );
            $update = wp_remote_retrieve_body( $response );
            
            if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
                return false;
            }

            $json = json_decode( trim( $update ) );
            $version = array(
                'name' => $json->name,
                'latest' => $json->latest,
                'msg' => $json->msg
            );

            set_transient( $this->plugin_slug . '_update_plugin', $version, $duration );
        }

        if ( version_compare( $plugin_version, $version['latest'], '<' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Shows the update notification if any update founds
     */
    function update_notification() {

        $version = get_transient( $this->plugin_slug . '_update_plugin' );

        if ( $this->update_check() ) {
            $version = get_transient( $this->plugin_slug . '_update_plugin' );

            if ( current_user_can( 'update_core' ) ) {
                $msg = sprintf( __( '<strong>%s</strong> version %s is now available! %s.', 'wedevs' ), $version['name'], $version['latest'], $version['msg'] );
            } else {
                $msg = sprintf( __( '%s version %s is now available! Please notify the site administrator.', 'wedevs' ), $version['name'], $version['latest'], $version['msg'] );
            }

            echo "<div class='update-nag'>$msg</div>";
        }
    }

}

$wpuf = new WP_User_Frontend();
