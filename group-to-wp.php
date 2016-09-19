<?php
/*
Plugin Name: Facebook Group to WordPress importer
Plugin URI: http://tareq.wedevs.com/
Description: Import facebook group posts into WordPress
Version: 1.0
Author: Tareq Hasan
Author URI: https://tareq.co/
License: GPL2
*/

/**
 * Copyright (c) 2014 Tareq Hasan (email: tareq@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/includes/admin.php';
}

// WeDevs_FB_Group_To_WP::init()->trash_all();

/**
 * WeDevs_FB_Group_To_WP class
 *
 * @class WeDevs_FB_Group_To_WP The class that holds the entire WeDevs_FB_Group_To_WP plugin
 */
class WeDevs_FB_Group_To_WP {

    private $post_type = 'fb_group_post';

    /**
     * Constructor for the WeDevs_FB_Group_To_WP class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
        add_action( 'init', array( $this, 'register_post_type' ) );

        add_action( 'init', array( $this, 'debug_run' ) );
        add_action( 'init', array( $this, 'historical_import' ) );
        add_action( 'fbgr2wp_import', array( $this, 'do_import' ) );

        add_filter( 'cron_schedules', array($this, 'cron_schedules') );

        add_filter( 'get_avatar_comment_types', array( $this, 'avatar_comment_type' ) );
        add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 3 );

        add_filter( 'the_content', array( $this, 'the_content' ) );

        if ( is_admin() ) {
            new WeDevs_FB_Group_To_WP_Admin();
        }
    }

    /**
     * Registers our custom post type
     *
     * @return void
     */
    public function register_post_type() {
        $labels = array(
            'name'                => _x( 'Group Posts', 'Post Type General Name', 'fbgr2wp' ),
            'singular_name'       => _x( 'Group Post', 'Post Type Singular Name', 'fbgr2wp' ),
            'menu_name'           => __( 'FB Group Posts', 'fbgr2wp' ),
            'parent_item_colon'   => __( 'Parent Post:', 'fbgr2wp' ),
            'all_items'           => __( 'All Posts', 'fbgr2wp' ),
            'view_item'           => __( 'View Post', 'fbgr2wp' ),
            'add_new_item'        => __( 'Add New Post', 'fbgr2wp' ),
            'add_new'             => __( 'Add New', 'fbgr2wp' ),
            'edit_item'           => __( 'Edit Post', 'fbgr2wp' ),
            'update_item'         => __( 'Update Post', 'fbgr2wp' ),
            'search_items'        => __( 'Search Post', 'fbgr2wp' ),
            'not_found'           => __( 'Not found', 'fbgr2wp' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'fbgr2wp' ),
        );

        $rewrite = array(
            'slug'                => 'fb-post',
            'with_front'          => true,
            'pages'               => true,
            'feeds'               => false,
        );

        $args = array(
            'label'               => __( 'fb_group_post', 'fbgr2wp' ),
            'description'         => __( 'WordPress Group Post', 'fbgr2wp' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'post-formats', 'thumbnail', 'comments' ),
            'taxonomies'          => array( 'category', 'post_tag' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'rewrite'             => $rewrite,
            'capability_type'     => 'post',
        );

        register_post_type( $this->post_type, $args );
    }

    /**
     * Initializes the WeDevs_FB_Group_To_WP() class
     *
     * Checks for an existing WeDevs_FB_Group_To_WP() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new WeDevs_FB_Group_To_WP();
        }

        return $instance;
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {
        if ( false == wp_next_scheduled( 'fbgr2wp_import' ) ){
            wp_schedule_event( time(), 'half-hour', 'fbgr2wp_import' );
        }
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {
        wp_clear_scheduled_hook( 'fbgr2wp_import' );
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'fbgr2wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Add new cron schedule
     *
     * @param  array $schedules
     * @return array
     */
    function cron_schedules( $schedules ) {
        $schedules['half-hour'] = array(
            'interval' => MINUTE_IN_SECONDS * 30,
            'display' => __( 'In every 30 Minutes', 'fbgr2wp' )
        );

        return $schedules;
    }

    /**
     * Manually trigger the cron
     *
     * @return void
     */
    function debug_run() {
        if ( !isset( $_GET['fb2wp_test'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->do_import();

        die();
    }

    /**
     * Get the facebook settings
     *
     * @return array
     */
    function get_settings() {
        $option = get_option( 'fbgr2wp_settings', array() );

        // return if no configuration found
        if ( !isset( $option['app_id'] ) || !isset( $option['app_secret'] ) || !isset( $option['group_id'] ) ) {
            return false;
        }

        // no app id or app secret
        if ( empty( $option['app_id'] ) || empty( $option['app_secret'] ) ) {
            return false;
        }

        // no group id
        if ( empty( $option['group_id'] ) ) {
            return false;
        }

        return $option;
    }

    /**
     * Do a historical or paginated import
     *
     * This is a clever approach to import all the posts from a group.
     * When you visit the url http://example.com/?fb2wp_hist, it'll start it's process.
     *
     * The plugin will start from the recent to next page without any interaction from
     * your end. It'll build the url and reload the page in every 5/10 seconds and impport
     * the next posts.
     *
     * As it doesn't do any blocking in the server, your server will not be overloaded
     * and any timeout wouldn't happen.
     *
     * @return void
     */
    function historical_import() {

        if ( ! isset( $_GET['fb2wp_hist'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $root_page    = add_query_arg( array( 'fb2wp_hist' => '' ), home_url() );
        $page_num     = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;


        $option       = $this->get_settings();
        $access_token = $option['app_id'] . '|' . $option['app_secret'];
        $group_id     = $option['group_id'];
        $limit        = isset( $option['limit'] ) ? intval( $option['limit'] ) : 30;

        $fb_url       = 'https://graph.facebook.com/' . $group_id . '/feed/?limit=' . $limit . '&access_token=' . $access_token;

        // build the query URL for next page
        if ( $page_num > 1 ) {
            $until        = isset( $_GET['until'] ) ? $_GET['until'] : '';
            $paging_token = isset( $_GET['paging_token'] ) ? $_GET['paging_token'] : '';

            $fb_url = add_query_arg( array(
                'until'          => $until,
                '__paging_token' => $paging_token
            ), $fb_url );
        }

        // do the import
        $json_posts  = $this->fetch_stream( $fb_url );
        $decoded     = json_decode( $json_posts );
        $group_posts = $decoded->data;

        $count       = $this->insert_posts( $group_posts, $group_id );

        // show debug info
        printf( '<strong>%d</strong> posts imported<br>', $count );
        printf( 'Showing Page: %d<br>', $page_num );
        printf( 'Per Page: %d<br>', $limit );
        printf( 'Group ID: %d<br>', $group_id );

        // Build the next page URL
        // Reload the page automatically after few seconds
        // and do it's thing without killing the server
        if ( $page_num && property_exists( $decoded, 'paging' ) ) {

            $paging = $decoded->paging;
            parse_str( $paging->next, $next_page );

            $next_page_url = add_query_arg( array(
                'page'         => ($page_num + 1),
                'until'        => $next_page['until'],
                'paging_token' => $next_page['__paging_token']
            ), $root_page );

            ?>
            <script type="text/javascript">
                setTimeout(function(){
                    window.location.href = '<?php echo $next_page_url; ?>';
                }, 5000);
            </script>
            <?php
        }

        exit;
    }

    /**
     * Do the actual import via cron
     *
     * @return boolean
     */
    function do_import() {
        $option = $this->get_settings();

        if ( !$option ) {
            return;
        }

        $api_version  = 'v2.7';
        $access_token = $option['app_id'] . '|' . $option['app_secret'];
        $group_id     = $option['group_id'];
        $limit        = isset( $option['limit'] ) ? intval( $option['limit'] ) : 30;
        $fields       = array( 'message', 'status_type', 'full_picture', 'type', 'permalink_url', 'id', 'from', 'updated_time', 'created_time', 'description', 'comments' );
        $url          = sprintf( 'https://graph.facebook.com/%s/%d/feed/?fields=%s&limit=%d&access_token=%s', $api_version, $group_id, implode( ',', $fields ), $limit, $access_token );

        $json_posts   = $this->fetch_stream( $url );

        if ( !$json_posts ) {
            return;
        }

        $decoded     = json_decode( $json_posts );
        $group_posts = $decoded->data;
        $paging      = $decoded->paging;

        // var_dump( $group_posts ); exit;

        $count       = $this->insert_posts( $group_posts, $group_id );

        printf( '%d posts imported', $count );
    }

    /**
     * Fetch group posts from facebook API
     *
     * @param  string $url
     * @return string
     */
    function fetch_stream( $url ) {
        self::log( 'debug', 'Fetching data from facebook' );

        $request = wp_remote_get( $url );
        $json_posts = wp_remote_retrieve_body( $request );

        if ( is_wp_error( $request ) ) {
            self::log( 'error', 'Fetching failed with code. WP_Error' );
            return;
        }

        if ( $request['response']['code'] != 200 ) {
            self::log( 'error', 'Fetching failed with code: ' . $request['response']['code'] );
            return false;
        }

        return $json_posts;
    }

    /**
     * Loop through the facebook feed and insert them
     *
     * @param array $group_posts
     * @return int
     */
    function insert_posts( $group_posts, $group_id ) {
        $count = 0;

        if ( $group_posts ) {
            foreach ($group_posts as $fb_post) {
                $post_id = $this->insert_post( $fb_post, $group_id );

                if ( $post_id ) {

                    if ( property_exists( $fb_post, 'comments' ) ) {
                        $comment_count = $this->insert_comments( $post_id, $fb_post->comments->data);
                    }

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Insert comments for a post
     *
     * @param  int $post_id
     * @param  array $comments
     * @return int
     */
    function insert_comments( $post_id, $comments ) {
        $count = 0;

        if ( $comments ) {
            foreach ($comments as $comment) {
                $comment_id = $this->insert_comment( $post_id, $comment );

                if ( $comment_id ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check if the post already exists
     *
     * Checks via guid. guid = fb post link
     *
     * @global object $wpdb
     * @param string $fb_link_id facebook post link
     * @return boolean
     */
    function is_post_exists( $fb_link_id ) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s", $fb_link_id ) );

        if ( $row ) {
            return $row->ID;
        }

        return false;
    }

    /**
     * Check if a comment already exists
     *
     * Checks via meta key in comment
     *
     * @global object $wpdb
     * @param string $fb_comment_id facebook comment id
     * @return boolean
     */
    function is_comment_exists( $fb_comment_id ) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM $wpdb->commentmeta WHERE meta_key = '_fb_comment_id' AND meta_value = %s", $fb_comment_id ) );

        if ( $row ) {
            return true;
        }

        return false;
    }

    /**
     * Insert a new imported post from facebook
     *
     * @param object $fb_post
     * @param int $group_id
     * @return int|WP_Error
     */
    function insert_post( $fb_post, $group_id ) {

        // bail out if the post already exists
        if ( $post_id = $this->is_post_exists( $fb_post->permalink_url ) ) {
            return $post_id;
        }

        $featured_image = false;
        $option = get_option( 'fbgr2wp_settings', array(
            'post_status'    => 'publish',
            'comment_status' => 'open'
        ) );

        $postarr = array(
            'post_type'      => $this->post_type,
            'post_status'    => $option['post_status'],
            'comment_status' => isset( $option['comment_status'] ) ? $option['comment_status'] : 'open',
            'ping_status'    => isset( $option['comment_status'] ) ? $option['comment_status'] : 'open',
            'post_author'    => 1,
            'post_date'      => gmdate( 'Y-m-d H:i:s', ( strtotime( $fb_post->created_time ) ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
            'guid'           => $fb_post->permalink_url
        );

        $meta = array(
            '_fb_author_id'   => $fb_post->from->id,
            '_fb_author_name' => $fb_post->from->name,
            '_fb_link'        => $fb_post->permalink_url,
            '_fb_group_id'    => $group_id,
            '_fb_post_id'     => $fb_post->id
        );

        switch ($fb_post->type) {
            case 'status':
                $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->message ), 10, '...' );
                $postarr['post_content'] = $fb_post->message;
                break;

            case 'photo':
            case 'video':

                $featured_image = $fb_post->full_picture;

                if ( property_exists( $fb_post, 'message' ) ) {
                    $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->message ), 10, '...' );
                    $postarr['post_content'] = sprintf( '%1$s', $fb_post->message );
                } else {
                    $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->description ), 10, '...' );
                    $postarr['post_content'] = sprintf( '<blockquote>%1$s</blockquote>', $fb_post->description );
                }

                break;

            case 'link':

                if ( property_exists( $fb_post, 'status_type' ) && $fb_post->status_type == 'shared_story' ) {
                    $featured_image = $fb_post->full_picture;
                }

                $postarr['post_title']   = wp_trim_words( strip_tags( $fb_post->description ), 10, '...' );
                $postarr['post_content'] = '<blockquote>' . $fb_post->description . '</blockquote>';

                if ( property_exists( $fb_post, 'message' ) ) {
                    $postarr['post_content'] = $fb_post->message . "\n" . $postarr['post_content'];
                }

                break;

            default:
                # code...
                break;
        }

        $post_id = wp_insert_post( $postarr );

        if ( $post_id && !is_wp_error( $post_id ) ) {

            if ( $featured_image ) {
                // required libraries for media_sideload_image
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $result      = media_sideload_image( $featured_image, $post_id, $postarr['post_title'] );
                $attachments = get_posts(array('numberposts' => '1', 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC'));

                if ( sizeof($attachments) > 0 ) {
                    set_post_thumbnail( $post_id, $attachments[0]->ID );
                }
            }

            if ( $fb_post->type !== 'status' ) {
                set_post_format( $post_id, $fb_post->type );
            }

            foreach ($meta as $key => $value) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        return $post_id;
    }

    /**
     * Insert a comment in a post
     *
     * @param  int $post_id
     * @param  stdClass $fb_comment
     * @return void
     */
    function insert_comment( $post_id, $fb_comment ) {

        // bail out if the comment already exists
        if ( $this->is_comment_exists( $fb_comment->id ) ) {
            return;
        }

        $commentarr = array(
            'comment_post_ID'    => $post_id,
            'comment_author'     => $fb_comment->from->name,
            'comment_author_url' => 'https://facebook.com/' . $fb_comment->from->id,
            'comment_content'    => $fb_comment->message,
            'comment_date'       => gmdate( 'Y-m-d H:i:s', ( strtotime( $fb_comment->created_time ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
            'comment_approved'   => 1,
            'comment_type'       => 'fb_group_post'
        );

        $meta = array(
            '_fb_author_id'   => $fb_comment->from->id,
            '_fb_comment_id'  => $fb_comment->id
        );

        $comment_id = wp_insert_comment( $commentarr );

        if ( $comment_id && !is_wp_error( $comment_id ) ) {
            foreach ($meta as $key => $value) {
                update_comment_meta( $comment_id, $key, $value );
            }
        }

        self::log( 'debug', 'comment is being inserted with FBID '.$fb_comment->id);

        return $comment_id;
    }

    /**
     * Trash all imported posts
     *
     * @return void
     */
    function trash_all() {
        $query = new WP_Query( array( 'post_type' => $this->post_type, 'posts_per_page' => -1 ) );

        if ( $query->have_posts()) {
            $all_posts = $query->get_posts();

            foreach ($all_posts as $post) {
                wp_delete_post( $post->ID, true );
            }
        }
    }

    /**
     * Adds author, post and group link to the end of the post
     *
     * @global object $post
     * @param string $content
     * @return string
     */
    function the_content( $content ) {
        global $post;

        if ( $post->post_type == $this->post_type ) {
            $author_id   = get_post_meta( $post->ID, '_fb_author_id', true );
            $author_name = get_post_meta( $post->ID, '_fb_author_name', true );
            $link        = get_post_meta( $post->ID, '_fb_link', true );
            $group_id    = get_post_meta( $post->ID, '_fb_group_id', true );

            $author_link = sprintf( '<a href="https://facebook.com/%d" target="_blank">%s</a>', $author_id, $author_name );

            $custom_data = '<div class="fb-group-meta">';
            $custom_data .= sprintf( __( 'Posted by %s', 'fbgr2wp' ), $author_link );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="%s" target="_blank">%s</a>', $link, __( 'View Post', 'fbgr2wp' ) );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="https://facebook.com/groups/%s" target="_blank">%s</a>', $group_id, __( 'View Group', 'fbgr2wp' ) );
            $custom_data .= '</div>';

            $custom_data = apply_filters( 'fbgr2wp_content', $custom_data, $post, $author_id, $author_name, $link, $group_id );

            $content .= $custom_data;
        }

        return $content;
    }

    /**
     * Add support for avatar in fb_group_post comment type
     *
     * @param  array $types
     * @return array
     */
    function avatar_comment_type( $types ) {
        $types[] = 'fb_group_post';

        return $types;
    }

    /**
     * Adds avatar image from facebook in comments
     *
     * @param  string $avatar
     * @param  string $id_or_email
     * @param  int $size
     * @return string
     */
    function get_avatar( $avatar, $id_or_email, $size ) {

        // it's not a comment
        if ( ! is_object( $id_or_email ) ) {
            return $avatar;
        }

        if ( empty( $id_or_email->comment_type ) || $id_or_email->comment_type != 'fb_group_post' ) {
            return $avatar;
        }

        $profile_id = get_comment_meta( $id_or_email->comment_ID, '_fb_author_id', true );

        if ( ! $profile_id ) {
            return $avatar;
        }

        $image  = sprintf( 'http://graph.facebook.com/%1$d/picture?type=square&height=%2$s&width=%2$s', $profile_id, $size );
        $avatar = sprintf( '<img src="%1$s" class="avatar avatar-44 photo avatar-default" height="%2$s" width="%2$s" />', $image, $size );

        return $avatar;
    }

    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/debug.log' );
        }
    }

} // WeDevs_FB_Group_To_WP

$wp_fb_import = WeDevs_FB_Group_To_WP::init();
