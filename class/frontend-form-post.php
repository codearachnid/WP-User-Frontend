<?php

class WPUF_Frontend_Form_Post extends WPUF_Render_Form {

    function __construct() {

        add_shortcode( 'wpuf_form', array($this, 'add_post_shortcode') );
        add_shortcode( 'wpuf_edit', array($this, 'edit_post_shortcode') );

        // ajax requests
        add_action( 'wp_ajax_wpuf_submit_post', array($this, 'submit_post') );
        add_action( 'wp_ajax_nopriv_wpuf_submit_post', array($this, 'submit_post') );

        // form preview
        add_action( 'wp_ajax_wpuf_form_preview', array($this, 'preview_form') );
    }

    /**
     * Add post shortcode handler
     *
     * @param array $atts
     * @return string
     */
    function add_post_shortcode( $atts ) {
        extract( shortcode_atts( array('id' => 0), $atts ) );
        ob_start();

        $form_settings = get_post_meta( $id, 'wpuf_form_settings', true );
        $info = apply_filters( 'wpuf_addpost_notice', '' );
        $user_can_post = apply_filters( 'wpuf_can_post', 'yes' );

        if ( $user_can_post == 'yes' ) {

            do_action( 'wpuf_add_post_form_top', $id, $form_settings );

            $this->render_form( $id );
        } else {
            echo '<div class="info">' . $info . '</div>';
        }


        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Edit post shortcode handler
     *
     * @param array $atts
     * @return string
     */
    function edit_post_shortcode( $atts ) {
        extract( shortcode_atts( array('post_id' => 0), $atts ) );

        ob_start();

        if ( !$post_id ) {
            $post_id = isset( $_GET['pid'] ) ? intval( $_GET['pid'] ) : 0;
        }

        $form_id = get_post_meta( $post_id, self::$config_id, true );
        $form_settings = get_post_meta( $form_id, 'wpuf_form_settings', true );

        if ( !$form_id ) {
            return __( "I don't know how to edit this post, I don't have the form ID", 'wpuf' );
        }

        if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'post_updated' ) {
            echo '<div class="wpuf-success">';
            echo $form_settings['update_message'];
            echo '</div>';
        }

        $this->render_form( $form_id, $post_id );

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * New/Edit post submit handler
     *
     * @return void
     */
    function submit_post() {
        check_ajax_referer( 'wpuf_form_add' );

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

        $form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
        $form_vars = $this->get_input_fields( $form_id );
        $form_settings = get_post_meta( $form_id, 'wpuf_form_settings', true );

        list( $post_vars, $taxonomy_vars, $meta_vars ) = $form_vars;

        // search if rs captcha is there
        if ( $this->search( $post_vars, 'input_type', 'really_simple_captcha' ) ) {
            $this->validate_rs_captcha();
        }

        // check recaptcha
        if ( $this->search( $post_vars, 'input_type', 'recaptcha' ) ) {
            $this->validate_re_captcha();
        }

        $is_update = false;
        $post_author = null;
        $default_post_author = wpuf_get_option( 'default_post_owner' );

        // Guest Stuffs: check for guest post
        if ( !is_user_logged_in() ) {
            if ( $form_settings['guest_post'] == 'true' && $form_settings['guest_details'] == 'true' ) {
                $guest_name = trim( $_POST['guest_name'] );
                $guest_email = trim( $_POST['guest_email'] );

                // is valid email?
                if ( !is_email( $guest_email ) ) {
                    $this->send_error( __( 'Invalid email address.', 'wpuf' ) );
                }

                // check if the user email already exists
                $user = get_user_by( 'email', $guest_email );
                if ( $user ) {
                    $post_author = $user->ID;
                } else {

                    // user not found, lets register him
                    // username from email address
                    $username = $this->guess_username( $guest_email );
                    $user_pass = wp_generate_password( 12, false );

                    $user_id = wp_create_user( $username, $user_pass, $guest_email );

                    // if its a success and no errors found
                    if ( $user_id && !is_wp_error( $user_id ) ) {
                        update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.
                        wp_new_user_notification( $user_id, $user_pass );

                        // update display name to full name
                        wp_update_user( array('ID' => $user_id, 'display_name' => $guest_name) );

                        $post_author = $user_id;
                    } else {
                        //something went wrong creating the user, set post author to the default author
                        $post_author = $default_post_author;
                    }
                }

                // guest post is enabled and details are off
            } elseif ( $form_settings['guest_post'] == 'true' && $form_settings['guest_details'] == 'false' ) {
                $post_author = $default_post_author;
            }

            // the user must be logged in already
        } else {
            $post_author = get_current_user_id();
        }

        $postarr = array(
            'post_type' => $form_settings['post_type'],
            'post_status' => $form_settings['post_status'],
            'post_author' => $post_author,
            'post_title' => isset( $_POST['post_title'] ) ? trim( $_POST['post_title'] ) : '',
            'post_content' => isset( $_POST['post_content'] ) ? trim( $_POST['post_content'] ) : '',
            'post_excerpt' => isset( $_POST['post_excerpt'] ) ? trim( $_POST['post_excerpt'] ) : '',
        );

        if ( isset( $_POST['category'] ) ) {
            $category = $_POST['category'];
            $postarr['post_category'] = is_array( $category ) ? $category : array($category);
        }

        if ( isset( $_POST['tags'] ) ) {
            $postarr['tags_input'] = explode( ',', $_POST['tags'] );
        }

        // if post_id is passed, we update the post
        if ( isset( $_POST['post_id'] ) ) {
            $is_update = true;
            $postarr['ID'] = $_POST['post_id'];
        }


        // ############ It's Time to Save the World ###############
        if ( $is_update ) {
            $postarr['post_status'] = $form_settings['edit_post_status'];
            $postarr = apply_filters( 'wpuf_update_post_args', $postarr, $form_id, $form_settings, $form_vars );
        } else {
            $postarr = apply_filters( 'wpuf_add_post_args', $postarr, $form_id, $form_settings, $form_vars );
        }

        $post_id = wp_insert_post( $postarr );

        if ( $post_id ) {
            self::update_post_meta($meta_vars, $post_id);

            // set the post form_id for later usage
            update_post_meta( $post_id, self::$config_id, $form_id );

            // save any custom taxonomies
            foreach ($taxonomy_vars as $taxonomy) {
                if ( isset( $_POST[$taxonomy['name']] ) ) {

                    if ( is_object_in_taxonomy( $form_settings['post_type'], $taxonomy['name'] ) ) {
                        $tax = $_POST[$taxonomy['name']];

                        // if it's not an array, make it one
                        if ( !is_array( $tax ) ) {
                            $tax = array($tax);
                        }

                        wp_set_post_terms( $post_id, $_POST[$taxonomy['name']], $taxonomy['name'] );
                    }
                }
            }

            if ( $is_update ) {

                // plugin API to extend the functionality
                do_action( 'wpuf_edit_post_after_update', $post_id, $form_id, $form_settings, $form_vars );

                //send mail notification
                if ( $form_settings['notification']['edit'] == 'on' ) {
                    $mail_body = $this->prepare_mail_body( $form_settings['notification']['edit_body'], $post_author, $post_id );
                    wp_mail( $form_settings['notification']['edit_to'], $form_settings['notification']['edit_subject'], $mail_body );
                }
            } else {

                // plugin API to extend the functionality
                do_action( 'wpuf_add_post_after_insert', $post_id, $form_id, $form_settings, $form_vars );

                // send mail notification
                if ( $form_settings['notification']['new'] == 'on' ) {
                    $mail_body = $this->prepare_mail_body( $form_settings['notification']['new_body'], $post_author, $post_id );
                    wp_mail( $form_settings['notification']['new_to'], $form_settings['notification']['new_subject'], $mail_body );
                }
            }

            //redirect URL
            $show_message = false;

            if ( $is_update ) {
                if ( $form_settings['edit_redirect_to'] == 'page' ) {
                    $redirect_to = get_permalink( $form_settings['edit_page_id'] );
                } elseif ( $form_settings['edit_redirect_to'] == 'url' ) {
                    $redirect_to = $form_settings['edit_url'];
                } elseif ( $form_settings['edit_redirect_to'] == 'same' ) {
                    $redirect_to = add_query_arg( array(
                        'pid' => $post_id,
                        '_wpnonce' => wp_create_nonce('wpuf_edit'),
                        'msg' => 'post_updated'
                         ), get_permalink( $_POST['page_id'] )
                    );
                } else {
                    $redirect_to = get_permalink( $post_id );
                }

            } else {
                if ( $form_settings['redirect_to'] == 'page' ) {
                    $redirect_to = get_permalink( $form_settings['page_id'] );
                } elseif ( $form_settings['redirect_to'] == 'url' ) {
                    $redirect_to = $form_settings['url'];
                } elseif ( $form_settings['redirect_to'] == 'same' ) {
                    $show_message = true;
                } else {
                    $redirect_to = get_permalink( $post_id );
                }
            }

            // send the response
            $response = array(
                'success' => true,
                'redirect_to' => $redirect_to,
                'show_message' => $show_message,
                'message' => $form_settings['message']
            );

            if ( $is_update ) {
                $response = apply_filters( 'wpuf_edit_post_redirect', $response, $post_id, $form_id, $form_settings );
            } else {
                $response = apply_filters( 'wpuf_add_post_redirect', $response, $post_id, $form_id, $form_settings );
            }

            echo json_encode( $response );
            exit;
        }

        $this->send_error( __( 'Something went wrong', 'wpuf' ) );
    }
    
    public static function update_post_meta( $meta_vars, $post_id ) {
        
        // prepare the meta vars
        list( $meta_key_value, $multi_repeated, $files ) = self::prepare_meta_fields( $meta_vars );
        
        // set featured image if there's any
        if ( isset( $_POST['wpuf_files']['featured_image'] ) ) {
            $attachment_id = $_POST['wpuf_files']['featured_image'][0];

            wpuf_associate_attachment( $attachment_id, $post_id );
            set_post_thumbnail( $post_id, $attachment_id );
        }

        // save all custom fields
        foreach ($meta_key_value as $meta_key => $meta_value) {
            update_post_meta( $post_id, $meta_key, $meta_value );
        }

        // save any multicolumn repeatable fields
        foreach ($multi_repeated as $repeat_key => $repeat_value) {
            // first, delete any previous repeatable fields
            delete_post_meta( $post_id, $repeat_key );

            // now add them
            foreach ($repeat_value as $repeat_field) {
                add_post_meta( $post_id, $repeat_key, $repeat_field );
            }
        }

        // save any files attached
        foreach ($files as $file_input) {
            // delete any previous value
            delete_post_meta( $post_id, $file_input['name'] );

            foreach ($file_input['value'] as $attachment_id) {
                wpuf_associate_attachment( $attachment_id, $post_id );
                add_post_meta( $post_id, $file_input['name'], $attachment_id );
            }
        }
    }

    function prepare_mail_body( $content, $user_id, $post_id ) {
        $user = get_user_by( 'id', $user_id );
        $post = get_post( $post_id );

        // var_dump($post);

        $post_field_search = array( '%post_title%', '%post_content%', '%post_excerpt%', '%tags%', '%category%',
            '%author%', '%sitename%', '%siteurl%', '%permalink%', '%editlink%' );

        $post_field_replace = array(
            $post->post_title,
            $post->post_content,
            $post->post_excerpt,
            get_the_term_list( $post_id, 'post_tag', '', ', '),
            get_the_term_list( $post_id, 'category', '', ', '),
            $user->display_name,
            get_bloginfo( 'name' ),
            home_url(),
            get_permalink( $post_id ),
            admin_url( 'post.php?action=edit&post=' . $post_id )
        );

        $content = str_replace( $post_field_search, $post_field_replace, $content );

        // custom fields
        preg_match_all( '/%custom_([\w-]*)\b%/', $content, $matches);
        list( $search, $replace ) = $matches;

        if ( $replace ) {
            foreach ($replace as $index => $meta_key ) {
                $value = get_post_meta( $post_id, $meta_key );
                $content = str_replace( $search[$index], implode( '; ', $value ), $content );
            }
        }

        return $content;
    }

}