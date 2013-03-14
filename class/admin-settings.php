<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
class WPUF_Admin_Settings {

    private $settings_api;

    function __construct() {

        if ( !class_exists( 'WeDevs_Settings_API' ) ) {
            require_once dirname( dirname( __FILE__ ) ) . '/lib/class.settings-api.php';
        }
        
        require_once dirname( dirname( __FILE__ ) ) . '/admin/subscription.php';
        require_once dirname( dirname( __FILE__ ) ) . '/admin/transaction.php';

        $this->settings_api = new WeDevs_Settings_API();

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    /**
     * Register the admin menu
     *
     * @since 1.0
     */
    function admin_menu() {
        add_menu_page( __( 'WP User Frontend', 'wpuf' ), __( 'User Frontend', 'wpuf' ), 'delete_others_pages', 'wpuf-admin-opt', array($this, 'plugin_page'), null, 55 );
        add_submenu_page( 'wpuf-admin-opt', __( 'Subscription', 'wpuf' ), __( 'Subscription', 'wpuf' ), 'activate_plugins', 'wpuf_subscription', 'wpuf_subscription_admin' );
        add_submenu_page( 'wpuf-admin-opt', __( 'Transaction', 'wpuf' ), __( 'Transaction', 'wpuf' ), 'activate_plugins', 'wpuf_transaction', 'wpuf_transaction' );
        add_submenu_page( 'wpuf-admin-opt', __( 'Settings', 'wpuf' ), __( 'Settings', 'wpuf' ), 'delete_others_pages', 'wpuf-settings', array($this, 'plugin_page') );
    }

    /**
     * WPUF Settings sections
     *
     * @since 1.0
     * @return array
     */
    function get_settings_sections() {
        return wpuf_settings_sections();
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        return wpuf_settings_fields();
    }

    function plugin_page() {
        ?>
        <div class="wrap">

            <?php screen_icon( 'options-general' ); ?>

            <?php
            settings_errors();

            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
            ?>

        </div>
        <?php
    }

}