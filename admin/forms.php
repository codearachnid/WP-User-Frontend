<?php

class WPUF_Forms {

    function __construct() {
        add_action( 'init', array($this, 'register_post_type') );
        add_action( 'add_meta_boxes_wpuf_forms', array($this, 'meta_box') );
    }

    function register_post_type() {
        register_post_type( 'wpuf_forms', array(
            'label' => __( 'Forms', 'wpuf' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'wpuf-admin-opt',
            'capability_type' => 'post',
            'hierarchical' => false,
            'query_var' => false,
            'supports' => array('title'),
            'labels' => array(
                'name' => __( 'Forms', 'wpuf' ),
                'singular_name' => __( 'Form', 'wpuf' ),
                'menu_name' => __( 'Forms', 'wpuf' ),
                'add_new' => __( 'Add Form', 'wpuf' ),
                'add_new_item' => __( 'Add New Form', 'wpuf' ),
                'edit' => __( 'Edit', 'wpuf' ),
                'edit_item' => __( 'Edit Form', 'wpuf' ),
                'new_item' => __( 'New Form', 'wpuf' ),
                'view' => __( 'View Form', 'wpuf' ),
                'view_item' => __( 'View Form', 'wpuf' ),
                'search_items' => __( 'Search Form', 'wpuf' ),
                'not_found' => __( 'No Form Found', 'wpuf' ),
                'not_found_in_trash' => __( 'No Form Found in Trash', 'wpuf' ),
                'parent' => __( 'Parent Form', 'wpuf' ),
            ),
        ) );
    }

    function meta_box() {
        add_meta_box( 'wpuf-metabox', 'Fields', array($this, 'meta_box_fields'), 'wpuf_forms', 'normal', 'high' );
    }

    function meta_box_fields() {
        include dirname(__FILE__) . '/forms-edit.php';
    }

}

$wpuf_forms = new WPUF_Forms();