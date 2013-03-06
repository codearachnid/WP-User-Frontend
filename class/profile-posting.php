<?php
/**
 * Handles form generaton and posting for add/edit post in frontend
 *
 * @package WP User Frontend
 */
class WPUF_Profile_Posting extends WPUF_Form_Posting {

    protected $meta_key = 'wpuf_form';
    protected $separator = ', ';
    protected $config_id = '_wpuf_form_id';

    function __construct() {
        add_shortcode( 'wpuf_profile', array($this, 'shortcode_handler') );

        // ajax requests
        add_action( 'wp_ajax_nopriv_wpuf_submit_register', array($this, 'user_register') );

        add_action( 'wp_ajax_wpuf_submit_editprofile', array($this, 'update_profile') );
    }

    /**
     * Add post shortcode handler
     *
     * @param array $atts
     * @return string
     */
    function shortcode_handler( $atts ) {
        extract( shortcode_atts( array('id' => 0, 'type' => 'registration'), $atts ) );
        ob_start();

        var_dump( $id, $type );
        // $this->render_form( $id );

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
