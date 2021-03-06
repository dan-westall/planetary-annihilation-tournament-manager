<?php
/**
 * Planetary_Annihilation_Tournament_Manager
 *
 * @package   Planetary_Annihilation_Tournament_Manager_Admin
 * @author    Dan Westall <dan.westall@googlemail.com>
 * @license   GPL-2.0+
 * @link      http://www.dan-westall.co.uk
 * @copyright 2014 Dan Westall
 */

/**
 * Planetary_Annihilation_Tournament_Manager_Admin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Planetary_Annihilation_Tournament_Manager_Admin
 * @author  Dan Westall <dan.westall@googlemail.com>
 */
class WP_Tournament_Manager_Admin {

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = null;

    /**
     * Initialize the plugin by loading admin scripts & styles and adding a
     * settings page and menu.
     *
     * @since     1.0.0
     */
    private function __construct() {

        /*
         * @TODO :
         *
         * - Uncomment following lines if the admin class should only be available for super admins
         */
        /* if( ! is_super_admin() ) {
            return;
        } */

        /*
         * Call $plugin_slug from public plugin class.
         */
        $plugin            = WP_Tournament_Manager::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();


        // Load admin style sheet and JavaScript.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add the options page and menu item.
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'remove_dashboard_meta'));

        // Add an action link pointing to the options page.
        $plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->plugin_slug . '.php');
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));

        add_action('gform_field_advanced_settings', array($this, 'action_add_gravity_forms_map_field'), 10, 2);
        add_action('gform_editor_js', array($this, 'action_add_gravity_forms_map_field_setting'));
        add_filter('gform_tooltips', array($this, 'filter_gravity_forms_tooltips'));

        //user admin columns
        add_filter('manage_users_columns', array($this, 'modify_user_table'));
        add_filter('manage_users_custom_column', array($this, 'modify_user_table_row'), 10, 3);

        //post type columns
        add_filter('manage_edit-match_columns', array($this, 'match_columns'));
        add_filter('manage_edit-tournament_columns', array($this, 'tournament_columns'));
        add_filter("manage_edit-player_columns", array($this, 'player_columns'));
        add_filter("manage_edit-notification_columns", array($this, 'notification_columns'));

        add_action('manage_posts_custom_column', array($this, 'custom_columns'), 10, 2);

        //admin dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'tournament_player_management') );


        if(function_exists('acf_add_options_sub_page')){
            acf_add_options_sub_page(array(
                'title' => 'PLTM Signatures',
                'parent' => 'options-general.php',
                'capability' => 'manage_options'
            ));

        }

        add_filter( 'parse_query', array($this, 'player_table_filter') );
        add_action( 'restrict_manage_posts', array($this, 'player_table_filtering') );

    }


    public function tournament_columns($columns) {

        global $post, $current_user;
        get_currentuserinfo();

        $columns = array(
            'cb'       => '<input type="checkbox" />',
            'title'    => __('Title'),
            'spaces'   => __('Open Slots / Taken slots'),
            'run_date' => __('Run date'),
            'status'   => __('Status')
        );

        return $columns;

    }

    public function match_columns($columns) {

        global $post, $current_user;
        get_currentuserinfo();

        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'title'      => __('Title'),
            'tournament' => __('Tournament'),
            'players'    => __('Players'),
            'format'     => __('Format')

        );

        return $columns;

    }

    public function player_columns($columns) {

        global $post, $current_user;
        get_currentuserinfo();

        $columns = array(
            'cb'                   => '<input type="checkbox" />',
            'title'                => __('Title'),
            'player_profile_owner' => __('Correct Profile Owner'),
            'player_user_account' => __('Player User Acount'),
            'pa_stats_id'       => __('Player PA Stats ID'),

        );

        return $columns;

    }

    public function notification_columns($columns) {

        global $post, $current_user;
        get_currentuserinfo();

        $columns = array(
            'cb'                   => '<input type="checkbox" />',
            'title'                => __('Title'),
            'notification_action'  => __('Notification Action')
        );

        return $columns;

    }




    public function modify_user_table($column) {

        $column = array(
            'cb'             => '<input type="checkbox" />',
            'username'       => __('Username'),
            'nickname'       => __('Nickname'),
            'displayname'    => __('Display Name'),
            'clan'           => __('Clan'),
            'role'           => __('Role'),
            'email'          => __('email'),
            'player_profile' => __('Player Profile')
        );

        return $column;
    }

    public function modify_user_table_row($val, $column_name, $user_id) {

        $user = get_userdata($user_id);

        switch ($column_name) {

            case 'clan' :

                return get_user_meta($user_id, 'clan', true);

                break;


            case 'nickname' :

                return get_user_meta($user_id, 'nickname', true);

                break;


            case 'displayname' :

                return $user->display_name;

                break;


            case 'player_profile' :

                if (get_user_meta($user_id, 'player_id', true)) {

                    $player_id = get_user_meta($user_id, 'player_id', true);

                    return sprintf('<a href="%s">%s</a>', admin_url('post.php?post=' . $player_id . '&action=edit'), get_the_title($player_id));

                } else {
                    return __('No Player profiled linked', 'PLTM');
                }

                break;

        }

    }


    public function custom_columns($column, $post_id) {

        global $current_user;
        get_currentuserinfo();

        switch ($column) {

            case 'spaces';

                $tournament_closed        = get_post_meta($post_id, 'signup_closed', true);
                $tournament_slots         = get_post_meta($post_id, 'slots', true);
                $tournament_reserve_slots = get_post_meta($post_id, 'reserve_slots', true);
                $total_tournament_slots   = ($tournament_slots + $tournament_reserve_slots);
                $tournament_player_status = tournamentCPT::$tournament_player_status;

                $current_player_total     = tournamentCPT::get_tournament_player_count($post_id, [$tournament_player_status[0], $tournament_player_status[1]]);

                echo sprintf('%s/%s', $total_tournament_slots, $current_player_total);

                break;

            case 'run_date';

                echo date( 'd.m.y', strtotime(get_post_meta($post_id, 'run_date', true) ) );

                break;

            case 'order_form';

                echo '<a href="' . get_permalink($post_id) . 'view" target="_blank">View PO Form</a>';

                break;

            case 'status';

                echo tournamentCPT::$tournament_status[get_post_meta($post_id, 'tournament_status', true)];

                break;

            case "tournament":

                $tournament_id = matchCPT::get_match_tournament_id($post_id);

                if ($tournament_id) {

                    $title = get_the_title($tournament_id);
                    $url   = admin_url('post.php?post=' . $tournament_id . '&action=edit');

                    printf('<a href="%s">%s</a>', $url, $title);

                } else {
                    echo 'No tournament link found';
                }

                break;

            case "players":

                $players = p2p_type('match_players')->get_connected($post_id);

                foreach ($players->posts As $player) {

                    $players_array[] = sprintf('<a href="%s">%s</a>', admin_url('post.php?post=' . $player->ID . '&action=edit'), $player->post_title);

                }

                echo implode(', ', $players_array);

                break;

            case "player_profile_owner" :

                $post = get_post($post_id);

                $user = get_userdata($post->post_author);

                $player_user_id = get_post_meta($post_id, 'user_id', true);

                if ($user->ID == $player_user_id) {

                    printf('Yes (%s)', $user->display_name);

                } else if (empty($player_user_id) || $player_user_id == null) {

                    printf('Action Needed: No user id Set');

                } else if ($user->ID != $player_user_id) {

                    printf('Action Needed: Incorrect post_author, user_id ');

                }

                break;

            case "player_user_account" :

                $player_user_id = get_post_meta($post_id, 'user_id', true);

                $user = get_userdata($player_user_id);

                if($player_user_id){

                    echo sprintf('<a href="%s">%s</a>', get_edit_user_link($player_user_id), $user->display_name);

                } else {
                    echo 'Account not linked';
                }

                break;

            case "pa_stats_id" :

                if(get_post_meta($post_id, 'pastats_player_id', true)){
                    echo get_post_meta($post_id, 'pastats_player_id', true);
                } else if(get_post_meta($post_id, 'player_email', true)) {
                    echo 'Player has no player PA stats ID set! <a class="player-missing-pa-stats-id-email" data-security="'.wp_create_nonce( "missing_pa_stats_id" ).'" data-player-id="'.$post_id.'" href="javascript:void(0);">Send Email</a>';
                }



                break;

            case "notification_action" :

                if(get_post_meta($post_id, 'notification_actions', true)){
                    echo notificationCPT::$notification_actions[get_post_meta($post_id, 'notification_actions', true)];
                } else {
                    echo 'Not in use';
                }

                break;

        }
    }

    function action_add_gravity_forms_map_field($position, $form_id) {

        //create settings on position 50 (right after Admin Label)
        if ($position == 50) {
            ?>
            <li class="field_map_setting field_setting">
                <label for="field_admin_label">
                    <?php _e("Field Map", "gravityforms"); ?>
                    <?php gform_tooltip("form_field_field_map_value") ?>
                </label>
                <input type="text" id="field_field_map_value"
                       onchange="SetFieldProperty('field_mapField', this.value);"/>
            </li>
        <?php
        }
    }

    //Action to inject supporting script to the form editor page

    function action_add_gravity_forms_map_field_setting() {
        ?>
        <script type='text/javascript'>
            //adding setting to fields of type "text"
            fieldSettings["text"] += ", .field_map_setting";
            fieldSettings["email"] += ", .field_map_setting";
            fieldSettings["textarea"] += ", .field_map_setting";
            fieldSettings["checkbox"] += ", .field_map_setting";

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).bind("gform_load_field_settings", function (event, field, form) {

                jQuery("#field_field_map_value").val(field["field_mapField"]);
            });
        </script>
    <?php
    }

    //Filter to add a new tooltip

    function filter_gravity_forms_tooltips($tooltips) {
        $tooltips["form_field_encrypt_value"] = "<h6>Field Map</h6>Check this box to encrypt this field's data";

        return $tooltips;
    }

    public function remove_dashboard_meta() {

        $widgets = [
            ['slug' => 'dashboard_incoming_links', 'screen' => 'dashboard', 'context' => 'normal'],
            ['slug' => 'dashboard_plugins', 'screen' => 'dashboard', 'context' => 'normal'],
            ['slug' => 'dashboard_primary', 'screen' => 'dashboard', 'context' => 'normal'],
            ['slug' => 'dashboard_incoming_links', 'screen' => 'dashboard', 'context' => 'normal'],
            ['slug' => 'dashboard_quick_press', 'screen' => 'dashboard', 'context' => 'side'],
            ['slug' => 'dashboard_recent_drafts', 'screen' => 'dashboard', 'context' => 'side'],
            ['slug' => 'dashboard_recent_comments', 'screen' => 'dashboard', 'context' => 'side'],
            ['slug' => 'dashboard_right_now', 'screen' => 'dashboard', 'context' => 'side'],
            ['slug' => 'dashboard_activity', 'screen' => 'dashboard', 'context' => 'side']
        ];

        array_map(function($widget){
            remove_meta_box($widget['slug'], $widget['screen'], $widget['context']);
        }, $widgets);

    }

    function tournament_player_management() {

//        wp_add_dashboard_widget(
//            'tournament_player_signup_status', // Widget slug.
//            'Tournament Player signup stats', // Title.
//            array( $this, 'tournament_player_signup_status') // Display function.
//        );
    }

    public function tournament_player_signup_status() {

        ?>

        <table class="widefat">
            <thead>
            <tr>
                <td>Player Name</td>
                <td>User account</td>
                <td>PA Stats Number</td>
                <td>Tournament</td>
                <td>Status</td>
                <td></td>

            </tr>

            </thead>

        </table>


    <?php

    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        /*
         * @TODO :
         *
         * - Uncomment following lines if the admin class should only be available for super admins
         */
        /* if( ! is_super_admin() ) {
            return;
        } */

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles() {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        wp_enqueue_style( 'default-' . $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/default.admin.css', __FILE__ ), array(), WP_Tournament_Manager::VERSION );

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('assets/css/admin.css', __FILE__), array(), WP_Tournament_Manager::VERSION);
        }

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts() {

        if (!isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        wp_enqueue_script( 'default-' . $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/default.admin.js', __FILE__ ), array( 'jquery' ), WP_Tournament_Manager::VERSION );

        $screen = get_current_screen();
        if ($this->plugin_screen_hook_suffix == $screen->id) {
            wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), WP_Tournament_Manager::VERSION);
        }

    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {


        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         *
         * @TODO:
         *
         * - Change 'manage_options' to the capability you see fit
         *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
         */


        add_menu_page(
            'WP Tournament Manager',
            __('WP Tournament Manager', $this->plugin_slug),
            'manage_options',
            $this->plugin_slug,
            [ $this, 'display_plugin_admin_page' ],
            'dashicons-networking');

        //add_submenu_page( 'my-top-level-handle', 'Page title', 'Sub-menu title', 'manage_options', 'my-submenu-handle', 'my_magic_function');
//
//        $this->plugin_screen_hook_suffix = add_options_page(
//            __('WP Tournament Manager Settings', $this->plugin_slug),
//            __('WP Tournament Manager', $this->plugin_slug),
//            'manage_options',
//            $this->plugin_slug,
//            array($this, 'display_plugin_admin_page')
//        );


    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
        include_once('views/admin.php');
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links($links) {

        return array_merge(
            array(
                'settings' => '<a href="' . admin_url('options-general.php?page=' . $this->plugin_slug) . '">' . __('Settings', $this->plugin_slug) . '</a>'
            ),
            $links
        );

    }

    function player_table_filter( $query ) {

        if( is_admin() && ( $query->query['post_type'] == playerCPT::$post_type  || $query->query['post_type'] == matchCPT::$post_type ) ) {
            $qv = &$query->query_vars;

            if( !empty( $_GET['tournament_filter'] ) ) {

                switch($query->query['post_type']){

                    case playerCPT::$post_type:

                        $qv['connected_type']  = 'tournament_players';
                        $qv['connected_items'] = $_GET['tournament_filter'];
                        $qv['nopaging']        = true;

                        break;

                    case matchCPT::$post_type:

                        $qv['connected_type']  = 'tournament_matches';
                        $qv['connected_items'] = $_GET['tournament_filter'];
                        $qv['nopaging']        = true;

                        break;
                }



            }
        }
    }

    public function player_table_filtering() {

        global $wpdb;

        $screen = get_current_screen();

        if ( $screen->post_type == 'player' || $screen->post_type == 'match') {

            DW_Helper::generate_post_select('tournament_filter', tournamentCPT::$post_type, $_GET['tournament_filter'] );

        }

    }

}