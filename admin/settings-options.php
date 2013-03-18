<?php

/**
 * Get the value of a settings field
 *
 * @param string $option option field name
 * @return mixed
 */
function wpuf_get_option( $option ) {

    $fields = wpuf_settings_fields();
    $prepared_fields = array();

    //prepare the array with the field as key
    //and set the section name on each field
    foreach ($fields as $section => $field) {
        foreach ($field as $fld) {
            $prepared_fields[$fld['name']] = $fld;
            $prepared_fields[$fld['name']]['section'] = $section;
        }
    }

    //get the value of the section where the option exists
    $opt = get_option( $prepared_fields[$option]['section'] );
    $opt = is_array( $opt ) ? $opt : array();

    //return the value if found, otherwise default
    if ( array_key_exists( $option, $opt ) ) {
        return $opt[$option];
    } else {
        $val = isset( $prepared_fields[$option]['default'] ) ? $prepared_fields[$option]['default'] : '';
        return $val;
    }
}

/**
 * Settings Sections
 *
 * @since 1.0
 * @return array
 */
function wpuf_settings_sections() {
    $sections = array(
        array(
            'id' => 'wpuf_general',
            'title' => __( 'General Options', 'wpuf' )
        ),
        array(
            'id' => 'wpuf_dashboard',
            'title' => __( 'Dashboard', 'wpuf' )
        ),
        array(
            'id' => 'wpuf_profile',
            'title' => __( 'Profile', 'wpuf' )
        ),
        array(
            'id' => 'wpuf_support',
            'title' => __( 'Support', 'wpuf' )
        ),
    );

    return apply_filters( 'wpuf_settings_sections', $sections );
}

function wpuf_settings_fields() {
    $settings_fields = array(
        'wpuf_general' => apply_filters( 'wpuf_options_others', array(
            array(
                'name' => 'fixed_form_element',
                'label' => __( 'Fixed Form Elements ', 'wpuf' ),
                'desc' => __( 'Show fixed form elements sidebar in form editor', 'wpuf' ),
                'type' => 'checkbox',
                'default' => 'on'
            ),
            array(
                'name' => 'enable_post_edit',
                'label' => __( 'Users can edit post?', 'wpuf' ),
                'desc' => __( 'Users will be able to edit their own posts', 'wpuf' ),
                'type' => 'select',
                'default' => 'yes',
                'options' => array(
                    'yes' => __( 'Yes', 'wpuf' ),
                    'no' => __( 'No', 'wpuf' )
                )
            ),
            array(
                'name' => 'enable_post_del',
                'label' => __( 'User can delete post?', 'wpuf' ),
                'desc' => __( 'Users will be able to delete their own posts', 'wpuf' ),
                'type' => 'select',
                'default' => 'yes',
                'options' => array(
                    'yes' => __( 'Yes', 'wpuf' ),
                    'no' => __( 'No', 'wpuf' )
                )
            ),
            array(
                'name' => 'edit_page_id',
                'label' => __( 'Edit Page', 'wpuf' ),
                'desc' => __( 'Select the page where [wpuf_edit] is located', 'wpuf' ),
                'type' => 'select',
                'options' => wpuf_get_pages()
            ),
            array(
                'name' => 'default_post_owner',
                'label' => __( 'Default Post Owner', 'wpuf' ),
                'desc' => __( 'If guest post is enabled and user details are OFF, the posts are assigned to this user', 'wpuf' ),
                'type' => 'select',
                'options' => wpuf_list_users(),
                'default' => '1'
            ),
            array(
                'name' => 'admin_access',
                'label' => __( 'Admin area access', 'wpuf' ),
                'desc' => __( 'Allow you to block specific user role to WordPress admin area.', 'wpuf' ),
                'type' => 'select',
                'default' => 'read',
                'options' => array(
                    'install_themes' => __( 'Admin Only', 'wpuf' ),
                    'edit_others_posts' => __( 'Admins, Editors', 'wpuf' ),
                    'publish_posts' => __( 'Admins, Editors, Authors', 'wpuf' ),
                    'edit_posts' => __( 'Admins, Editors, Authors, Contributors', 'wpuf' ),
                    'read' => __( 'Default', 'wpuf' )
                )
            ),
            array(
                'name' => 'override_editlink',
                'label' => __( 'Override the post edit link', 'wpuf' ),
                'desc' => __( 'Users see the edit link in post if s/he is capable to edit the post/page. Selecting <strong>Yes</strong> will override the default WordPress edit post link in frontend', 'wpuf' ),
                'type' => 'select',
                'default' => 'yes',
                'options' => array(
                    'yes' => __( 'Yes', 'wpuf' ),
                    'no' => __( 'No', 'wpuf' )
                )
            ),
            array(
                'name' => 'recaptcha_public',
                'label' => __( 'reCAPTCHA Public Key', 'wpuf' ),
            ),
            array(
                'name' => 'recaptcha_private',
                'label' => __( 'reCAPTCHA Private Key', 'wpuf' ),
            ),
            array(
                'name' => 'custom_css',
                'label' => __( 'Custom CSS codes', 'wpuf' ),
                'desc' => __( 'If you want to add your custom CSS code, it will be added on page header wrapped with style tag', 'wpuf' ),
                'type' => 'textarea'
            ),
        ) ),
        'wpuf_dashboard' => apply_filters( 'wpuf_options_dashboard', array(
            array(
                'name' => 'per_page',
                'label' => __( 'Posts per page', 'wpuf' ),
                'desc' => __( 'How many posts will be listed in a page', 'wpuf' ),
                'type' => 'text',
                'default' => '10'
            ),
            array(
                'name' => 'show_user_bio',
                'label' => __( 'Show user bio', 'wpuf' ),
                'desc' => __( 'Users biographical info will be shown', 'wpuf' ),
                'type' => 'checkbox',
                'default' => 'on'
            ),
            array(
                'name' => 'show_post_count',
                'label' => __( 'Show post count', 'wpuf' ),
                'desc' => __( 'Show how many posts are created by the user', 'wpuf' ),
                'type' => 'checkbox',
                'default' => 'on'
            ),
            array(
                'name' => 'show_ft_image',
                'label' => __( 'Show Featured Image', 'wpuf' ),
                'desc' => __( 'Show featured image of the post', 'wpuf' ),
                'type' => 'checkbox'
            ),
            array(
                'name' => 'ft_img_size',
                'label' => __( 'Featured Image size', 'wpuf' ),
                'type' => 'select',
                'options' => wpuf_get_image_sizes()
            ),
        ) ),
        'wpuf_profile' => array(
            array(
                'name' => 'register_link_override',
                'label' => __( 'Registration link override', 'wpuf' ),
                'desc' => __( 'Override registration link. <span class="description">(check if you have any custom registration page. Compatible with <strong>Theme My Login</strong>. Changes the registration url on Meta widget and theme my login plugin.)</span>', 'wpuf' ),
                'type' => 'checkbox',
                'default' => 'off'
            ),
            array(
                'name' => 'reg_override_page',
                'label' => __( 'Registration Page', 'wpuf' ),
                'desc' => __( 'Select the page you want to use as registration page override <em>(should have shortcode)</em>', 'wpuf' ),
                'type' => 'select',
                'options' => wpuf_get_pages()
            ),
        ),
        'wpuf_support' => apply_filters( 'wpuf_options_support', array(
            array(
                'name' => 'support',
                'label' => __( 'Need Help?', 'wpuf' ),
                'type' => 'html',
                'desc' => '
                        <ol>
                            <li>
                                <strong>Check the FAQ and the documentation</strong>
                                <p>First of all, check the <strong><a href="http://wordpress.org/extend/plugins/wp-user-frontend/faq/">FAQ</a></strong> before contacting! Most of the questions you might need answers to have already been asked and the answers are in the FAQ. Checking the FAQ is the easiest and quickest way to solve your problem.</p>
                            </li>
                            <li>
                                <strong>Use the Support Forum</strong>
                                <p>If you were unable to find the answer to your question on the FAQ page, you should check the <strong><a href="http://wordpress.org/tags/wp-user-frontend?forum_id=10">support forum on WordPress.org</a></strong>. If you can’t locate any topics that pertain to your particular issue, post a new topic for it.</p>
                                <p>But, remember that this is a free support forum and no one is obligated to help you. Every person who offers information to help you is a volunteer, so be polite. And, I would suggest that you read the <a href="http://wordpress.org/support/topic/68664">“Forum Rules”</a> before posting anything on this page.</p>
                            </li>
                            <li>
                                <strong>Got an idea?</strong>
                                <p>I would love to hear about your ideas and suggestions about the plugin. Please post them on the <strong><a href="http://wordpress.org/tags/wp-user-frontend?forum_id=10">support forum on WordPress.org</a></strong> and I will look into it</p>
                            </li>
                            <li>
                                <strong>Gettings no response?</strong>
                                <p>I try to answer all the question in the forum. I created the plugin without any charge and I am usually very busy with my other works. As this is a free plugin, I am not bound answer all of your questions.</p>
                            </li>
                            <li>
                                I spent countless hours to build this plugin, <strong><a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=tareq%40wedevs%2ecom&lc=US&item_name=WP%20User%20Frontend&item_number=Tareq%27s%20Planet&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted">support</a></strong> me if you like this plugin and <a href="http://wordpress.org/extend/plugins/wp-user-frontend/">rate</a> the plugin.
                            </li>
                        </ol>'
            )
        ) ),
    );

    return apply_filters( 'wpuf_settings_fields', $settings_fields );
}

function wpuf_settings_field_profile( $form ) {
    $user_roles = wpuf_get_user_roles();
    $forms = get_posts(  array(
        'numberposts' => -1,
        'post_type' => 'wpuf_profile'
    ) );

    $val = get_option( 'wpuf_profile', array() );
    ?>

    <p style="padding-left: 10px; font-style: italic; font-size: 13px;">
        <strong><?php _e( 'Select profile/registration forms for user roles. These forms will be used to populate extra edit profile fields in backend.', 'wpuf' ); ?></strong>
    </p>
    <table class="form-table">
        <?php
        foreach ($user_roles as $role => $name ) {
            $current = isset( $val['roles'][$role] ) ? $val['roles'][$role] : '';
            ?>
            <tr valign="top">
                <th scrope="row"><?php echo $name; ?></th>
                <td>
                    <select name="wpuf_profile[roles][<?php echo $role; ?>]">
                        <option value=""><?php _e( ' - select - ', 'wpuf' ); ?></option>
                        <?php foreach ( $forms as $form ) { ?>
                            <option value="<?php echo $form->ID; ?>"<?php selected( $current, $form->ID ); ?>><?php echo $form->post_title; ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
}

add_action( 'wsa_form_bottom_wpuf_profile', 'wpuf_settings_field_profile' );