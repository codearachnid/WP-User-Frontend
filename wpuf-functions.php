<?php
/**
 * Start output buffering
 *
 * This is needed for redirecting to post when a new post has made
 *
 * @since 0.8
 */
function wpuf_buffer_start() {
    ob_start();
}

add_action( 'init', 'wpuf_buffer_start' );

/**
 * Format the post status for user dashboard
 *
 * @param string $status
 * @since version 0.1
 * @author Tareq Hasan
 */
function wpuf_show_post_status( $status ) {

    if ( $status == 'publish' ) {

        $title = __( 'Live', 'wpuf' );
        $fontcolor = '#33CC33';
    } else if ( $status == 'draft' ) {

        $title = __( 'Offline', 'wpuf' );
        $fontcolor = '#bbbbbb';
    } else if ( $status == 'pending' ) {

        $title = __( 'Awaiting Approval', 'wpuf' );
        $fontcolor = '#C00202';
    } else if ( $status == 'future' ) {
        $title = __( 'Scheduled', 'wpuf' );
        $fontcolor = '#bbbbbb';
    }

    echo '<span style="color:' . $fontcolor . ';">' . $title . '</span>';
}

/**
 * Format error message
 *
 * @param array $error_msg
 * @return string
 */
function wpuf_error_msg( $error_msg ) {
    $msg_string = '';
    foreach ($error_msg as $value) {
        if ( !empty( $value ) ) {
            $msg_string = $msg_string . '<div class="error">' . $msg_string = $value . '</div>';
        }
    }
    return $msg_string;
}

/**
 * Notify the admin for new post
 *
 * @param object $userdata
 * @param int $post_id
 */
function wpuf_notify_post_mail( $user, $post_id ) {
    $blogname = get_bloginfo( 'name' );
    $to = get_bloginfo( 'admin_email' );
    $permalink = get_permalink( $post_id );

    $headers = sprintf( "From: %s <%s>\r\n", $blogname, $to );
    $subject = sprintf( __( '[%s] New Post Submission' ), $blogname );

    $msg = sprintf( __( 'A new post has been submitted on %s' ), $blogname ) . "\r\n\r\n";
    $msg .= sprintf( __( 'Author : %s' ), $user->display_name ) . "\r\n";
    $msg .= sprintf( __( 'Author Email : %s' ), $user->user_email ) . "\r\n";
    $msg .= sprintf( __( 'Title : %s' ), get_the_title( $post_id ) ) . "\r\n";
    $msg .= sprintf( __( 'Permalink : %s' ), $permalink ) . "\r\n";
    $msg .= sprintf( __( 'Edit Link : %s' ), admin_url( 'post.php?action=edit&post=' . $post_id ) ) . "\r\n";

    //plugin api
    $to = apply_filters( 'wpuf_notify_to', $to );
    $subject = apply_filters( 'wpuf_notify_subject', $subject );
    $msg = apply_filters( 'wpuf_notify_message', $msg );

    wp_mail( $to, $subject, $msg, $headers );
}

/**
 * Upload the files to the post as attachemnt
 *
 * @param <type> $post_id
 */
function wpuf_upload_attachment( $post_id ) {
    if ( !isset( $_FILES['wpuf_post_attachments'] ) ) {
        return false;
    }

    $fields = (int) wpuf_get_option( 'attachment_num' );

    for ($i = 0; $i < $fields; $i++) {
        $file_name = basename( $_FILES['wpuf_post_attachments']['name'][$i] );

        if ( $file_name ) {
            if ( $file_name ) {
                $upload = array(
                    'name' => $_FILES['wpuf_post_attachments']['name'][$i],
                    'type' => $_FILES['wpuf_post_attachments']['type'][$i],
                    'tmp_name' => $_FILES['wpuf_post_attachments']['tmp_name'][$i],
                    'error' => $_FILES['wpuf_post_attachments']['error'][$i],
                    'size' => $_FILES['wpuf_post_attachments']['size'][$i]
                );

                wpuf_upload_file( $upload );
            }//file exists
        }// end for
    }
}


/**
 * Get the attachments of a post
 *
 * @param int $post_id
 * @return array attachment list
 */
function wpfu_get_attachments( $post_id ) {
    $att_list = array();

    $args = array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_status' => null,
        'post_parent' => $post_id,
        'order' => 'ASC',
        'orderby' => 'menu_order'
    );

    $attachments = get_posts( $args );

    foreach ($attachments as $attachment) {
        $att_list[] = array(
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'url' => wp_get_attachment_url( $attachment->ID ),
            'mime' => $attachment->post_mime_type
        );
    }

    return $att_list;
}

/**
 * Remove the mdedia upload tabs from subscribers
 *
 * @package WP User Frontend
 * @author Tareq Hasan
 */
function wpuf_unset_media_tab( $list ) {
    if ( !current_user_can( 'edit_posts' ) ) {
        unset( $list['library'] );
        unset( $list['gallery'] );
    }

    return $list;
}

add_filter( 'media_upload_tabs', 'wpuf_unset_media_tab' );

/**
 * Get the registered post types
 *
 * @return array
 */
function wpuf_get_post_types() {
    $post_types = get_post_types();

    foreach ($post_types as $key => $val) {
        if ( $val == 'attachment' || $val == 'revision' || $val == 'nav_menu_item' ) {
            unset( $post_types[$key] );
        }
    }

    return $post_types;
}

/**
 * Get lists of users from database
 *
 * @return array
 */
function wpuf_list_users() {
    if ( function_exists( 'get_users' ) ) {
        $users = get_users();
    } else {
        ////wp 3.1 fallback
        $users = get_users_of_blog();
    }

    $list = array();

    if ( $users ) {
        foreach ($users as $user) {
            $list[$user->ID] = $user->display_name;
        }
    }

    return $list;
}

/**
 * Retrieve or display list of posts as a dropdown (select list).
 *
 * @return string HTML content, if not displaying.
 */
function wpuf_get_pages( $post_type = 'page' ) {
    global $wpdb;

    $array = array();
    $pages = get_pages( array( 'post_type' => $post_type ) );
    if ( $pages ) {
        foreach ($pages as $page) {
            $array[$page->ID] = $page->post_title;
        }
    }

    return $array;
}

/**
 * Get all the payment gateways
 *
 * @return array
 */
function wpuf_get_gateways( $context = 'admin' ) {
//    $gateways = WPUF_Payment::get_payment_gateways();
    $return = array();

//    foreach ($gateways as $id => $gate) {
//        if ( $context == 'admin' ) {
//            $return[$id] = $gate['admin_label'];
//        } else {
//            $return[$id] = $gate['checkout_label'];
//        }
//    }

    return $return;
}

/**
 * Edit post link for frontend
 *
 * @since 0.7
 * @param string $url url of the original post edit link
 * @param int $post_id
 * @return string url of the current edit post page
 */
function wpuf_edit_post_link( $url, $post_id ) {
    if ( is_admin() ) {
        return $url;
    }

    $override = wpuf_get_option( 'override_editlink', 'yes' );
    if ( $override == 'yes' ) {
        $url = '';
        if ( wpuf_get_option( 'enable_post_edit' ) == 'yes' ) {
            $edit_page = (int) wpuf_get_option( 'edit_page_id' );
            $url = get_permalink( $edit_page );

            $url = wp_nonce_url( $url . '?pid=' . $post_id, 'wpuf_edit' );
        }
    }

    return $url;
}

add_filter( 'get_edit_post_link', 'wpuf_edit_post_link', 10, 2 );

/**
 * Create HTML dropdown list of Categories.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class WPUF_Walker_Category_Multi extends Walker {

    /**
     * @see Walker::$tree_type
     * @var string
     */
    var $tree_type = 'category';

    /**
     * @see Walker::$db_fields
     * @var array
     */
    var $db_fields = array('parent' => 'parent', 'id' => 'term_id');

    /**
     * @see Walker::start_el()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $category Category data object.
     * @param int $depth Depth of category. Used for padding.
     * @param array $args Uses 'selected' and 'show_count' keys, if they exist.
     */
    function start_el( &$output, $category, $depth, $args, $id = 0 ) {
        $pad = str_repeat( '&nbsp;', $depth * 3 );

        $cat_name = apply_filters( 'list_cats', $category->name, $category );
        $output .= "\t<option class=\"level-$depth\" value=\"" . $category->term_id . "\"";
        if ( in_array($category->term_id, $args['selected'] ) )
            $output .= ' selected="selected"';
        $output .= '>';
        $output .= $pad . $cat_name;
        if ( $args['show_count'] )
            $output .= '&nbsp;&nbsp;(' . $category->count . ')';
        $output .= "</option>\n";
    }

}

/**
 * Category checklist walker
 *
 * @since 0.8
 */
class WPUF_Walker_Category_Checklist extends Walker {

    var $tree_type = 'category';
    var $db_fields = array('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function start_lvl( &$output, $depth, $args ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent<ul class='children'>\n";
    }

    function end_lvl( &$output, $depth, $args ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent</ul>\n";
    }

    function start_el( &$output, $category, $depth, $args ) {
        extract( $args );
        if ( empty( $taxonomy ) )
            $taxonomy = 'category';

        if ( $taxonomy == 'category' )
            $name = 'category';
        else
            $name = $taxonomy;

        $output .= "\n<li id='{$taxonomy}-{$category->term_id}'>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . $name . '[]" id="in-' . $taxonomy . '-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
    }

    function end_el( &$output, $category, $depth, $args ) {
        $output .= "</li>\n";
    }

}

/**
 * Displays checklist of a taxonomy
 *
 * @since 0.8
 * @param int $post_id
 * @param array $selected_cats
 */
function wpuf_category_checklist( $post_id = 0, $selected_cats = false, $tax = 'category', $exclude = false ) {
    require_once ABSPATH . '/wp-admin/includes/template.php';

    $walker = new WPUF_Walker_Category_Checklist();

    $args = array(
        'taxonomy' => $tax,
    );

    if ( $post_id ) {
        $args['selected_cats'] = wp_get_object_terms( $post_id, $tax, array('fields' => 'ids') );
    } elseif ($selected_cats ) {
        $args['selected_cats'] = $selected_cats;
    } else {
        $args['selected_cats'] = array();
    }

    $categories = (array) get_terms($tax, array('get' => 'all', 'exclude' => $exclude));

    echo '<ul class="wpuf-category-checklist">';
    echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
    echo '</ul>';
}

// display msg if permalinks aren't setup correctly
function wpuf_permalink_nag() {

    if ( current_user_can( 'manage_options' ) )
        $msg = sprintf( __( 'You need to set your <a href="%1$s">permalink custom structure</a> to at least contain <b>/&#37;postname&#37;/</b> before WP User Frontend will work properly.', 'wpuf' ), 'options-permalink.php' );

    echo "<div class='error fade'><p>$msg</p></div>";
}

//if not found %postname%, shows a error msg at admin panel
if ( !stristr( get_option( 'permalink_structure' ), '%postname%' ) ) {
    add_action( 'admin_notices', 'wpuf_permalink_nag', 3 );
}

/**
 * Get all the image sizes
 *
 * @return array image sizes
 */
function wpuf_get_image_sizes() {
    $image_sizes_orig = get_intermediate_image_sizes();
    $image_sizes_orig[] = 'full';
    $image_sizes = array();

    foreach ($image_sizes_orig as $size) {
        $image_sizes[$size] = $size;
    }

    return $image_sizes;
}


function wpuf_allowed_extensions() {
    $extesions = array(
        'images' => array( 'ext' => 'jpg,jpeg,gif,png,bmp', 'label' => __( 'Images', 'wpuf' ) ),
        'audio' => array( 'ext' => 'mp3,wav,ogg,wma,mka,m4a,ra,mid,midi', 'label' => __( 'Audio', 'wpuf' ) ),
        'video' => array( 'ext' => 'avi,divx,flv,mov,ogv,mkv,mp4,m4v,divx,mpg,mpeg,mpe', 'label' => __( 'Videos', 'wpuf' ) ),
        'pdf' => array( 'ext' => 'pdf', 'label' => __( 'PDF', 'wpuf' ) ),
        'office' => array( 'ext' => 'doc,ppt,pps,xls,mdb,docx,xlsx,pptx,odt,odp,ods,odg,odc,odb,odf,rtf,txt', 'label' => __( 'Office Documents', 'wpuf' ) ),
        'zip' => array( 'ext' => 'zip,gz,gzip,rar,7z', 'label' => __( 'Zip Archives' ) ),
        'exe' => array( 'ext' => 'exe', 'label' => __( 'Executable Files', 'wpuf' ) ),
        'csv' => array( 'ext' => 'csv', 'label' => __( 'CSV', 'wpuf') )
    );

    return apply_filters( 'wpuf_allowed_extensions', $extesions );
}


/**
 * Associate attachemnt to a post
 *
 * @since 2.0
 *
 * @param type $attachment_id
 * @param type $post_id
 */
function wpuf_associate_attachment( $attachment_id, $post_id ) {
    wp_update_post( array(
        'ID' => $attachment_id,
        'post_parent' => $post_id
    ) );
}

/**
 * Get user role names
 *
 * @global WP_Roles $wp_roles
 * @return array
 */
function wpuf_get_user_roles() {
    global $wp_roles;

    if ( !isset( $wp_roles ) )
        $wp_roles = new WP_Roles();

    return $wp_roles->get_names();
}

/**
 * User avatar wrapper for custom uploaded avatar
 *
 * @param string $avatar
 * @param mixed $id_or_email
 * @param int $size
 * @param string $default
 * @param string $alt
 * @return string image tag of the user avatar
 */
function wpuf_get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

    if ( is_numeric( $id_or_email ) ) {
        $user = get_user_by( 'id', $id_or_email );
    } elseif ( is_object( $id_or_email ) ) {
        if ( $id_or_email->user_id != '0' ) {
            $user = get_user_by( 'id', $id_or_email->user_id );
        } else {
            return $avatar;
        }
    } else {
        $user = get_user_by( 'email', $id_or_email );
    }

    // see if there is a user_avatar meta field
    $user_avatar = get_user_meta( $user->ID, 'user_avatar', true );
    if ( empty( $user_avatar ) ) {
        return $avatar;
    }

    // hmm, we found something
    $upload_dir = wp_upload_dir();
    $image_src = $upload_dir['baseurl'] . $user_avatar;

    return sprintf('<img src="%1$s" alt="%2$s" height="%3$s" width="%3$s" class="avatar"', esc_url( $image_src ), $alt, $size);
}

add_filter( 'get_avatar', 'wpuf_get_avatar', 99, 5 );