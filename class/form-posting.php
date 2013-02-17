<?php

class WPUF_Form_Posting {

    protected $meta_key = 'wpuf_form';
    protected $separator = ', ';
    protected $config_id = '_wpuf_form_id';

    function __construct() {
        add_shortcode( 'wpuf_form', array($this, 'add_post_shortcode') );
        add_shortcode( 'wpuf_edit', array($this, 'edit_post_shortcode') );

        // ajax requests
        add_action( 'wp_ajax_wpuf_submit_post', array($this, 'submit_post') );
        add_action( 'wp_ajax_nopriv_wpuf_submit_post', array($this, 'submit_post') );

        // form preview
        add_action( 'wp_ajax_wpuf_form_preview', array($this, 'preview_form') );
    }

    function submit_post() {
        check_ajax_referer( 'wpuf_form_add' );

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

        $form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
        $form_vars = $this->get_input_fields( $form_id );
        $form_settings = get_post_meta( $form_id, 'wpuf_form_settings', true );

        list( $post_vars, $taxonomy_vars, $meta_vars ) = $form_vars;
        // var_dump($post_vars, $taxonomy_vars, $meta_vars);

        if (isset($_POST['recaptcha_challenge_field']) && isset($_POST['recaptcha_response_field'])) {
            $resp = recaptcha_check_answer( wpuf_get_option('recaptcha_private'), $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

            if (!$resp->is_valid) {
                echo json_encode( array(
                    'success' => false,
                    'error' => __( 'reCAPTCHA validation failed', 'wpuf' )
                ));

                exit;
            }
        }

        $post_author = null;
        $default_post_author = wpuf_get_option( 'default_post_owner' );

        // Guest Stuffs: check for guest post
        if ( !is_user_logged_in() ) {
            if ( $form_settings['guest_post'] == 'true' && $form_settings['guest_details'] == 'true' ) {
                $guest_name = trim( $_POST['guest_name'] );
                $guest_email = trim( $_POST['guest_email'] );

                // check if the user email already exists
                $user = get_user_by( 'email', $guest_email );
                if ( $user ) {
                    $post_author = $user->ID;
                } else {

                    // user not found, lets register him
                    $username = sanitize_user( $guest_name );
                    $user_pass = wp_generate_password( 12, false );
                    $user_id = wp_create_user( $username, $user_pass, $guest_email );

                    if ( !$user_id ) {
                        //something went wrong creating the user, set post author to the default author
                        $post_author = $default_post_author;
                    } else {
                        update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.
                        wp_new_user_notification( $user_id, $user_pass );

                        // update display name to full name
                        wp_update_user( array('ID' => $user_id, 'display_name' => $guest_name) );

                        $post_author = $user_id;
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

        $category = isset( $_POST['category'] ) ? $_POST['category'] : array();
        $postarr = array(
            'post_type' => $form_settings['post_type'],
            'post_status' => $form_settings['post_status'],
            'post_author' => $post_author,
            'post_title' => isset( $_POST['post_title'] ) ? trim( $_POST['post_title'] ) : '',
            'post_content' => isset( $_POST['post_content'] ) ? trim( $_POST['post_content'] ) : '',
            'post_excerpt' => isset( $_POST['post_excerpt'] ) ? trim( $_POST['post_excerpt'] ) : '',
            'tags_input' => isset( $_POST['tags'] ) ? explode(',', $_POST['tags']) : array(),
            'post_category' => is_array( $category ) ? $category : array( $category )
        );

        // if post_id is passed, we update the post
        if ( isset( $_POST['post_id']) ) {
            $postarr['ID'] = $_POST['post_id'];
        }

        // loop through custom fields
        // skip files, put in a key => value paired array for later executation
        // process repeatable fields separately
        // if the input is array type, implode with separator in a field

        $files = array();
        $meta_key_value = array();
        $multi_repeated = array(); //multi repeated fields will in sotre duplicated meta key

        foreach ($meta_vars as $key => $value) {

            // put files in a separate array, we'll process it later
            if ( ($value['input_type'] == 'file_upload') || ($value['input_type'] == 'image_upload') ) {
                $files[] = array(
                    'name' => $value['name'],
                    'value' => isset( $_POST['wpuf_files'][$value['name']] ) ? $_POST['wpuf_files'][$value['name']] : array()
                );

                // process repeatable fiels
            } elseif ($value['input_type'] == 'repeat') {

                // if it is a multi column repeat field
                if (isset($value['multiple'])) {

                    // if there's any items in the array, process it
                    if ($_POST[$value['name']]) {

                        $ref_arr = array();
                        $cols = count( $value['columns'] );
                        $rows = count( $_POST[$value['name']] );

                        // loop through columns
                        for ($i = 0; $i < $cols; $i++) {

                            // loop through the rows and store in a temp array
                            $temp = array();
                            for ($j = 0; $j < $rows; $j++) {

                                $temp[] = $_POST[$value['name']][$j][$i];
                            }

                            // store all fields in a row with $this->separator separated
                            $ref_arr[] = implode( $this->separator, $temp );
                        }

                        // now, if we found anything in $ref_arr, store to $multi_repeated
                        if ( $ref_arr ) {
                            $multi_repeated[$value['name']] = $ref_arr;
                        }
                    }

                } else {
                    $meta_key_value[$value['name']] = implode( $this->separator, $_POST[$value['name']]);
                }

                // process other fields
            } else {

                // if it's an array, implode with this->separator
                if (is_array($_POST[$value['name']])) {
                    $meta_key_value[$value['name']] = implode( $this->separator, $_POST[$value['name']]);
                } else {
                    $meta_key_value[$value['name']] = trim( $_POST[$value['name']] );
                }
            }
        } //end foreach

        // print_r( $postarr );
        // print_r( $meta_key_value );
        // print_r( $multi_repeated );
        // print_r( $files );
        // print_r( $_POST );
        // print_r( $taxonomy_vars );
        // die();

        // ############ It's Time to Save the World ###############
        $post_id = wp_insert_post( $postarr );

        if ( $post_id ) {
            // set featured image if there's any
            if ( isset( $_POST['wpuf_files']['featured_image'])) {
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
                    $full_url = wp_get_attachment_url( $attachment_id );

                    wpuf_associate_attachment( $attachment_id, $post_id );
                    add_post_meta( $post_id, $file_input['name'], $full_url );
                }
            }

            // set the post form_id for later usage
            update_post_meta( $post_id, $this->config_id, $form_id );

            // save any custom taxonomies
            foreach ($taxonomy_vars as $taxonomy) {
                if (isset($_POST[$taxonomy['name']])) {

                    if ( is_object_in_taxonomy($form_settings['post_type'], $taxonomy['name']) ) {
                        $tax = $_POST[$taxonomy['name']];

                        // if it's not an array, make it one
                        if (!is_array($tax)) {
                            $tax = array( $tax );
                        }

                        wp_set_post_terms( $post_id, $_POST[$taxonomy['name']], $taxonomy['name'] );
                    }
                }
            }


            //redirect URL
            $show_message = false;
            if ( $form_settings['redirect_to'] == 'page' ) {
                $redirect_to = get_permalink( $form_settings['page_id'] );
            } elseif ( $form_settings['redirect_to'] == 'url') {
                $redirect_to = $form_settings['url'];
            } elseif ($form_settings['redirect_to'] == 'same') {
                $show_message = true;
            } else {
                $redirect_to = get_permalink( $post_id );
            }

            // send the response
            $response = array(
                'success' => true,
                'post_id' => $post_id,
                'redirect_to' => $redirect_to,
                'show_message' => $show_message,
                'message' => $form_settings['message']
            );

            echo json_encode( $response );
            exit;
        }

        echo json_encode( array(
            'success' => false,
            'error' => __( 'Something went wrong', 'wpuf' )
        ));

        exit;
    }

    function get_input_fields( $form_id ) {
        $form_vars = get_post_meta( $form_id, $this->meta_key, true );

        $ignore_lists = array('section_break', 'html');
        $post_vars = $meta_vars = $taxonomy_vars = array();

        foreach ($form_vars as $key => $value) {

            // ignore section break and HTML input type
            if (in_array($value['input_type'], $ignore_lists)) {
                continue;
            }

            //separate the post and custom fields
            if ($value['is_meta'] == 'yes') {
                $meta_vars[] = $value;
                continue;
            }

            if ( $value['input_type'] == 'taxonomy' ) {

                // don't add "category"
                if ( $value['name'] == 'category') {
                    continue;
                }

                $taxonomy_vars[] = $value;
            } else {
                $post_vars[] = $value;
            }
        }

        return array($post_vars, $taxonomy_vars, $meta_vars);
    }

    function add_post_shortcode( $atts ) {
        extract( shortcode_atts( array('id' => 0), $atts ) );
        ob_start();

        $this->render_form( $id );

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    function edit_post_shortcode( $atts ) {
        extract( shortcode_atts( array('post_id' => 0), $atts ) );

        ob_start();

        if (!$post_id) {
            $post_id = isset( $_GET['pid'] ) ? intval( $_GET['pid'] ) : 0;
        }

        $form_id = get_post_meta($post_id, $this->config_id, true);

        if ( !$form_id ) {
            return __( "I don't know how to edit this post, I don't have the form ID", 'wpuf' );
        }

        $this->render_form( $form_id, $post_id );


        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Handles the add post shortcode
     *
     * @param $atts
     */
    function render_form( $form_id, $post_id = NULL, $preview = false ) {

        $form_vars = get_post_meta( $form_id, $this->meta_key, true );
        $form_settings = get_post_meta( $form_id, 'wpuf_form_settings', true );

        // var_dump($form_settings);

        if ( !is_user_logged_in() && $form_settings['guest_post'] != 'true' ) {
            echo $form_settings['message_restrict'];
            return;
        }

        if ( $form_vars ) {
            ?>

            <?php if ( !$preview ) { ?>
                <form id="wpuf-form-add" action="" method="post">
            <?php } ?>

                <ul class="wpuf-form">

                    <?php if ( !is_user_logged_in() && $form_settings['guest_post'] == 'true' && $form_settings['guest_details'] == 'true' ) { ?>

                        <li class="el-name">
                            <div class="wpuf-label">
                                <label><?php echo $form_settings['name_label']; ?> <span class="required">*</span></label>
                            </div>

                            <div class="wpuf-fields">
                                <input type="text" required="required" data-required="yes" data-type="text" name="guest_name" value="" size="40">
                            </div>
                        </li>

                        <li class="el-email">
                            <div class="wpuf-label">
                                <label><?php echo $form_settings['email_label']; ?> <span class="required">*</span></label>
                            </div>

                            <div class="wpuf-fields">
                                <input type="email" required="required" data-required="yes" data-type="email" name="guest_email" value="" size="40">
                            </div>
                        </li>

                    <?php } ?>

                    <?php
                    foreach ($form_vars as $key => $form_field) {


                        printf( '<li class="el-%s">', isset( $form_field['name'] ) ? $form_field['name'] : 'class' );

                        switch ($form_field['input_type']) {
                            case 'text':
                                $this->text( $form_field, $post_id );
                                break;

                            case 'textarea':
                                $this->textarea( $form_field, $post_id );
                                break;

                            case 'image_upload':
                                $this->image_upload( $form_field, $post_id );
                                break;

                            case 'select':
                                $this->select( $form_field, false, $post_id );
                                break;

                            case 'multiselect':
                                $this->select( $form_field, true, $post_id );
                                break;

                            case 'radio':
                                $this->radio( $form_field, $post_id );
                                break;

                            case 'checkbox':
                                $this->checkbox( $form_field, $post_id );
                                break;

                            case 'file_upload':
                                $this->file_upload( $form_field, $post_id );
                                break;

                            case 'url':
                                $this->url( $form_field, $post_id );
                                break;

                            case 'email':
                                $this->email( $form_field, $post_id );
                                break;

                            case 'repeat':
                                $this->repeat( $form_field, $post_id );
                                break;

                            case 'taxonomy':
                                $this->taxonomy( $form_field, $post_id );
                                break;

                            case 'section_break':
                                $this->section_break( $form_field, $post_id );
                                break;

                            case 'html':
                                $this->html( $form_field );
                                break;

                            case 'recaptcha':
                                $this->recaptcha( $form_field );
                                break;

                            default:
                                # code...
                                break;
                        }

                        echo '</li>';
                        ?>

                        <?php
                    } //end foreach
                    ?>
                    <li class="wpuf-submit">
                        <div class="wpuf-label">
                            &nbsp;
                        </div>

                        <?php wp_nonce_field( 'wpuf_form_add' );  ?>
                        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                        <input type="hidden" name="action" value="wpuf_submit_post">

                        <?php if( $post_id ) { ?>
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            <input type="submit" name="submit" value="<?php echo $form_settings['update_text']; ?>" />
                        <?php } else { ?>
                            <input type="submit" name="submit" value="<?php echo $form_settings['submit_text']; ?>" />
                        <?php } ?>


                    </li>
                </ul>

            <?php if ( !$preview ) { ?>
                </form>
            <?php } ?>

            <?php
        } //endif
    }

    function preview_form() {
        $form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;

        if ( $form_id ) {
            ?>

            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Form Preview</title>
                <link rel="stylesheet" href="<?php echo plugins_url( '/css/frontend-forms.css', dirname( __FILE__ ) ); ?>">

                <style type="text/css">
                    body {
                        margin: 0;
                        padding: 0;
                        background: #eee;
                    }

                    .container {
                        width: 700px;
                        margin: 0 auto;
                        margin-top: 20px;
                        padding: 20px;
                        background: #fff;
                        border: 1px solid #DFDFDF;
                        -webkit-box-shadow: 1px 1px 2px rgba(0,0,0,0.1);
                        box-shadow: 1px 1px 2px rgba(0,0,0,0.1);
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <?php $this->render_form( $form_id, null, true ); ?>
                </div>
            </body>
            </html>

            <?php
        } else {
            wp_die( 'Error generating the form preview' );
        }

        exit;
    }

    function required_mark( $attr ) {
        if ( isset( $attr['required'] ) && $attr['required'] == 'yes' ) {
            return ' <span class="required">*</span>';
        }
    }

    function required_html5( $attr ) {
        if ( $attr['required'] == 'yes' ) {
            echo ' required="required"';
        }
    }

    function required_class( $attr ) {
        return;
        if ( $attr['required'] == 'yes' ) {
            echo ' required';
        }
    }

    function label( $attr ) {
        ?>
        <div class="wpuf-label">
            <label for="wpuf-<?php echo isset( $attr['name'] ) ? $attr['name'] : 'cls'; ?>"><?php echo $attr['label'] . $this->required_mark( $attr ); ?></label>
        </div>
        <?php
    }

    function text( $attr, $post_id ) {
        if ($post_id) {
            if ($attr['is_meta'] == 'yes') {
                $value = get_post_meta( $post_id, $attr['name'], true );
            } else {

                if ($attr['name'] == 'tags') {
                    $post_tags = wp_get_post_tags( $post_id );
                    $tagsarray = array();
                    foreach ($post_tags as $tag) {
                        $tagsarray[] = $tag->name;
                    }
                    $value = implode( ', ', $tagsarray );
                } else {
                    $value = get_post_field( $attr['name'], $post_id );
                }

            }
        } else {
            $value = $attr['default'];
        }

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">
            <input class="textfield<?php echo $this->required_class( $attr ); ?>" id="wpuf-<?php echo $attr['name']; ?>" type="text" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> name="<?php echo esc_attr( $attr['name'] ); ?>" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $value ) ?>" size="<?php echo esc_attr( $attr['size'] ) ?>" />
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>

        <?php
    }

    function textarea( $attr, $post_id ) {
        $req_class = ( $attr['required'] == 'yes' ) ? 'required' : 'rich-editor';

        if ($post_id) {
            if ($attr['is_meta'] == 'yes') {
                $value = get_post_meta( $post_id, $attr['name'], true );
            } else {
                $value = get_post_field( $attr['name'], $post_id );
            }
        } else {
            $value = $attr['default'];
        }

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">

            <?php if( isset( $attr['insert_image']) && $attr['insert_image'] == 'yes') { ?>
                <div id="wpuf-insert-image-container">
                    <a class="wpuf-button" id="wpuf-insert-image" href="#">
                        <span class="wpuf-media-icon"></span>
                        <?php _e( 'Insert Photo', 'wpuf' ); ?>
                    </a>
                </div>
            <?php } ?>

            <?php
            if ( $attr['rich'] == 'yes' ) {

                printf( '<span class="wpuf-rich-validation" data-required="%s" data-type="rich" data-id="%s"></span>', $attr['required'], $attr['name'] );
                wp_editor( $value, $attr['name'], array('editor_height' => $attr['rows'], 'quicktags' => false, 'media_buttons' => false, 'editor_class' => $req_class) );

            } else {
                ?>
                <textarea class="textareafield<?php echo $this->required_class( $attr ); ?>" id="<?php echo $attr['name']; ?>" name="<?php echo $attr['name']; ?>" data-required="<?php echo $attr['required'] ?>" data-type="textarea"<?php $this->required_html5( $attr ); ?> placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" rows="<?php echo $attr['rows']; ?>" cols="<?php echo $attr['cols']; ?>"><?php echo esc_textarea( $value ) ?></textarea>
            <?php } ?>
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>

        <?php
    }

    function image_upload( $attr, $post_id ) {

        $has_featured_image = false;

        if ($post_id) {
            if ($attr['is_meta'] == 'yes') {
                $url = get_post_meta( $post_id, $attr['name'], true );
            } else {
                // it's a featured image then
                $thumb_id = get_post_thumbnail_id( $post_id );

                if ($thumb_id) {
                    $has_featured_image = true;
                    $featured_image = WPUF_Uploader::attach_html( $thumb_id );
                }
            }
        }

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">
            <div id="wpuf-<?php echo $attr['name']; ?>-upload-container">
                <div class="wpuf-attachment-upload-filelist">
                    <a id="wpuf-<?php echo $attr['name']; ?>-pickfiles" class="button file-selector" href="#"><?php _e( 'Select Image', 'wpuf' ); ?></a>

                    <?php printf( '<span class="wpuf-file-validation" data-required="%s" data-type="file"></span>', $attr['required'] ); ?>

                    <ul class="wpuf-attachment-list thumbnails">
                        <?php
                        if ( $has_featured_image ) {
                            echo $featured_image;
                        }
                        ?>
                    </ul>
                </div>
            </div><!-- .container -->

            <span class="wpuf-help"><?php echo $attr['help']; ?></span>

        </div> <!-- .wpuf-fields -->

        <script type="text/javascript">
            jQuery(function($) {
                new WPUF_Uploader('wpuf-<?php echo $attr['name']; ?>-pickfiles', 'wpuf-<?php echo $attr['name']; ?>-upload-container', <?php echo $attr['count']; ?>, '<?php echo $attr['name']; ?>', 'jpg,jpeg,gif,png,bmp', <?php echo $attr['max_size'] ?>);
            });
        </script>
        <?php
    }

    function select( $attr, $multiselect = false, $post_id ) {
        $selected = isset( $attr['selected'] ) ? $attr['selected'] : '';

        $multi = $multiselect ? ' multiple="multiple"' : '';
        $data_type = $multiselect ? 'multiselect' : 'select';
        $css = $multiselect ? ' class="multiselect"' : '';

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">

            <select<?php echo $css; ?> name="<?php echo $attr['name'] ?>[]"<?php echo $multi; ?> data-required="<?php echo $attr['required'] ?>" data-type="<?php echo $data_type; ?>"<?php $this->required_html5( $attr ); ?>>
                <?php
                if ( $attr['options'] && count( $attr['options'] ) > 0 ) {
                    foreach ($attr['options'] as $option) {
                        ?>
                        <option value="<?php echo esc_attr( $option ); ?>"<?php selected( $selected, $option ); ?>><?php echo $option; ?></option>
                        <?php
                    }
                }
                ?>
            </select>
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>
        <?php
    }

    function radio( $attr, $post_id ) {
        $selected = isset( $attr['selected'] ) ? $attr['selected'] : '';

        if ($post_id) {
            $selected = get_post_meta( $post_id, $attr['name'], true );
        }

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">

            <span data-required="<?php echo $attr['required'] ?>" data-type="radio"></span>

            <?php
            if ( $attr['options'] && count( $attr['options'] ) > 0 ) {
                foreach ($attr['options'] as $option) {
                    ?>

                    <label>
                        <input name="<?php echo $attr['name']; ?>" type="radio" value="<?php echo esc_attr( $option ); ?>"<?php checked( $selected, $option ); ?> />
                        <?php echo $option; ?>
                    </label>
                    <?php
                }
            }
            ?>

        </div>

        <?php
    }

    function checkbox( $attr, $post_id ) {
        $selected = isset( $attr['selected'] ) ? $attr['selected'] : array();

        if ($post_id) {
            $selected = explode( $this->separator, get_post_meta( $post_id, $attr['name'], true ) );
        }

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">
            <span data-required="<?php echo $attr['required'] ?>" data-type="radio"></span>

            <?php
            if ( $attr['options'] && count( $attr['options'] ) > 0 ) {
                foreach ($attr['options'] as $option) {
                    ?>

                    <label>
                        <input type="checkbox" name="<?php echo $attr['name']; ?>[]" value="<?php echo esc_attr( $option ); ?>"<?php echo in_array( $option, $selected ) ? ' checked="checked"' : ''; ?> />
                        <?php echo $option; ?>
                    </label>
                    <?php
                }
            }
            ?>

        </div>

        <?php
    }

    function file_upload( $attr, $post_id ) {

        $this->label( $attr );

        $allowed_ext = '';
        $extensions = wpuf_allowed_extensions();
        if ( is_array( $attr['extension'] ) ) {
            foreach ($attr['extension'] as $ext) {
                $allowed_ext .= $extensions[$ext]['ext'] . ',';
            }
        } else {
            $allowed_ext = '*';
        }

        $uploaded_items = $post_id ? get_post_meta( $post_id, $attr['name'] ) : array();
        ?>

        <div class="wpuf-fields">
            <div id="wpuf-<?php echo $attr['name']; ?>-upload-container">
                <div class="wpuf-attachment-upload-filelist">
                    <a id="wpuf-<?php echo $attr['name']; ?>-pickfiles" class="button file-selector" href="#"><?php _e( 'Select File(s)', 'wpuf' ); ?></a>

                    <?php printf( '<span class="wpuf-file-validation" data-required="%s" data-type="file"></span>', $attr['required'] ); ?>

                    <ul class="wpuf-attachment-list thumbnails">
                        <?php
                        if ( $uploaded_items ) {
                            foreach ($uploaded_items as $attachment) {
                                list( $url, $attachment_id ) = explode( $this->separator, $attachment);
                                echo WPUF_Uploader::attach_html( $attachment_id );
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div><!-- .container -->

            <span class="wpuf-help"><?php echo $attr['help']; ?></span>

        </div> <!-- .wpuf-fields -->

        <script type="text/javascript">
            jQuery(function($) {
                new WPUF_Uploader('wpuf-<?php echo $attr['name']; ?>-pickfiles', 'wpuf-<?php echo $attr['name']; ?>-upload-container', <?php echo $attr['count']; ?>, '<?php echo $attr['name']; ?>', '<?php echo $allowed_ext; ?>', <?php echo $attr['max_size'] ?>);
            });
        </script>
        <?php
    }

    function url( $attr, $post_id ) {

        $value = $post_id ? get_post_meta( $post_id, $attr['name'], true ) : $attr['default'];

        $this->label( $attr );
        ?>

        <div class="wpuf-fields">
            <input id="wpuf-<?php echo $attr['name']; ?>" type="url" class="url" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> name="<?php echo esc_attr( $attr['name'] ); ?>" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $value ) ?>" size="<?php echo esc_attr( $attr['size'] ) ?>" />
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>

        <?php
    }

    function email( $attr, $post_id ) {
        $value = $post_id ? get_post_meta( $post_id, $attr['name'], true ) : $attr['default'];

        $this->label( $attr, $post_id );
        ?>

        <div class="wpuf-fields">
            <input id="wpuf-<?php echo $attr['name']; ?>" type="email" class="email" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> name="<?php echo esc_attr( $attr['name'] ); ?>" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $value ) ?>" size="<?php echo esc_attr( $attr['size'] ) ?>" />
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>

        <?php
    }

    function repeat( $attr, $post_id ) {
        $add = plugins_url( 'images/add.png', dirname( __FILE__ ) );
        $remove = plugins_url( 'images/remove.png', dirname( __FILE__ ) );

        $this->label( $attr, $post_id );
        ?>

        <div class="wpuf-fields">

            <?php if ( isset( $attr['multiple'] ) ) { ?>
                <table>
                    <thead>
                        <tr>
                            <?php
                            $num_columns = count( $attr['columns'] );
                            foreach ($attr['columns'] as $column) {
                                ?>
                                <th>
                                    <?php echo $column; ?>
                                </th>
                            <?php } ?>

                            <th style="visibility: hidden;">
                                Actions
                            </th>
                        </tr>

                    </thead>
                    <tbody>

                        <?php
                        $items = $post_id ? get_post_meta( $post_id, $attr['name'] ) : array();
                        // var_dump($items);

                        if ($items) {
                            foreach ($items as $item_val) {
                                $column_vals = explode( $this->separator, $item_val);
                            ?>

                                <tr>
                                    <?php for ($count = 0; $count < $num_columns; $count++) { ?>
                                        <td>
                                            <input type="text" name="<?php echo $attr['name'] . '[' . $count . ']'; ?>[]" value="<?php echo esc_attr( $column_vals[$count] ); ?>" size="<?php echo esc_attr( $attr['size'] ) ?>" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> />
                                        </td>
                                    <?php } ?>
                                    <td>
                                        <img class="wpuf-clone-field" alt="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" src="<?php echo $add; ?>">
                                        <img class="wpuf-remove-field" alt="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" src="<?php echo $remove; ?>">
                                    </td>
                                </tr>

                            <?php } //endforeach ?>

                        <?php } else { ?>

                            <tr>
                                <?php for ($count = 0; $count < $num_columns; $count++) { ?>
                                    <td>
                                        <input type="text" name="<?php echo $attr['name'] . '[' . $count . ']'; ?>[]" size="<?php echo esc_attr( $attr['size'] ) ?>" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> />
                                    </td>
                                <?php } ?>
                                <td>
                                    <img class="wpuf-clone-field" alt="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" src="<?php echo $add; ?>">
                                    <img class="wpuf-remove-field" alt="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" src="<?php echo $remove; ?>">
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>
                </table>

            <?php } else { ?>


                <table>
                    <?php
                    $items = $post_id ? explode( $this->separator, get_post_meta( $post_id, $attr['name'], true ) ) : array();

                    if ($items) {
                        foreach ($items as $item) {
                        ?>
                            <tr>
                                <td>
                                    <input id="wpuf-<?php echo $attr['name']; ?>" type="text" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> name="<?php echo esc_attr( $attr['name'] ); ?>[]" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $item ) ?>" size="<?php echo esc_attr( $attr['size'] ) ?>" />
                                </td>
                                <td>
                                    <img style="cursor:pointer; margin:0 3px;" alt="add another choice" title="add another choice" class="wpuf-clone-field" src="<?php echo $add; ?>">
                                    <img style="cursor:pointer;" class="wpuf-remove-field" alt="remove this choice" title="remove this choice" src="<?php echo $remove; ?>">
                                </td>
                            </tr>
                        <?php } //endforeach ?>
                    <?php } else { ?>

                        <tr>
                            <td>
                                <input id="wpuf-<?php echo $attr['name']; ?>" type="text" data-required="<?php echo $attr['required'] ?>" data-type="text"<?php $this->required_html5( $attr ); ?> name="<?php echo esc_attr( $attr['name'] ); ?>[]" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $attr['default'] ) ?>" size="<?php echo esc_attr( $attr['size'] ) ?>" />
                            </td>
                            <td>
                                <img style="cursor:pointer; margin:0 3px;" alt="add another choice" title="add another choice" class="wpuf-clone-field" src="<?php echo $add; ?>">
                                <img style="cursor:pointer;" class="wpuf-remove-field" alt="remove this choice" title="remove this choice" src="<?php echo $remove; ?>">
                            </td>
                        </tr>

                    <?php } ?>

                </table>
            <?php } ?>
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>

        <?php
    }

    function taxonomy( $attr, $post_id ) {
        $exclude = $attr['exclude'];
        $taxonomy = $attr['name'];

        $selected = '';
        $terms = array();
        if ($post_id) {
            $terms = wp_get_post_terms( $post_id, $taxonomy );
            $selected = isset( $terms[0] ) ? $terms[0]->term_id : '';
        }

        $this->label( $attr );

        // var_dump($attr);
        ?>

        <div class="wpuf-fields">
            <?php
            switch ($attr['type']) {
                case 'select':

                    $required = sprintf( 'data-required="%s" data-type="select"', $attr['required'] );
                    $select = wp_dropdown_categories( 'show_option_none=' . __( '-- Select --', 'wpuf' ) . "&hierarchical=1&hide_empty=0&orderby=name&name=$taxonomy&id=$taxonomy&taxonomy=$taxonomy&show_count=0&echo=0&title_li=&use_desc_for_title=1&class=$taxonomy&exclude=" . $exclude . '&selected=' . $selected );
                    echo str_replace('<select', '<select ' . $required, $select);
                    break;

                case 'multiselect':
                    $required = sprintf( 'data-required="%s" data-type="multiselect"', $attr['required'] );
                    $select = wp_dropdown_categories( 'show_option_none=' . __( '-- Select --', 'wpuf' ) . "&hierarchical=1&hide_empty=0&orderby=name&name={$taxonomy}[]&id=$taxonomy&taxonomy=$taxonomy&show_count=0&echo=0&title_li=&use_desc_for_title=1&class=$taxonomy multiselect&exclude=" . $exclude . '&selected=' . $selected );
                    echo str_replace('<select', '<select multiple="multiple" ' . $required, $select);
                    break;

                case 'checkbox':
                    printf( '<span data-required="%s" data-type="tax-checkbox" />', $attr['required'] );
                    wpuf_category_checklist( $post_id, false, $taxonomy, $exclude);
                    break;

                default:
                    # code...
                    break;
            }
            ?>
            <span class="wpuf-help"><?php echo $attr['help']; ?></span>
        </div>

        <?php
    }

    function html( $attr ) {
        $this->label( $attr );
        ?>
        <div class="wpuf-fields">
            <?php echo do_shortcode( $attr['html'] ); ?>
        </div>
        <?php
    }

    function recaptcha( $attr ) {
        $this->label( $attr );

        ?>
        <div class="wpuf-fields">
            <?php echo recaptcha_get_html( wpuf_get_option('recaptcha_public') ); ?>
        </div>
        <?php
    }

    function section_break( $attr ) {
        ?>
        <div class="wpuf-section-wrap">
            <h2 class="wpuf-section-title"><?php echo $attr['label']; ?></h2>
            <div class="wpuf-section-details"><?php echo $attr['description']; ?></div>
        </div>
        <?php
    }

}