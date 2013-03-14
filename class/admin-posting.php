<?php

/**
 * Admin side posting handler
 *
 * Builds custom fields UI for post add/edit screen
 * and handles value saving.
 *
 * @package WP User Frontend
 */
class WPUF_Admin_Posting {

    protected $separator = ', ';

    function __construct() {
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_script') );

        add_action( 'save_post', array( $this, 'save_meta' ), 1, 2 ); // save the custom fields
    }

    function enqueue_script() {
        $path = plugins_url( '', dirname( __FILE__ ) );

        wp_enqueue_style( 'jquery-ui', $path . '/css/jquery-ui-1.9.1.custom.css' );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-slider' );
        wp_enqueue_script( 'jquery-ui-timepicker', $path . '/js/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker') );

        wp_enqueue_script( 'wpuf-upload', $path . '/js/upload.js', array('jquery', 'plupload-handlers') );
        wp_localize_script( 'wpuf-upload', 'wpuf_frontend_upload', array(
            'confirmMsg' => __( 'Are you sure?', 'wpuf' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wpuf_nonce' ),
            'plupload' => array(
                'url' => admin_url( 'admin-ajax.php' ) . '?nonce=' . wp_create_nonce( 'wpuf_featured_img' ),
                'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
                'filters' => array(array('title' => __( 'Allowed Files' ), 'extensions' => '*')),
                'multipart' => true,
                'urlstream_upload' => true,
            )
        ) );
    }

    function add_meta_boxes() {
        $post_types = get_post_types( array('public' => true) );
        foreach ($post_types as $post_type) {
            add_meta_box( 'wpuf-custom-fields', __( 'WPUF Custom Fields', 'wpuf' ), array($this, 'render_form'), $post_type, 'normal', 'high' );
        }
    }

    function hide_form() {
        ?>
        <style type="text/css">
            #wpuf-custom-fields { display: none; }
        </style>
        <?php
    }

    function render_form() {
        global $post;

        $form_id = get_post_meta( $post->ID, '_wpuf_form_id', true );

        // hide the metabox itself if no form ID is set
        if ( !$form_id ) {
            $this->hide_form();
            return;
        }

        $add = plugins_url( 'images/add.png', dirname( __FILE__ ) );
        $remove = plugins_url( 'images/remove.png', dirname( __FILE__ ) );
        $custom_fields = array();

        $form_vars = get_post_meta( $form_id, 'wpuf_form', true );
        foreach ($form_vars as $var) {
            if ( $var['is_meta'] == 'yes' ) {
                $custom_fields[] = $var;
            }
        }

        if ( empty( $custom_fields ) ) {
            _e( 'No custom field found.', 'wpuf' );
        }
        ?>

        <input type="hidden" name="wpuf_cf_update" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />
        <input type="hidden" name="wpuf_cf_form_id" value="<?php echo $form_id; ?>" />

        <table class="form-table wpuf-cf-table">
            <tbody>
                <?php foreach ($custom_fields as $attr) { ?>
                    <tr valign="top">
                        <th><?php echo $attr['label']; ?></th>
                        <td>
                            <?php
                            $value = get_post_meta( $post->ID, $attr['name'], true );

                            switch ($attr['input_type']) {
                                case 'text':
                                case 'url':
                                case 'email':
                                    printf( '<input type="text" name="%s" value="%s" size="40">', $attr['name'], esc_attr( $value ) );

                                    break;

                                case 'textarea':
                                    if ( $attr['rich'] == 'yes' ) {
                                        wp_editor( $value, $attr['name'], array('editor_height' => $attr['rows'], 'quicktags' => false, 'media_buttons' => false, 'editor_class' => $req_class) );
                                    } else {
                                        printf( '<textarea rows="3" cols="50" name="%s">%s</textarea>', $attr['name'], esc_textarea( $value ) );
                                    }
                                    break;

                                case 'radio':
                                    if ( $attr['options'] && count( $attr['options'] ) > 0 ) {
                                        foreach ($attr['options'] as $option) {
                                            ?>

                                            <label>
                                                <input name="<?php echo $attr['name']; ?>" type="radio" value="<?php echo esc_attr( $option ); ?>"<?php checked( $value, $option ); ?> />
                                                <?php echo $option; ?>
                                            </label>
                                            <?php
                                        }
                                    }
                                    break;

                                case 'checkbox':
                                    $value = explode( $this->separator, $value );

                                    if ( $attr['options'] && count( $attr['options'] ) > 0 ) {
                                        foreach ($attr['options'] as $option) {
                                            ?>

                                            <label>
                                                <input type="checkbox" name="<?php echo $attr['name']; ?>[]" value="<?php echo esc_attr( $option ); ?>"<?php echo in_array( $option, $value ) ? ' checked="checked"' : ''; ?> />
                                                <?php echo $option; ?>
                                            </label>
                                            <?php
                                        }
                                    }
                                    break;

                                case 'select':

                                    if ( empty( $value ) ) {
                                        $selected = isset( $attr['selected'] ) ? $attr['selected'] : '';
                                    } else {
                                        $selected = $value;
                                    }

                                    ?>
                                    <select name="<?php echo $attr['name'] ?>"<?php echo $multi; ?>>
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
                                    <?php
                                    break;

                                case 'multiselect':

                                    if ( empty( $value ) ) {
                                        $selected = isset( $attr['selected'] ) ? $attr['selected'] : array();
                                    } else {
                                        $selected = explode( $this->separator, $value );
                                    }

                                    ?>
                                    <select name="<?php echo $attr['name'] ?>[]" multiple="multiple">
                                        <?php
                                        if ( $attr['options'] && count( $attr['options'] ) > 0 ) {
                                            foreach ($attr['options'] as $option) {
                                                ?>
                                                <option value="<?php echo esc_attr( $option ); ?>"<?php selected( in_array( $option, $selected) ); ?>><?php echo $option; ?></option>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </select>
                                    <?php
                                    break;

                                case 'repeat':

                                    if ( isset( $attr['multiple'] ) ) {
                                        ?>
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
                                                $items = get_post_meta( $post->ID, $attr['name'] );
                                                // var_dump($items);

                                                if ( $items ) {
                                                    foreach ($items as $item_val) {
                                                        $column_vals = explode( $this->separator, $item_val );
                                                        ?>

                                                        <tr>
                                                            <?php for ($count = 0; $count < $num_columns; $count++) { ?>
                                                                <td>
                                                                    <input type="text" name="<?php echo $attr['name'] . '[' . $count . ']'; ?>[]" value="<?php echo esc_attr( $column_vals[$count] ); ?>" size="25" />
                                                                </td>
                                                            <?php } ?>
                                                            <td>
                                                                <img class="wpuf-clone-field" alt="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" src="<?php echo $add; ?>">
                                                                <img class="wpuf-remove-field" alt="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" src="<?php echo $remove; ?>">
                                                            </td>
                                                        </tr>

                                                    <?php } //endforeach   ?>

                                                <?php } else { ?>

                                                    <tr>
                                                        <?php for ($count = 0; $count < $num_columns; $count++) { ?>
                                                            <td>
                                                                <input type="text" name="<?php echo $attr['name'] . '[' . $count . ']'; ?>[]" size="25"  />
                                                            </td>
                                                        <?php } ?>
                                                        <td>
                                                            <img style="cursor:pointer;" class="wpuf-clone-field" alt="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Add another', 'wpuf' ); ?>" src="<?php echo $add; ?>">
                                                            <img style="cursor:pointer;" class="wpuf-remove-field" alt="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" title="<?php esc_attr_e( 'Remove this choice', 'wpuf' ); ?>" src="<?php echo $remove; ?>">
                                                        </td>
                                                    </tr>

                                                <?php } ?>

                                            </tbody>
                                        </table>

                                    <?php } else { ?>


                                        <table>
                                            <?php
                                            $items = explode( $this->separator, $value );

                                            if ( $items ) {
                                                foreach ($items as $item) {
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <input type="text" name="<?php echo esc_attr( $attr['name'] ); ?>[]" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $item ) ?>" />
                                                        </td>
                                                        <td>
                                                            <img style="cursor:pointer; margin:0 3px;" alt="add another choice" title="add another choice" class="wpuf-clone-field" src="<?php echo $add; ?>">
                                                            <img style="cursor:pointer;" class="wpuf-remove-field" alt="remove this choice" title="remove this choice" src="<?php echo $remove; ?>">
                                                        </td>
                                                    </tr>
                                                <?php } //endforeach  ?>
                                            <?php } else { ?>

                                                <tr>
                                                    <td>
                                                        <input type="text" name="<?php echo esc_attr( $attr['name'] ); ?>[]" placeholder="<?php echo esc_attr( $attr['placeholder'] ); ?>" value="<?php echo esc_attr( $attr['default'] ) ?>" size="40" />
                                                    </td>
                                                    <td>
                                                        <img style="cursor:pointer; margin:0 3px;" alt="add another choice" title="add another choice" class="wpuf-clone-field" src="<?php echo $add; ?>">
                                                        <img style="cursor:pointer;" class="wpuf-remove-field" alt="remove this choice" title="remove this choice" src="<?php echo $remove; ?>">
                                                    </td>
                                                </tr>

                                            <?php } ?>

                                        </table>
                                        <?php
                                    }
                                    break;

                                case 'date':

                                    printf( '<input id="wpuf-date-%s" type="text" name="%s" value="%s" size="40">', $attr['name'], $attr['name'], esc_attr( $value ) );
                                    ?>
                                    <script type="text/javascript">
                                        jQuery(function($) {
                                        <?php if ( $attr['time'] == 'yes' ) { ?>
                                            $("#wpuf-date-<?php echo $attr['name']; ?>").datetimepicker({ dateFormat: '<?php echo $attr["format"]; ?>' });
                                        <?php } else { ?>
                                            $("#wpuf-date-<?php echo $attr['name']; ?>").datepicker({ dateFormat: '<?php echo $attr["format"]; ?>' });
                                        <?php } ?>
                                        });
                                    </script>
                                    <?php
                                    break;

                                case 'map':
                                    list( $def_lat, $def_long ) = explode(',', $value );
                                    // printf( '<input type="text" name="%s" value="%s" size="40">', $attr['name'], esc_attr( $value ) );
                                    ?>
                                    <div class="wpuf-fields">
                                        <input id="wpuf-map-lat-<?php echo $attr['name']; ?>" type="hidden" name="<?php echo esc_attr( $attr['name'] ); ?>" value="<?php echo esc_attr( $value ) ?>" size="30" />

                                        <?php if ( $attr['address'] == 'yes' ) { ?>
                                            <input id="wpuf-map-add-<?php echo $attr['name']; ?>" type="text" value="" name="find-address" placeholder="<?php _e( 'Type an address to find', 'wpuf' ); ?>" size="30" />
                                            <button class="button" id="wpuf-map-btn-<?php echo $attr['name']; ?>"><?php _e( 'Find Address', 'wpuf' ); ?></button>
                                        <?php } ?>

                                        <div class="google-map" style="height: 250px; width: 450px;" id="wpuf-map-<?php echo $attr['name']; ?>"></div>
                                        <span class="wpuf-help"><?php echo $attr['help']; ?></span>
                                    </div>

                                    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
                                    <script type="text/javascript">

                                        (function($) {
                                            $(function() {
                                                var def_zoomval = <?php echo $attr['zoom']; ?>;
                                                var def_longval = <?php echo $def_long; ?>;
                                                var def_latval = <?php echo $def_lat; ?>;
                                                var curpoint = new google.maps.LatLng(def_latval, def_longval),
                                                    geocoder   = new window.google.maps.Geocoder(),
                                                    $map_area = $('#wpuf-map-<?php echo $attr['name']; ?>'),
                                                    $input_area = $( '#wpuf-map-lat-<?php echo $attr['name']; ?>' ),
                                                    $input_add = $( '#wpuf-map-add-<?php echo $attr['name']; ?>' ),
                                                    $find_btn = $( '#wpuf-map-btn-<?php echo $attr['name']; ?>' );

                                                $find_btn.on('click', function(e) {
                                                    e.preventDefault();

                                                    geocodeAddress( $input_add.val() );
                                                });

                                                var gmap = new google.maps.Map( $map_area[0], {
                                                    center: curpoint,
                                                    zoom: def_zoomval,
                                                    mapTypeId: window.google.maps.MapTypeId.ROADMAP
                                                });

                                                var marker = new window.google.maps.Marker({
                                                    position: curpoint,
                                                    map: gmap,
                                                    draggable: true
                                                });

                                                window.google.maps.event.addListener( gmap, 'click', function ( event ) {
                                                    marker.setPosition( event.latLng );
                                                    updatePositionInput( event.latLng );
                                                } );

                                                window.google.maps.event.addListener( marker, 'drag', function ( event ) {
                                                    updatePositionInput(event.latLng );
                                                } );

                                                function updatePositionInput( latLng ) {
                                                    $input_area.val( latLng.lat() + ',' + latLng.lng() );
                                                }

                                                function updatePositionMarker() {
                                                    var coord = $input_area.val(),
                                                        pos, zoom;

                                                    if ( coord ) {
                                                        pos = coord.split( ',' );
                                                        marker.setPosition( new window.google.maps.LatLng( pos[0], pos[1] ) );

                                                        zoom = pos.length > 2 ? parseInt( pos[2], 10 ) : 12;

                                                        gmap.setCenter( marker.position );
                                                        gmap.setZoom( zoom );
                                                    }
                                                }

                                                function geocodeAddress( address ) {
                                                    geocoder.geocode( {'address': address}, function ( results, status ) {
                                                        if ( status == window.google.maps.GeocoderStatus.OK ) {
                                                            updatePositionInput( results[0].geometry.location );
                                                            marker.setPosition( results[0].geometry.location );
                                                            gmap.setCenter( marker.position );
                                                            gmap.setZoom( 15 );
                                                        }
                                                    } );
                                                }

                                            });
                                        })(jQuery);
                                    </script>
                                    <?php
                                    break;

                                case 'file_upload':
                                case 'image_upload':

                                    $allowed_ext = '';
                                    $extensions = wpuf_allowed_extensions();
                                    if ( is_array( $attr['extension'] ) ) {
                                        foreach ($attr['extension'] as $ext) {
                                            $allowed_ext .= $extensions[$ext]['ext'] . ',';
                                        }
                                    } else {
                                        $allowed_ext = '*';
                                    }

                                    $uploaded_items = get_post_meta( $post->ID, $attr['name'] );
                                    ?>
                                    <div id="wpuf-<?php echo $attr['name']; ?>-upload-container">
                                        <div class="wpuf-attachment-upload-filelist">
                                            <a id="wpuf-<?php echo $attr['name']; ?>-pickfiles" class="button file-selector" href="#"><?php _e( 'Select File(s)', 'wpuf' ); ?></a>

                                            <?php printf( '<span class="wpuf-file-validation" data-required="%s" data-type="file"></span>', $attr['required'] ); ?>

                                            <ul class="wpuf-attachment-list thumbnails">
                                                <?php
                                                if ( $uploaded_items ) {
                                                    foreach ($uploaded_items as $attach_id) {
                                                        echo WPUF_Upload::attach_html( $attach_id, $attr['name'] );
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div><!-- .container -->
                                    <script type="text/javascript">
                                        jQuery(function($) {
                                            new WPUF_Uploader('wpuf-<?php echo $attr['name']; ?>-pickfiles', 'wpuf-<?php echo $attr['name']; ?>-upload-container', <?php echo $attr['count']; ?>, '<?php echo $attr['name']; ?>', '<?php echo $allowed_ext; ?>', <?php echo $attr['max_size'] ?>);
                                        });
                                    </script>
                                    <?php
                                    break;

                                default:
                                    break;
                            }
                            ?>

                            <div class="description"><?php echo $attr['help']; ?></div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <script type="text/javascript">
            jQuery(function($){


                var wpuf = {
                    init: function() {
                        $('#wpuf-custom-fields').on('click', 'img.wpuf-clone-field', this.cloneField);
                        $('#wpuf-custom-fields').on('click', 'img.wpuf-remove-field', this.removeField);
                    },
                    cloneField: function(e) {
                        e.preventDefault();

                        var $div = $(this).closest('tr');
                        var $clone = $div.clone();
                        // console.log($clone);

                        //clear the inputs
                        $clone.find('input').val('');
                        $clone.find(':checked').attr('checked', '');
                        $div.after($clone);
                    },

                    removeField: function() {
                        //check if it's the only item
                        var $parent = $(this).closest('tr');
                        var items = $parent.siblings().andSelf().length;

                        if( items > 1 ) {
                            $parent.remove();
                        }
                    }
                };

                wpuf.init();
            });

        </script>
        <style type="text/css">
        ul.wpuf-attachment-list li {
          display: inline-block;
          border: 1px solid #dfdfdf;
          padding: 5px;
          -webkit-border-radius: 5px;
          -moz-border-radius: 5px;
          border-radius: 5px;
          margin-right: 5px;
        }
        ul.wpuf-attachment-list li a.attachment-delete {
          text-decoration: none;
          padding: 3px 12px;
          border: 1px solid #C47272;
          color: #ffffff;
          text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
          -webkit-border-radius: 3px;
          -moz-border-radius: 3px;
          border-radius: 3px;
          background-color: #da4f49;
          background-image: -moz-linear-gradient(top, #ee5f5b, #bd362f);
          background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ee5f5b), to(#bd362f));
          background-image: -webkit-linear-gradient(top, #ee5f5b, #bd362f);
          background-image: -o-linear-gradient(top, #ee5f5b, #bd362f);
          background-image: linear-gradient(to bottom, #ee5f5b, #bd362f);
          background-repeat: repeat-x;
          filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffee5f5b', endColorstr='#ffbd362f', GradientType=0);
          border-color: #bd362f #bd362f #802420;
          border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
          *background-color: #bd362f;
          filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
        }
        ul.wpuf-attachment-list li a.attachment-delete:hover,
        ul.wpuf-attachment-list li a.attachment-delete:active {
          color: #ffffff;
          background-color: #bd362f;
          *background-color: #a9302a;
        }

        .wpuf-cf-table table th,
        .wpuf-cf-table table td{
            padding-left: 0 !important;
        }

        </style>
        <?php
    }

    // Save the Metabox Data
    function save_meta( $post_id, $post ) {
        if ( !isset( $_POST['wpuf_cf_update'] ) ) {
            return $post->ID;
        }

        if ( !wp_verify_nonce( $_POST['wpuf_cf_update'], plugin_basename( __FILE__ ) ) ) {
            return $post->ID;
        }

        // Is the user allowed to edit the post or page?
        if ( !current_user_can( 'edit_post', $post->ID ) )
            return $post->ID;

        $form_vars = get_post_meta( $_POST['wpuf_cf_form_id'], 'wpuf_form', true );

        $files = array();
        $meta_key_value = array();
        $multi_repeated = array(); //multi repeated fields will in sotre duplicated meta key

        foreach ($form_vars as $key => $value) {

            if ( isset( $value['is_meta'] ) && $value['is_meta'] == 'yes' ) {
                // var_dump($value);

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
                            $first = array_shift( array_values( $_POST[$value['name']] ) ); //first element
                            $rows = count( $first );

                            // loop through columns
                            for ($i = 0; $i < $rows; $i++) {

                                // loop through the rows and store in a temp array
                                $temp = array();
                                for ($j = 0; $j < $cols; $j++) {

                                    $temp[] = $_POST[$value['name']][$j][$i];
                                }

                                // store all fields in a row with $this->separator separated
                                $ref_arr[] = implode( $this->separator, $temp );
                            }

                            // now, if we found anything in $ref_arr, store to $multi_repeated
                            if ( $ref_arr ) {
                                $multi_repeated[$value['name']] = array_slice( $ref_arr, 0, $rows );
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

            } //end if meta
        } //end foreach

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
}