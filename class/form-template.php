<?php
/*
1. Featured image,
2. Image Uploads,
3. URL,
4. Emails
5. HTML
6. date
7. Colorpicker
8. Address
9. Custom Taxonomies
10. Multicolumn Input
11. Numeric stepper. (plus, minus clicker)
12. Captcha [reCaptcha, really simple Captcha]

*/

/**
 * WPUF Form builder template
 */
class WPUF_Form_Template {

    static $input_name = 'wpuf_input';

    function __construct() {

    }

    /**
     * Initializes the WPUF_Form_Template() class
     *
     * Checks for an existing WPUF_Form_Template() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new WPUF_Form_Template();
        }

        return $instance;
    }

    public static function legend( $title = 'Field Name', $values = array() ) {
        $field_label = $values ? ': <strong>' . $values['label'] . '</strong>' : '';
        ?>
        <div class="wpuf-legend">
            <div class="wpuf-label"><?php echo $title . $field_label; ?></div>
            <div class="wpuf-actions">
                <a href="#" class="wpuf-remove"><?php _e( 'Remove', 'wpuf' ); ?></a>
                <a href="#" class="wpuf-toggle"><?php _e( 'Toggle', 'wpuf' ); ?></a>
            </div>
        </div> <!-- .wpuf-legend -->
        <?php
    }

    public static function common( $id, $field_name_value = '', $custom_field = true, $values = array() ) {
        $tpl = '%s[%d][%s]';
        $required_name = sprintf( $tpl, self::$input_name, $id, 'required' );
        $field_name = sprintf( $tpl, self::$input_name, $id, 'name' );
        $label_name = sprintf( $tpl, self::$input_name, $id, 'label' );
        $is_meta_name = sprintf( $tpl, self::$input_name, $id, 'is_meta' );
        $help_name = sprintf( $tpl, self::$input_name, $id, 'help' );
        $css_name = sprintf( $tpl, self::$input_name, $id, 'css' );

        // $field_name_value = $field_name_value ?
        $required = $values ? esc_attr( $values['required'] ) : 'yes';
        $label_value = $values ? esc_attr( $values['label'] ) : '';
        $help_value = $values ? esc_textarea( $values['help'] ) : '';

        if ($custom_field && $values) {
            $field_name_value = $values['name'];
        }

        // var_dump($values);
        // var_dump($required, $label_value, $help_value);
        ?>
        <div class="wpuf-form-rows">
            <label><?php _e( 'Required', 'wpuf' ); ?></label>

            <?php //self::hidden_field($order_name, ''); ?>

            <div class="wpuf-form-sub-fields">
                <label><input type="radio" name="<?php echo $required_name; ?>" value="yes"<?php checked( $required, 'yes' ); ?>> <?php _e( 'Yes', 'wpuf' ); ?> </label>
                <label><input type="radio" name="<?php echo $required_name; ?>" value="no"<?php checked( $required, 'no' ); ?>> <?php _e( 'No', 'wpuf' ); ?> </label>
            </div>
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Field Label', 'wpuf' ); ?></label>
            <input type="text" data-type="label" name="<?php echo $label_name; ?>" value="<?php echo $label_value; ?>">
        </div> <!-- .wpuf-form-rows -->

        <?php if ( $custom_field ) { ?>
            <div class="wpuf-form-rows">
                <label><?php _e( 'Meta Key', 'wpuf' ); ?></label>
                <input type="text" name="<?php echo $field_name; ?>" value="<?php echo $field_name_value; ?>">
                <input type="hidden" name="<?php echo $is_meta_name; ?>" value="yes">
            </div> <!-- .wpuf-form-rows -->
        <?php } else { ?>

            <input type="hidden" name="<?php echo $field_name; ?>" value="<?php echo $field_name_value; ?>">
            <input type="hidden" name="<?php echo $is_meta_name; ?>" value="no">

        <?php } ?>

        <div class="wpuf-form-rows">
            <label><?php _e( 'Help text', 'wpuf' ); ?></label>
            <textarea name="<?php echo $help_name; ?>"><?php echo $help_value; ?></textarea>
        </div> <!-- .wpuf-form-rows -->

        <?php
    }

    public static function common_text( $id, $values = array() ) {
        $tpl = '%s[%d][%s]';
        $placeholder_name = sprintf( $tpl, self::$input_name, $id, 'placeholder' );
        $default_name = sprintf( $tpl, self::$input_name, $id, 'default' );
        $size_name = sprintf( $tpl, self::$input_name, $id, 'size' );

        $placeholder_value = $values ? esc_attr( $values['placeholder'] ) : '';
        $default_value = $values ? esc_attr( $values['default'] ) : '';
        $size_value = $values ? esc_attr( $values['size'] ) : '40';

        // var_dump($values);
        ?>
        <div class="wpuf-form-rows">
            <label><?php _e( 'Placeholder text', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $placeholder_name; ?>" title="<?php esc_attr_e( 'Text for HTML5 placeholder attribute', 'wpuf' ); ?>" value="<?php echo $placeholder_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Default value', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $default_name; ?>" title="<?php esc_attr_e( 'The default value this field will have', 'wpuf' ); ?>" value="<?php echo $default_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Size', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $size_name; ?>" title="<?php esc_attr_e( 'Size of this input field', 'wpuf' ); ?>" value="<?php echo $size_value; ?>" />
        </div> <!-- .wpuf-form-rows -->
        <?php
    }

    public static function common_textarea( $id, $values = array() ) {
        $tpl = '%s[%d][%s]';
        $rows_name = sprintf( $tpl, self::$input_name, $id, 'rows' );
        $cols_name = sprintf( $tpl, self::$input_name, $id, 'cols' );
        $rich_name = sprintf( $tpl, self::$input_name, $id, 'rich' );
        $placeholder_name = sprintf( $tpl, self::$input_name, $id, 'placeholder' );
        $default_name = sprintf( $tpl, self::$input_name, $id, 'default' );

        $rows_value = $values ? esc_attr( $values['rows'] ) : '5';
        $cols_value = $values ? esc_attr( $values['cols'] ) : '25';
        $rich_value = $values ? esc_attr( $values['rich'] ) : 'no';
        $placeholder_value = $values ? esc_attr( $values['placeholder'] ) : '';
        $default_value = $values ? esc_attr( $values['default'] ) : '';

        // var_dump($values);
        ?>
        <div class="wpuf-form-rows">
            <label><?php _e( 'Rows', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $rows_name; ?>" title="Number of rows in textarea" value="<?php echo $rows_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Columns', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $cols_name; ?>" title="Number of columns in textarea" value="<?php echo $cols_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Placeholder text', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $placeholder_name; ?>" title="text for HTML5 placeholder attribute" value="<?php echo $placeholder_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Default value', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $default_name; ?>" title="the default value this field will have" value="<?php echo $default_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Enable Rich textarea', 'wpuf' ); ?></label>

            <div class="wpuf-form-sub-fields">
                <label><input type="radio" name="<?php echo $rich_name; ?>" value="yes"<?php checked( $rich_value, 'yes' ); ?>> Yes </label>
                <label><input type="radio" name="<?php echo $rich_name; ?>" value="no"<?php checked( $rich_value, 'no' ); ?>> No </label>
            </div>
        </div> <!-- .wpuf-form-rows -->
        <?php
    }

    public static function hidden_field( $name, $value = '' ) {
        printf( '<input type="hidden" name="%s" value="%s" />', self::$input_name . $name, $value );
    }

    function radio_fields( $field_id, $name, $values = array() ) {
        // var_dump($values);
        $selected_name = sprintf( '%s[%d][selected]', self::$input_name, $field_id );
        $input_name = sprintf( '%s[%d][%s]', self::$input_name, $field_id, $name );

        $selected_value = ( $values && isset( $values['selected'] ) ) ? $values['selected'] : '';

        if ( $values && $values['options'] > 0 ) {
            foreach ($values['options'] as $key => $value) {
                ?>
                <div>
                    <input type="radio" name="<?php echo $selected_name ?>" value="<?php echo $value; ?>" <?php checked( $selected_value, $value ); ?>>
                    <input type="text" name="<?php echo $input_name; ?>[]" value="<?php echo $value; ?>">

                    <?php self::remove_button(); ?>
                </div>
                <?php
            }
        } else {
        ?>
            <div>
                <input type="radio" name="<?php echo $selected_name ?>">
                <input type="text" name="<?php echo $input_name; ?>[]" value="">

                <?php self::remove_button(); ?>
            </div>
        <?php
        }
    }

    function checkbox_field( $field_id, $name, $values = array() ) {
        // var_dump($values);
        $selected_name = sprintf( '%s[%d][selected]', self::$input_name, $field_id );
        $input_name = sprintf( '%s[%d][%s]', self::$input_name, $field_id, $name );

        $selected_value = ( $values && isset( $values['selected'] ) ) ? $values['selected'] : array();
        // var_dump($selected_value);

        if ( $values && $values['options'] > 0 ) {
            foreach ($values['options'] as $key => $value) {
                ?>
                <div>
                    <input type="checkbox" name="<?php echo $selected_name ?>[]" value="<?php echo $value; ?>"<?php echo in_array($value, $selected_value) ? ' checked="checked"' : ''; ?> />
                    <input type="text" name="<?php echo $input_name; ?>[]" value="<?php echo $value; ?>">

                    <?php self::remove_button(); ?>
                </div>
                <?php
            }
        } else {
        ?>
            <div>
                <input type="checkbox" name="<?php echo $selected_name ?>[]">
                <input type="text" name="<?php echo $input_name; ?>[]" value="">

                <?php self::remove_button(); ?>
            </div>
        <?php
        }
    }

    public static function remove_button() {
        $add = plugins_url( 'images/add.png', dirname( __FILE__ ) );
        $remove = plugins_url( 'images/remove.png', dirname( __FILE__ ) );
        ?>
        <img style="cursor:pointer; margin:0 3px;" alt="add another choice" title="add another choice" class="wpuf-clone-field" src="<?php echo $add; ?>">
        <img style="cursor:pointer;" class="wpuf-remove-field" alt="remove this choice" title="remove this choice" src="<?php echo $remove; ?>">
        <?php
    }

    public static function get_buffered($func, $field_id, $label) {
        ob_start();

        self::$func( $field_id, $label );

        return ob_get_clean();
    }

    public static function post_title( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_title">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'text' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_title' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'post_title', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_content( $field_id, $label, $values = array() ) {
        // var_dump($values);

        $image_insert_name = sprintf('%s[%d][insert_image]', self::$input_name, $field_id);
        $image_insert_value = isset( $values['insert_image'] ) ? $values['insert_image'] : 'yes';
        ?>
        <li class="post_content">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'textarea' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_content' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'post_content', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::common_textarea( $field_id, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Enable Image Insertion', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label>
                            <?php self::hidden_field( "[$field_id][insert_image]", 'no' ); ?>
                            <input type="checkbox" name="<?php echo $image_insert_name ?>" value="yes"<?php checked( $image_insert_value, 'yes' ); ?> />
                            <?php _e( 'Enable image upload in post area', 'wpuf' ); ?>
                        </label>
                    </div>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_excerpt( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_excerpt">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'textarea' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_excerpt' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'post_excerpt', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::common_textarea( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_tags( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_tags">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'text' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_tags' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'tags', false, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function featured_image( $field_id, $label, $values = array() ) {
        $max_file_name = sprintf('%s[%d][max_size]', self::$input_name, $field_id);
        $max_file_value = $values ? $values['max_size'] : '2';
        $help = esc_attr( __( 'Enter maximum upload size limit in MB', 'wpuf' ) );
        ?>
        <li class="featured_image">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'image_upload' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'featured_image' ); ?>
            <?php self::hidden_field( "[$field_id][count]", '1' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'featured_image', false, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. file size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_file_name; ?>" value="<?php echo $max_file_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function post_category( $field_id, $label, $values = array() ) {
        ?>
        <li class="post_category">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'post_category' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'category', false, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_text( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_text">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'text' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_text' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_textarea( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_textarea">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'textarea' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_textarea' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_textarea( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_radio( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_radio">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'radio' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_radio' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php self::radio_fields( $field_id, 'options', $values ); ?>
                    </div> <!-- .wpuf-form-sub-fields -->
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_checkbox( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_checkbox">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'checkbox' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_checkbox' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php self::checkbox_field( $field_id, 'options', $values ); ?>
                    </div> <!-- .wpuf-form-sub-fields -->
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_select( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_select">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'select' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_select' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php self::radio_fields( $field_id, 'options', $values ); ?>
                    </div> <!-- .wpuf-form-sub-fields -->
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_multiselect( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_multiselect">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'multiselect' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_multiselect' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php self::radio_fields( $field_id, 'options', $values ); ?>
                    </div> <!-- .wpuf-form-sub-fields -->
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_image( $field_id, $label, $values = array() ) {
        $max_size_name = sprintf('%s[%d][max_size]', self::$input_name, $field_id);
        $max_files_name = sprintf('%s[%d][count]', self::$input_name, $field_id);

        $max_size_value = $values ? $values['max_size'] : '2';
        $max_files_value = $values ? $values['count'] : '1';

        $help = esc_attr( __( 'Enter maximum upload size limit in MB', 'wpuf' ) );
        $count = esc_attr( __( 'Number of images can be uploaded', 'wpuf' ) );
        ?>
        <li class="custom-field custom_image">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'image_upload' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_image' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, 'custom_image', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. file size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_size_name; ?>" value="<?php echo $max_size_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. files', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_files_name; ?>" value="<?php echo $max_files_value; ?>" title="<?php echo $count; ?>">
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_file( $field_id, $label, $values = array() ) {
        $max_size_name = sprintf('%s[%d][max_size]', self::$input_name, $field_id);
        $max_files_name = sprintf('%s[%d][count]', self::$input_name, $field_id);
        $extensions_name = sprintf('%s[%d][extension][]', self::$input_name, $field_id);

        $max_size_value = $values ? $values['max_size'] : '2';
        $max_files_value = $values ? $values['count'] : '1';
        $extensions_value = $values ? $values['extension'] : array('images', 'audio', 'video', 'pdf', 'office', 'zip', 'exe', 'csv');

        $extesions = wpuf_allowed_extensions();

        // var_dump($extesions);

        $help = esc_attr( __( 'Enter maximum upload size limit in MB', 'wpuf' ) );
        $count = esc_attr( __( 'Number of images can be uploaded', 'wpuf' ) );
        ?>
        <li class="custom-field custom_image">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'file_upload' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_file' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. file size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_size_name; ?>" value="<?php echo $max_size_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. files', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_files_name; ?>" value="<?php echo $max_files_value; ?>" title="<?php echo $count; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Allowed Files', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php foreach ($extesions as $key => $value) {
                            ?>
                            <label>
                                <input type="checkbox" name="<?php echo $extensions_name; ?>" value="<?php echo $key; ?>"<?php echo in_array($key, $extensions_value) ? ' checked="checked"' : ''; ?>>
                                <?php printf('%s (%s)', $value['label'], str_replace( ',', ', ', $value['ext'] ) ) ?>
                            </label> <br />
                        <?php } ?>
                    </div>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_url( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_url">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'url' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_url' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_email( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field custom_email">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'email' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_email' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_repeater( $field_id, $label, $values = array() ) {
        $tpl = '%s[%d][%s]';

        $enable_column_name = sprintf( '%s[%d][multiple]', self::$input_name, $field_id );
        $column_names = sprintf( '%s[%d][columns]', self::$input_name, $field_id );
        $has_column = ( $values && isset( $values['multiple'] ) ) ? true : false;

        $placeholder_name = sprintf( $tpl, self::$input_name, $field_id, 'placeholder' );
        $default_name = sprintf( $tpl, self::$input_name, $field_id, 'default' );
        $size_name = sprintf( $tpl, self::$input_name, $field_id, 'size' );

        $placeholder_value = $values ? esc_attr( $values['placeholder'] ) : '';
        $default_value = $values ? esc_attr( $values['default'] ) : '';
        $size_value = $values ? esc_attr( $values['size'] ) : '40';

        ?>
        <li class="custom-field custom_repeater">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'repeat' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_repeater' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Multiple Column', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label><input type="checkbox" class="multicolumn" name="<?php echo $enable_column_name ?>"<?php echo $has_column ? ' checked="checked"' : ''; ?> value="true"> Enable Multi Column</label>
                    </div>
                </div>

                <div class="wpuf-form-rows<?php echo $has_column ? ' wpuf-hide' : ''; ?>">
                    <label><?php _e( 'Placeholder text', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $placeholder_name; ?>" title="text for HTML5 placeholder attribute" value="<?php echo $placeholder_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows<?php echo $has_column ? ' wpuf-hide' : ''; ?>">
                    <label><?php _e( 'Default value', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $default_name; ?>" title="the default value this field will have" value="<?php echo $default_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $size_name; ?>" title="Size of this input field" value="<?php echo $size_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows column-names<?php echo $has_column ? '' : ' wpuf-hide'; ?>">
                    <label><?php _e( 'Columns', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                    <?php

                        if ( $values && $values['columns'] > 0 ) {
                            foreach ($values['columns'] as $key => $value) {
                                ?>
                                <div>
                                    <input type="text" name="<?php echo $column_names; ?>[]" value="<?php echo $value; ?>">

                                    <?php self::remove_button(); ?>
                                </div>
                                <?php
                            }
                        } else {
                        ?>
                            <div>
                                <input type="text" name="<?php echo $column_names; ?>[]" value="">

                                <?php self::remove_button(); ?>
                            </div>
                        <?php
                        }
                    ?>
                    </div>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_html( $field_id, $label, $values = array() ) {
        $title_name = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $html_name = sprintf( '%s[%d][html]', self::$input_name, $field_id );

        $title_value = $values ? esc_attr( $values['label'] ) : '';
        $html_value = $values ? esc_attr( $values['html'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'html' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_html' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'HTML Codes', 'wpuf' ); ?></label>
                    <textarea class="smallipopInput" title="Paste your HTML codes, WordPress shortcodes will also work here" name="<?php echo $html_name; ?>" rows="10"><?php echo esc_html( $html_value ); ?></textarea>
                </div>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function section_break( $field_id, $label, $values = array() ) {
        $title_name = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $description_name = sprintf( '%s[%d][description]', self::$input_name, $field_id );

        $title_value = $values ? esc_attr( $values['label'] ) : '';
        $description_value = $values ? esc_attr( $values['description'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'section_break' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'section_break' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Description', 'wpuf' ); ?></label>
                    <textarea class="smallipopInput" title="Some details text about the section" name="<?php echo $description_name; ?>" rows="3"><?php echo esc_html( $description_value ); ?></textarea>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function taxonomy( $field_id, $label, $taxonomy = '', $values = array() ) {
        $type_name = sprintf( '%s[%d][type]', self::$input_name, $field_id );
        $exclude_name = sprintf( '%s[%d][exclude]', self::$input_name, $field_id );

        $type_value = $values ? esc_attr( $values['type'] ) : 'select';
        $exclude_value = $values ? esc_attr( $values['exclude'] ) : '';
        ?>
        <li class="taxonomy <?php echo $taxonomy; ?>">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'taxonomy' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'taxonomy' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, $taxonomy, false, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Type', 'wpuf' ); ?></label>
                    <select name="<?php echo $type_name ?>">
                        <option value="select"<?php selected( $type_value, 'select' ); ?>><?php _e( 'Dropdown', 'wpuf' ); ?></option>
                        <option value="multiselect"<?php selected( $type_value, 'multiselect' ); ?>><?php _e( 'Multi Select', 'wpuf' ); ?></option>
                        <option value="checkbox"<?php selected( $type_value, 'checkbox' ); ?>><?php _e( 'Checkbox', 'wpuf' ); ?></option>
                    </select>
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Exclude terms', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $exclude_name; ?>" title="Enter the term ID's as comma separated to exclude in the form" value="<?php echo $exclude_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function recaptcha( $field_id, $label, $values = array() ) {
        $title_name = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $html_name = sprintf( '%s[%d][html]', self::$input_name, $field_id );

        $title_value = $values ? esc_attr( $values['label'] ) : '';
        $html_value = $values ? esc_attr( $values['html'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'recaptcha' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'recaptcha' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />

                        <div class="description" style="margin-top: 8px;">
                            <?php printf( __( "Insert your public key and private key in <a href='%s'>plugin settings</a>. <a href='https://www.google.com/recaptcha/' target='_blank'>Register</a> first if you don't have any keys." ), admin_url( 'admin.php?page=wpuf-settings' ) ); ?>
                        </div>
                    </div> <!-- .wpuf-form-rows -->
                </div>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

}