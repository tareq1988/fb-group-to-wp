<?php
/*
Plugin Name: Facebook Group to WordPress importer
Plugin URI: http://tareq.wedevs.com/
Description: Import facebook group posts to WordPress
Version: 0.1
Author: Tareq Hasan
Author URI: http://tareq.wedevs.com/
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
        add_action( 'init', array( $this, 'debug_run' ) );
        add_action( 'init', array( $this, 'register_post_type' ) );

        add_action( 'fbgr2wp_import', array( $this, 'do_import' ) );

        add_filter( 'the_content', array( $this, 'the_content' ) );

        // Loads frontend scripts and styles
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    }

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
            'supports'            => array( 'title', 'editor', 'post-formats', ),
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
            wp_schedule_event( time(), 'hourly', 'fbgr2wp_import' );
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
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function enqueue_scripts() {

        /**
         * All styles goes here
         */
        wp_enqueue_style( 'fbgr2wp-styles', plugins_url( 'css/style.css', __FILE__ ), false, date( 'Ymd' ) );

        /**
         * All scripts goes here
         */
        wp_enqueue_script( 'fbgr2wp-scripts', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), false, true );


        /**
         * Example for setting up text strings from Javascript files for localization
         *
         * Uncomment line below and replace with proper localization variables.
         */
        // $translation_array = array( 'some_string' => __( 'Some string to translate', 'fbgr2wp' ), 'a_value' => '10' );
        // wp_localize_script( 'base-plugin-scripts', 'fbgr2wp', $translation_array ) );

    }

    function debug_run() {
        if ( !isset( $_GET['fb2wp_test'] ) ) {
            return;
        }

        do_import();

        die();
    }

    function do_import() {
        $access_token = '226916994002335|ks3AFvyAOckiTA1u_aDoI4HYuuw';
        $group_id = '241884142616448';
        $url = 'https://graph.facebook.com/' . $group_id . '/feed/?access_token=' . $access_token;

        $transient_key = 'g2w_' . $group_id;
        $json_posts = get_transient( $transient_key );

        if ( false === $json_posts ) {
            $request = wp_remote_get( $url );
            $json_posts = wp_remote_retrieve_body( $request );

            if ( is_wp_error( $request ) || $request['response']['code'] != 200 ) {
                return false;
            }

            set_transient( $transient_key, $json_posts, HOUR_IN_SECONDS );
        }

        $decoded = json_decode( $json_posts );
        $group_posts = $decoded->data;
        $paging = $decoded->paging;

        // type: status, photo, link
        $count = 0;
        if ( $group_posts ) {
            foreach ($group_posts as $fb_post) {
                $post_id = $this->insert_post( $fb_post, $group_id );

                if ( $post_id ) {
                    $count++;
                }
            }
        }

        printf( '%d posts imported', $count );
    }

    function is_post_exists( $fb_link_id ) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_status = 'publish'", $fb_link_id ) );

        if ( $row ) {
            return true;
        }

        return false;
    }

    function insert_post( $fb_post, $group_id ) {

        // bail out if the post already exists
        if ( $this->is_post_exists( $fb_post->actions[0]->link )) {
            return;
        }

        $postarr = array(
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'post_author' => 1,
            'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( $fb_post->created_time ) ),
            'guid' => $fb_post->actions[0]->link
        );

        $meta = array(
            '_fb_author_id' => $fb_post->from->id,
            '_fb_author_name' => $fb_post->from->name,
            '_fb_link' => $fb_post->actions[0]->link,
            '_fb_group_id' => $group_id,
            '_fb_post_id' => $fb_post->id
        );

        switch ($fb_post->type) {
            case 'status':
                $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 6, '...' );
                $postarr['post_content'] = $fb_post->message;
                break;

            case 'photo':

                if ( !isset( $fb_post->message ) ) {
                    $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->story ), 6, '...' );
                    $postarr['post_content'] = sprintf( '<p>%1$s</p> <div class="image-wrap"><img src="%2$s" alt="%1$s" /></div>', $fb_post->story, $fb_post->picture );
                } else {
                    $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 6, '...' );
                    $postarr['post_content'] = sprintf( '<p>%1$s</p> <div class="image-wrap"><img src="%2$s" alt="%1$s" /></div>', $fb_post->message, $fb_post->picture );
                }

                break;

            case 'link':
                parse_str( $fb_post->picture, $parsed_link );

                $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 6, '...' );
                $postarr['post_content'] = '<p>' . $fb_post->message . '</p>';

                if ( !empty( $parsed_link['url']) ) {
                    $postarr['post_content'] .= sprintf( '<a href="%s"><img src="%s"></a>', $fb_post->link, $parsed_link['url'] );
                } else {
                    $postarr['post_content'] .= sprintf( '<a href="%s">%s</a>', $fb_post->link, $fb_post->name );
                }

                break;

            default:
                # code...
                break;
        }

        $post_id = wp_insert_post( $postarr );

        if ( $post_id && !is_wp_error( $post_id ) ) {

            if ( $fb_post->type !== 'status' ) {
                set_post_format( $post_id, $fb_post->type );
            }

            foreach ($meta as $key => $value) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        // var_dump( $fb_post );
        // var_dump( $postarr );
        // var_dump( $meta );

        return $post_id;
    }

    function trash_all() {
        $query = new WP_Query( array( 'post_type' => $this->post_type, 'posts_per_page' => -1 ) );

        if ( $query->have_posts()) {
            $all_posts = $query->get_posts();

            foreach ($all_posts as $post) {
                wp_delete_post( $post->ID, true );
            }
        }
    }

    function the_content( $content ) {
        global $post;

        if ( $post->post_type == $this->post_type ) {
            $author_id = get_post_meta( $post->ID, '_fb_author_id', true );
            $author_name = get_post_meta( $post->ID, '_fb_author_name', true );
            $link = get_post_meta( $post->ID, '_fb_link', true );
            $group_id = get_post_meta( $post->ID, '_fb_group_id', true );

            $author_link = sprintf( '<a href="https://facebook.com/profile.php?id=%d" target="_blank">%s</a>', $author_id, $author_name );

            $custom_data = '<div class="fb-group-meta">';
            $custom_data .= sprintf( __( 'Posted by %s', 'fbgr2wp' ), $author_link );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="%s" target="_blank">%s</a>', $link, __( 'View Post', 'fbgr2wp' ) );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="https://facebook.com/groups/%s" target="_blank">%s</a>', $group_id, __( 'View Group', 'fbgr2wp' ) );
            $custom_data .= '</div>';

            $content .= $custom_data;
        }

        return $content;
    }

} // WeDevs_FB_Group_To_WP

$wp_fb_import = WeDevs_FB_Group_To_WP::init();
