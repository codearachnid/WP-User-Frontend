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

        $form_vars = get_post_meta( $id, $this->meta_key, true );
        $form_settings = get_post_meta( $id, 'wpuf_form_settings', true );

        if ( $type == 'profile' && is_user_logged_in() ) {
            $this->profile_edit( $id, $form_vars, $form_settings );
        } elseif ( $type == 'registration' && !is_user_logged_in() ) {
            $this->profile_edit( $id, $form_vars, $form_settings );
        }
        // var_dump( $id, $type, $form_vars, $form_settings );


        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    function profile_edit( $form_id, $form_vars, $form_settings ) {
        echo '<form id="wpuf-form-add" action="" method="post">';
        echo '<ul class="wpuf-form">';
        $this->render_items( $form_vars, get_current_user_id(), 'user' );
        $this->submit_button( $form_id, $form_settings );
        echo '</ul>';
        echo '</form>';
    }

    function submit_button( $form_id, $form_settings ) {
        ?>
        <li class="wpuf-submit">
            <div class="wpuf-label">
                &nbsp;
            </div>

            <?php wp_nonce_field( 'wpuf_form_add' ); ?>
            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">

            <?php if ( is_user_logged_in() ) { ?>
                <input type="hidden" name="action" value="wpuf_update_profile">
                <input type="submit" name="submit" value="<?php echo $form_settings['update_text']; ?>" />
            <?php } else { ?>
                <input type="hidden" name="action" value="wpuf_submit_register">
                <input type="submit" name="submit" value="<?php echo $form_settings['submit_text']; ?>" />
            <?php } ?>
        </li>
        <?php
    }

    function user_register() {
        check_ajax_referer( 'wpuf_form_add' );

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

        $form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
        $form_vars = $this->get_input_fields( $form_id );
        $form_settings = get_post_meta( $form_id, 'wpuf_form_settings', true );

        list( $user_vars, $taxonomy_vars, $meta_vars ) = $form_vars;

        // search if rs captcha is there
        if ( $this->search( $user_vars, 'input_type', 'really_simple_captcha' ) ) {
            $this->validate_rs_captcha();
        }

        // check recaptcha
        if ( $this->search( $user_vars, 'input_type', 'recaptcha' ) ) {
            $this->validate_re_captcha();
        }

        $has_username_field = false;
        $username = '';
        $user_email = '';
        $firstname = '';
        $lastname = '';

        // don't let to be registered if no email address given
        if ( !isset( $_POST['user_email']) ) {
            $this->send_error( __( 'An Email address is required', 'wpuf' ) );
        }

        // if any username given, check if it exists
        if ( $this->search( $user_vars, 'name', 'user_login' )) {
            $has_username_field = true;
            $username = sanitize_user( trim( $_POST['user_login'] ) );

            if ( username_exists( $username ) ) {
                $this->send_error( __( 'Username already exists.', 'wpuf' ) );
            }
        }

        // if any email address given, check if it exists
        if ( $this->search( $user_vars, 'name', 'user_email' )) {
            $user_email = trim( $_POST['user_email'] );

            if ( email_exists( $user_email ) ) {
                $this->send_error( __( 'E-mail address already exists.', 'wpuf' ) );
            }
        }

        // if there isn't any username field in the form, lets guess a username
        if (!$has_username_field) {
            $username = $this->guess_username( $email );
        }

        if ( !validate_username( $username ) ) {
            $this->send_error( __( 'Username is not valid', 'wpuf' ) );
        }

        // verify password
        if ( $pass_element = $this->search($user_vars, 'name', 'password') ) {
            $pass_element = current( $pass_element );
            $password = $_POST['pass1'];
            $password_repeat = $_POST['pass2'];

            // min length check
            if ( strlen( $password ) < intval( $pass_element['min_length'] ) ) {
                $this->send_error( sprintf( __( 'Password must be %s character long', 'wpuf' ), $pass_element['min_length'] ) );
            }

            // repeat password check
            if ( $password != $password_repeat ) {
                $this->send_error( __( 'Password didn\'t match', 'wpuf' ) );
            }
        } else {
            $password = wp_generate_password();
        }

        // prepare meta fields
        list( $meta_key_value, $multi_repeated, $files ) = $this->prepare_meta_fields( $meta_vars );

        // seems like we don't have any error. Lets register the user
        $user_id = wp_create_user( $username, $password, $user_email );

        if ( is_wp_error( $user_id ) ) {
            $this->send_error( $user_id->get_error_message() );

        } else {

            $userdata = array(
                'ID' => $user_id,
                'first_name' => $this->search( $user_vars, 'name', 'first_name' ) ? $_POST['first_name'] : '',
                'last_name' => $this->search( $user_vars, 'name', 'last_name' ) ? $_POST['last_name'] : '',
                'nickname' => $this->search( $user_vars, 'name', 'nickname' ) ? $_POST['nickname'] : '',
                'user_url' => $this->search( $user_vars, 'name', 'user_url' ) ? $_POST['user_url'] : '',
                'description' => $this->search( $user_vars, 'name', 'description' ) ? $_POST['description'] : '',
                'role' => $form_settings['role']
            );

            $user_id = wp_update_user( $userdata );

            if ( $user_id ) {

                // set featured image if there's any
                if ( isset( $_POST['wpuf_files']['avatar'] ) ) {
                    $attachment_id = $_POST['wpuf_files']['avatar'][0];

                    $upload_dir = wp_upload_dir();
                    $uploaded_file = wp_get_attachment_url( $attachment_id );
                    $relative_url = str_replace( $upload_dir['baseurl'], '', $uploaded_file );

                    if ( function_exists( 'wp_get_image_editor' ) ) {
                        // try to crop the photo if it's big
                        $file_path = $upload_dir['basedir'] . $relative_url;

                        // as the image upload process generated a bunch of images
                        // try delete the intermediate sizes.
                        $ext = strrchr( $file_path, '.');
                        $file_path_w_ext = str_replace( $ext, '', $file_path );
                        $small_url = $file_path_w_ext . '-avatar' . $ext;
                        $relative_url = str_replace( $upload_dir['basedir'], '', $small_url);

                        $editor = wp_get_image_editor( $file_path );

                        if ( !is_wp_error( $editor ) ) {
                            $editor->resize( 100, 100, true );
                            $editor->save( $small_url );

                            // if the file creation successfull, delete the original attachment
                            if ( file_exists( $small_url ) ) {
                                wp_delete_attachment( $attachment_id, true );
                            }
                        }
                    }

                    update_user_meta($user_id, 'user_avatar', $relative_url);
                }

                // save all custom fields
                foreach ($meta_key_value as $meta_key => $meta_value) {
                    update_user_meta( $user_id, $meta_key, $meta_value );
                }

                // save any multicolumn repeatable fields
                foreach ($multi_repeated as $repeat_key => $repeat_value) {
                    // first, delete any previous repeatable fields
                    delete_user_meta( $user_id, $repeat_key );

                    // now add them
                    foreach ($repeat_value as $repeat_field) {
                        add_user_meta( $user_id, $repeat_key, $repeat_field );
                    }
                } //foreach

                // send user notification
                wp_new_user_notification( $user_id, $password );

                //redirect URL
                $show_message = false;
                if ( $form_settings['redirect_to'] == 'page' ) {
                    $redirect_to = get_permalink( $form_settings['page_id'] );
                } elseif ( $form_settings['redirect_to'] == 'url' ) {
                    $redirect_to = $form_settings['url'];
                } elseif ( $form_settings['redirect_to'] == 'same' ) {
                    $show_message = true;
                } else {
                    $redirect_to = get_permalink( $post_id );
                }

                // send the response
                $response = array(
                    'success' => true,
                    'post_id' => $user_id,
                    'redirect_to' => $redirect_to,
                    'show_message' => $show_message,
                    'message' => $form_settings['message']
                );

                echo json_encode( $response );
                exit;

            } // endif

        }

        echo json_encode( array(
            'success' => false,
            'error' => __( 'Something went wrong', 'wpuf' )
        ) );

        exit;
    }

}
