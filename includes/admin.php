<?php

require_once dirname( __FILE__ ) . '/class.settings-api.php';

/**
 * Admin options handler class
 *
 * @since 0.4
 * @author Tareq Hasan <tareq@wedevs.com>
 */
class WeDevs_FB_Group_To_WP_Admin {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API();

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_submenu_page( 'edit.php?post_type=fb_group_post', __( 'Facebook Group to WordPress Importer', 'fbgr2wp' ), __( 'Settings', 'fbgr2wp' ), 'manage_options', 'fbgr2wp-settings', array( $this, 'settings_page' ) );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'fbgr2wp_settings',
                'title' => __( 'Settings', 'cpm' )
            )
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array();
        $settings_fields['fbgr2wp_settings'] = array(
            array(
                'name'    => 'app_id',
                'label'   => __( 'Facebook App ID', 'cpm'),
                'default' => '',
                'desc'    => sprintf( __( 'Insert your facebook application ID from <a href="%s">here</a>.', 'fbgr2wp' ), 'https://developers.facebook.com/apps/' )
            ),
            array(
                'name'    => 'app_secret',
                'label'   => __( 'Facebook App Secret', 'cpm'),
                'default' => '',
                'desc'    => __( 'Insert your facebook App Secret' )
            ),
            array(
                'name'    => 'group_id',
                'label'   => __( 'Facebook Group ID', 'fbgr2wp'),
                'default' => '',
                'desc'    => __( 'Add your facebook group ID. e.g: 241884142616448' )
            ),
            array(
                'name'    => 'limit',
                'label'   => __( 'List per Query', 'fbgr2wp'),
                'default' => '30',
                'desc'    => __( 'Posts fetched from Facebook in a single query' )
            ),
            array(
                'name'    => 'post_status',
                'label'   => __( 'Default Post Status', 'fbgr2wp'),
                'default' => 'publish',
                'type'    => 'select',
                'options' => get_post_statuses(),
                'desc'    => __( 'What will be the post status when a post is imported/created' )
            ),
            array(
                'name'    => 'comment_status',
                'label'   => __( 'Default Comment Status', 'fbgr2wp'),
                'default' => 'open',
                'type'    => 'select',
                'options' => array(
                    'open'   => __( 'Open', 'fbgr2wp' ),
                    'closed' => __( 'Closed', 'fbgr2wp' )
                ),
            ),
        );

        return $settings_fields;
    }

    function settings_page() {
        echo '<div class="wrap">';
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }
}