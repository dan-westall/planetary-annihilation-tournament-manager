<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class tournamentCPT {

    public static $post_type = 'tournament';

    public static $tournament_status = array('Signup', 'In Progress', 'Cancelled', 'Finished', 'Preparation');

    public static $tournament_format = array('standard' => 'Standard', 'clanwars' => 'League (Clan Wars)', 'kotp' => 'King of the planet', 'teamarmies' => 'Team Armies');

    public static $tournament_player_status = array( 'Active', 'Reserve', 'No Show', 'Banned', 'Disqualify', 'Withdrawn');

    private $player_tournament_status = '';

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_tournament') );
        add_action( 'init', array( $this, 'register_cpt_taxonomies') );
        add_action( 'init', array( $this, 'cpt_api_args') );

        add_action( 'after_setup_theme', array( $this, 'ctp_permission') );

        add_action( 'widgets_init', array( $this, 'register_tournament_sidebar') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );
        add_action( 'p2p_created_connection', array( $this, 'action_p2p_new_connection' ) );
        add_action( 'p2p_delete_connections', array( $this, 'action_p2p_delete_connection' ) );
        add_action( 'p2p_tournament_matches_args',   array( $this, 'p2p_tournament_match_fields'));

        add_action( 'gform_after_submission', array( $this, 'signup_tournament_player'), 10, 2);
        add_filter( 'gform_validation', array( $this, 'signup_form_validation'), 10, 4);
        add_filter( 'gform_validation_message', array( $this, 'signup_form_validation_message'), 10, 2);
        //add_action( 'gform_confirmation', array( $this, 'signup_custom_confirmation'), 10, 2);

        add_action( 'template_include', array( $this, 'load_endpoint_template')  );
        //todo sync players from challonge to wordpress, fair bit of work, need to refactor signup_tournament_player to make it happen
        //add_action( 'save_post', array( $this, 'action_challonge_sync_check') );

        add_shortcode('tournament-players', array( $this, 'get_tournament_players') );

        add_action( 'save_post',  array( $this, 'delete_tournament_caches') );
        add_action( 'tournament_state_change',  array( $this, 'delete_tournament_caches') );

        add_filter( 'tournament_rounds', array( $this, 'filter_tournament_rounds' ) );
        //add_filter( 'the_title', array( $this, 'filter_endpoint_titles'), 10, 2 );
        //add_filter( 'single_template', array( $this, 'single_tournament_template') );
        add_filter( 'post_updated_messages', array( $this, 'filter_post_type_feedback_messages') );

        add_filter( 'acf/load_field/name=challonge_tournament_link', array( $this, 'filter_challonge_tournament_listing') );
        add_filter( 'acf/load_field/name=tournament_status', array( $this, 'filter_tournament_status') );
        add_filter( 'acf/load_field/name=fixture_status', array( $this, 'filter_tournament_status') );
        add_filter( 'acf/load_field/name=tournament_format', array( $this, 'filter_tournament_format') );

        add_filter( 'page_js_args', array( $this, 'filter_page_js_vars'), 10, 2);

        add_filter( 'json_prepare_post',  array( $this, 'tournament_json_extend_v2' ), 10, 3 );

        add_action( 'parse_query',   array( $this, 'tournament_api_filter'));
        add_action( 'pre_get_posts',   array( $this, 'pre_tournament_api_filter'));


        add_filter( 'tournament_prize_tiers', array( $this, 'get_tournament_prize_tiers') );

        add_action ( 'the_pos', array( $this, 'amend_tournament_object'), 10, 1 );

    }

    function register_cpt_tournament(){

        $tournamentLabel = array(
            'name'               => __('Tournaments'),
            'menu_name'          => __('Tournaments'),
            'all_items'          => __('All Tournaments'),
            'singular_name'      => __('Tournament'),
            'add_new_item'       => __('Add New Tournament'),
            'edit_item'          => __('Edit Tournament'),
            'new_item'           => __('New Tournament'),
            'view_item'          => __('View Tournament'),
            'search_items'       => __('Search Tournaments'),
            'not_found'          => __('No Tournaments found'),
            'not_found_in_trash' => __('No Tournaments found in trash')
        );

        $tournamentArgs = array(
            'labels'                => $tournamentLabel,
            'description'           => 'Description',
            'public'                => true,
            'has_archive'           => true,
            'exclude_from_search'   => true,
            'show_ui'               => true,
            'show_in_json'          => true,
            'rest_base'             => 'tournament',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'hierarchical'          => false,
            'menu_position'         => 10,
            'menu_icon'             => 'dashicons-networking',
            'capability_type'       => array('tournament', 'tournaments'),
            'supports'              => array('title', 'editor', 'thumbnail')
        );

        register_post_type( self::$post_type, $tournamentArgs );

    }

    function cpt_api_args() {
        global $wp_post_types;
        $wp_post_types['tournament']->show_in_rest = true;
        $wp_post_types['tournament']->rest_base = 'tournament';
        $wp_post_types['tournament']->rest_controller_class = 'WP_REST_Posts_Controller';
    }

    function ctp_permission(){

        $roles = array(
            get_role('administrator')
        );


        $caps  = array(
            'read',
            'read_'.self::$post_type.'',
            'read_private_'.self::$post_type.'s',
            'edit_'.self::$post_type,
            'edit_'.self::$post_type.'s',
            'edit_private_'.self::$post_type.'s',
            'edit_published_'.self::$post_type.'s',
            'edit_others_'.self::$post_type.'s',
            'publish_'.self::$post_type.'s',
            'delete_'.self::$post_type,
            'delete_'.self::$post_type.'s',
            'delete_private_'.self::$post_type.'s',
            'delete_published_'.self::$post_type.'s',
            'delete_others_'.self::$post_type.'s',
        );

        foreach ($roles as $role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    function register_cpt_taxonomies(){

        $labels = array(
            'name'              => _x( 'Tournament Affiliation', 'taxonomy general name' ),
            'singular_name'     => _x( 'Tournament Affiliation', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Tournament Affiliation' ),
            'all_items'         => __( 'All Tournament Affiliation' ),
            'parent_item'       => __( 'Parent Tournament Affiliation' ),
            'parent_item_colon' => __( 'Parent Tournament Affiliation:' ),
            'edit_item'         => __( 'Edit Tournament Affiliation' ),
            'update_item'       => __( 'Update Tournament Affiliation' ),
            'add_new_item'      => __( 'Add New Tournament Affiliation' ),
            'new_item_name'     => __( 'New Tournament Affiliation' ),
            'menu_name'         => __( 'Tournament Affiliation' ),
        );

        $args = array(
            'labels'            => $labels,
            'show_ui'           => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'affiliation-type' ),
        );

        register_taxonomy( 'tournament_affiliation', self::$post_type, $args );

        $labels = array(
            'name'              => _x( 'Tournament Series', 'taxonomy general name' ),
            'singular_name'     => _x( 'Tournament Series', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Tournament Series' ),
            'all_items'         => __( 'All Tournament Series' ),
            'parent_item'       => __( 'Parent Tournament Series' ),
            'parent_item_colon' => __( 'Parent Tournament Series:' ),
            'edit_item'         => __( 'Edit Tournament Series' ),
            'update_item'       => __( 'Update Tournament Series' ),
            'add_new_item'      => __( 'Add New Tournament Series' ),
            'new_item_name'     => __( 'New Tournament Series' ),
            'menu_name'         => __( 'Tournament Series' ),
        );

        $args = array(
            'labels'            => $labels,
            'show_ui'           => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'series' ),
        );

        register_taxonomy( 'tournament_series', self::$post_type, $args );


    }

    public function register_p2p_connections() {

        global $post;

        //todo move fields off into filters?

        $object_id = isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : 0;
        $object_id = isset($_REQUEST['post']) ? $_REQUEST['post']: 0;


        $post_type = get_post_type($object_id);


        p2p_register_connection_type( array(
            'name' => 'tournament_staff',
            'from' => self::$post_type,
            'to' => 'user',
            'sortable' => 'from',
            'admin_box' => array(
                'show' => 'from',
                'context' => 'advanced'
            ),
            'title' => array(
                'from' => __( 'Tournament Staff', 'my-textdomain' )
            ),
            'fields' => array(
                'role' => array(
                    'title' => 'Role',
                    'type' => 'select',
                    'values' => apply_filters('tournament_staff_roles', array( 'Caster', 'Assistant', 'Director', 'Analyst' ) )
                ),
                'job' => array(
                    'title' => 'Job',
                    'type' => 'text',
                ),
            )
        ) );

        $tournament_players_args = array(
            'name' => 'tournament_players',
            'from' => self::$post_type,
            'to' => 'player',
            'title' => array(
                'from' => __( 'Players', 'PLTM' )
            ),
            'admin_box' => array(
                'show' => 'any',
                'context' => 'advanced'
            ),
            'fields' => array(
                'status' => array(
                    'title' => 'Status',
                    'type' => 'select',
                    'values' => apply_filters('tournament_player_status', self::$tournament_player_status )
                ),
                'clan' => array(
                    'title' => 'Clan',
                    'type' => 'custom',
                    'render' => 'tournamentCPT::p2p_display_clan'
                )
            )
        );

        if( get_tournament_type($object_id) == 'clanwars'){

            $tournament_players_args = array_merge_recursive($tournament_players_args, [ 'fields' => [
                'clan_contact' => array(
                    'title' => 'Clan Contact',
                    'type' => 'checkbox'
                )
            ]]);

        }

        if(get_tournament_type($object_id) == 'teamarmies'){

            $tournament_players_args = array_merge_recursive($tournament_players_args, [ 'fields' => [
                'team_name' => array(
                    'title' => 'Team Name',
                    'type' => 'text'
                )
            ]]);

        }

        $tournament_players_args = array_merge_recursive($tournament_players_args, ['fields' => [
            'note'   => array(
                'title' => 'Note',
                'type'  => 'text',
            ),
            'group'   => array(
                'title' => 'Group',
                'type'  => 'select',
                'values' => range('A', 'Z')
            ),
            'result' => array(
                'title'  => 'Result',
                'type'   => 'select',
                'values' => apply_filters('tournament_prize_tiers', $object_id)
            ),
            'reserve_position' => array(
                'title' => 'R#',
                'type' => 'text'
            )
        ]]);

        p2p_register_connection_type( apply_filters('wptm_p2p_args', $tournament_players_args, $object_id ) );

        p2p_register_connection_type( array(
            'name' => 'tournament_excluded_players',
            'from' => self::$post_type,
            'to' => 'player',
            'title' => array(
                'from' => __( 'Excluded Players', 'PLTM' )
            ),
            'admin_box' => array(
                'show' => 'from',
                'context' => 'side'
            )
        ) );

        $tournament_matches_args = [
            'name'      => 'tournament_matches',
            'from'      => self::$post_type,
            'to'        => matchCPT::$post_type,
            'admin_box' => [
                'show'    => 'any',
                'context' => 'advanced'
            ],

        ];


        if(get_tournament_type($object_id) == 'clanwars' || count(self::tournament_fixtures()) > 0){

            $tournament_matches_args = array_merge_recursive($tournament_matches_args, [ 'fields' => [
                    'match_fixture' => [
                        'title' => 'Fixture',
                        'type' => 'select',
                        'values' => self::tournament_fixtures()
                    ]
            ]]);

        }

        $tournament_matches_args = array_merge_recursive($tournament_matches_args, [ 'fields' => [
            'match_round' => [
                'title' => 'Round',
                'type' => 'select',
                'values' => range(1, 24)
            ]
        ]]);

        p2p_register_connection_type( apply_filters('wptm_p2p_args', $tournament_matches_args, $object_id ) );

    }

    function single_tournament_template($single) {
        global $wp_query, $post;

        /* Checks for single template by post type */
        if ($post->post_type == self::$post_type) {

           return PLTM_PLUGIN_DIR.'/includes/templates/single-'.self::$post_type.'.php';

        }

        return $single;

    }

    public function load_endpoint_template($template_path){

        global $wp_query, $post;

        if ($post->post_type == 'tournament' && isset( $wp_query->query_vars['countdown'] )) {

            $template_path = get_template_directory() . "/countdown.php";

            if(file_exists($template_path)){
                return $template_path;
            }

        }

        if ($post->post_type == 'tournament' && self::is_tournament_endpoint('brackets') && isset($_GET['overlay'])) {

            $template_path = get_template_directory() . "/overlays/tournament-brackets.php";

            if(file_exists($template_path)){
                return $template_path;
            }

        }


        return $template_path;
    }

    public static function tournament_endpoint_sections(){

        global $wp_query, $post;

        $template_path = PLTM_PLUGIN_DIR . "/includes/templates/section-content.php";

        foreach(WP_Tournament_Manager::$tournament_endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

                if(file_exists(PLTM_PLUGIN_DIR . "/includes/templates/single-$post->post_type-$endpoint.php")){

                    $template_path = PLTM_PLUGIN_DIR . "/includes/templates/single-$post->post_type-$endpoint.php";

                }

            }
        }

        include($template_path);

    }

    public static function is_tournament_endpoint($endpoint_check){

        global $wp_query, $post;

        foreach(WP_Tournament_Manager::$tournament_endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint_check] )) {

                return true;

            }
        }

        return false;
    }

    public function filter_tournament_rounds($rounds){

        global $post;

        if(isset($_GET['post'])){

            $post = get_post($_GET['post']);

            if(is_object($post)){

                if($post->post_type == "tournament"){

                    for($round = 1; $round <= get_post_meta($post->ID, 'rounds', true); $round ++){

                        $rounds[$round] = sprintf('Round %s', $round);

                    }

                }

            }

        }

        return $rounds;

    }

    public function filter_endpoint_titles($title, $id){

        global $post, $wp_query;

        if(!is_object($post) && !isset($id) || (is_admin() || !in_the_loop()))
            return $title;

        foreach(WP_Tournament_Manager::$tournament_endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

                $title .= sprintf(' - %s', ucwords($endpoint));

            }

        }

        return $title;

    }

    function register_tournament_sidebar(){

        register_sidebar( array(
            'name'          => __( 'Single Tournament Widgets', 'PLTM' ),
            'description'   => __( 'Used for single tournament widgets', 'PLTM' ),
            'before_widget' => '<section id="%1$s" class="widget container-box %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h1 class="widget-title">',
            'after_title'   => '</h1>',
        ) );

        register_sidebar( array(
            'name'          => __( 'Archive Tournament Widgets', 'PLTM' ),
            'id'            => 'sidebar-5',
            'description'   => __( 'Used for archive tournament widgets', 'PLTM' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h1 class="widget-title">',
            'after_title'   => '</h1>',
        ) );


    }

    public function filter_challonge_tournament_listing($field){

        $c = new ChallongeAPI(WP_Tournament_Manager::fetch_challonge_API());

        $form_listing[] = 'Select Tournament';
        $form_listing[] = 'Custom Tournament ID';

        $args = array('subdomain' => 'exodusesports');

        try{

            $tournaments = $c->getTournaments($args);

            //converts the simplexmlobject to a standard object with arrays, much easier to work with.
            $tournaments = json_decode( json_encode( (array) $tournaments), false );

            if(is_array($tournaments->tournament)){

                foreach($tournaments->tournament as $t){

                    $form_listing[$t->id] = $t->name;

                }

            } else {
                $form_listing[$tournaments->tournament->id] = $tournaments->tournament->name;
            }

            $field['choices'] = $form_listing;

        } catch (Exception $e) {

        }

        return $field;

    }

    public function filter_tournament_status($field){

        $field['choices'] = self::$tournament_status;

        return $field;

    }

    public function filter_tournament_format($field){


        foreach(self::$tournament_format as $key => $format){

            $format_listing[$key] = $format;

        }

        $field['choices'] = $format_listing;

        return $field;

    }

    public function action_p2p_new_connection($p2p_id){

        $connection = p2p_get_connection( $p2p_id );

        if ( defined( 'DOING_AJAX' ) ) {

            //return;

        }

        if ( 'tournament_players' == $connection->p2p_type && is_admin() && get_tournament_type($connection->p2p_from) != 'teamarmies') {

            $tournament_id = $connection->p2p_from;

            $challonge_tournament_id = $this->get_the_challonge_tournament_id($connection->p2p_from);

            $player = get_post($connection->p2p_to);

            $email = get_post_meta($connection->p2p_to, 'player_email', true);
            $ign   = $player->post_title;

            //add player to current challonge tournament
            if($challonge_tournament_id){
                $challonge_result = WPTM_Tournament_Signup::challonge_add_player_to_tournament($challonge_tournament_id, $email, $ign);

                p2p_add_meta( $p2p_id, 'challonge_tournament_id', $challonge_tournament_id);
                p2p_add_meta( $p2p_id, 'challonge_participant_id', $challonge_result->id);

                //save the return to db as this has useful info in it
                update_post_meta($connection->p2p_to, 'challonge_data', $challonge_result);

                //easy search
                update_post_meta($connection->p2p_to, 'challonge_participant_id', $challonge_result->id);
            }

            p2p_add_meta( $p2p_id, 'date', current_time('mysql') );

            self::delete_tournament_caches($tournament_id);

        }

        if(get_tournament_type($connection->p2p_from) == 'teamarmies'){
            self::delete_tournament_caches($tournament_id);
        }



    }

    public function action_p2p_delete_connection($p2p_id){

        $connection = p2p_get_connection( $p2p_id );

        if ( defined( 'DOING_AJAX' ) ) {

            //return;

        }

        if ( 'tournament_players' == $connection->p2p_type && is_admin() && get_tournament_type($connection->p2p_from) != 'teamarmies') {

            //todo tournament remove reason and history will be to be done.

            $tournament_id = $connection->p2p_from;
            $challonge_tournament_id = $this->get_the_challonge_tournament_id($connection->p2p_from);
            $challonge_participant_id = p2p_get_meta( $connection->p2p_id, 'challonge_participant_id', true );

            if($challonge_tournament_id && !empty($challonge_participant_id)){
                $challonge_result = WPTM_Tournament_Signup::challonge_remove_player_from_tournament($challonge_tournament_id, $challonge_participant_id);
            }


            //save the return to db as this has useful info in it
            delete_post_meta($connection->p2p_to, 'challonge_data' );

            //easy search
            delete_post_meta($connection->p2p_to, 'challonge_participant_id');

            self::delete_tournament_caches($tournament_id);

        }

        if(get_tournament_type($connection->p2p_from) == 'teamarmies'){
            self::delete_tournament_caches($tournament_id);
        }

    }

    public static function get_the_challonge_tournament_id($post_id){


        //todo move into a single id this is a pain to reverse from challonge id -> tournament id
        if(get_post_meta($post_id, 'challonge_tournament_link',true) == "Custom Tournament ID"){
            return get_post_meta($post_id, 'custom_tournament_id',true);
        } else {
            return get_post_meta($post_id, 'challonge_tournament_link',true);
        }

        return false;

    }

    public static function get_tournament_id_by($id, $switch = 'challonge_tournament_id'){

        $tournament_id = false;

        switch($switch){

            case "challonge_tournament_id" :

                $args = array(
                    'post_type' => 'tournament',
                    'meta_query'     => array(
                        'relation' => 'OR',
                        array(
                            'key'   => 'custom_tournament_id',
                            'value' => $id
                        ),
                        array(
                            'key'   => 'challonge_tournament_link',
                            'value' => $id
                        )
                    ),
                    'posts_per_page' => 1
                );

                $tournament = get_posts($args);

                //if tournament is empty return false.
                if(!empty($tournament))
                    $tournament_id = $tournament->ID;

                break;

        }

        return $tournament_id;

    }

    function filter_post_type_feedback_messages( $messages ) {

        $post             = get_post();
        $post_type        = get_post_type( $post );
        $post_type_object = get_post_type_object( $post_type );

        if($post_type == self::$post_type){

            $messages[self::$post_type] = array(
                0  => '', // Unused. Messages start at index 1.
                1  => __( 'Tournament updated.', 'PLTM' ),
                2  => __( 'Custom field updated.', 'PLTM' ),
                3  => __( 'Custom field deleted.', 'PLTM' ),
                4  => __( 'Tournament updated.', 'PLTM' ),
                /* translators: %s: date and time of the revision */
                5  => isset( $_GET['revision'] ) ? sprintf( __( 'Tournament restored to revision from %s', 'PLTM' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => __( 'Tournament published.', 'PLTM' ),
                7  => __( 'Tournament saved.', 'PLTM' ),
                8  => __( 'Tournament submitted.', 'PLTM' ),
                9  => sprintf(
                    __( 'Tournament scheduled for: <strong>%1$s</strong>.', 'PLTM' ),
                    // translators: Publish box date format, see http://php.net/date
                    date_i18n( __( 'M j, Y @ G:i', 'PLTM' ), strtotime( $post->post_date ) )
                ),
                10 => __( 'Tournament draft updated.', 'PLTM' ),
            );

            if ( $post_type_object->publicly_queryable ) {
                $permalink = get_permalink( $post->ID );

                $view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View tournament', 'PLTM' ) );
                $messages[ $post_type ][1] .= $view_link;
                $messages[ $post_type ][6] .= $view_link;
                $messages[ $post_type ][9] .= $view_link;

                $preview_permalink = add_query_arg( 'preview', 'true', $permalink );
                $preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview tournament', 'PLTM' ) );
                $messages[ $post_type ][8]  .= $preview_link;
                $messages[ $post_type ][10] .= $preview_link;
            }

        }
        return $messages;

    }

    public static function tournament_menu($post_id = 0){

        //todo remove html spit out to different function

        global $wp_query, $current_user;

        get_currentuserinfo();

        $html = '';

        $tournament        = get_post($post_id);
        $tournament_closed = get_post_meta($tournament->ID, 'signup_closed', true);

        $tournament_signup = new WPTM_Tournament_Signup();

        $endpoint_set = false;

        foreach (WP_Tournament_Manager::$tournament_endpoints as $tournament_endpoint):

            $classes = '';

            if($tournament_endpoint == 'countdown' || $tournament_endpoint == 'brackets-full')
                continue 1;

            if(isset($wp_query->query_vars[$tournament_endpoint])){
                $classes = 'active';
                $endpoint_set = true;
            }


            switch($tournament_endpoint){

                case "sign-up":

                    if( $tournament_signup->is_tournament_signup_open( $tournament->ID ) && !WPTM_Tournament_Signup::is_existing_tournament_player($current_user->player_id, $tournament->ID)){

                        $html .= sprintf('<li class="%4$s"><a href="%1$s%2$s/">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);

                    }

                    break;

                case "matches":

                    if(get_tournament_matches($tournament->ID)) {

                        $html .= sprintf('<li class="%4$s"><a href="%1$s%2$s/">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);

                    }

                    break;

                case "brackets":

                    $template_path = get_template_directory() . "/brackets/bracket-" . $tournament->ID . ".php";
                    
                    if(file_exists($template_path)){
                        $html .= sprintf('<li class="%4$s"><a href="%1$s%2$s/">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);
                    }
                    else
                    {
                        $bracketlink = get_post_meta($tournament->ID, 'brackets', true);
                        //$html .= $bracketlink;

                        if(strpos($bracketlink,"challonge.com") !== FALSE){
                            $html .= sprintf('<li class="%4$s"><a href="%1$s%2$s/">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint),  $classes);
                        }

                    }

                    break;

                case "results" :

                    break;
                default :

                    $html .= sprintf('<li class="%4$s"><a href="%1$s%2$s/">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);

                    break;

            }

        endforeach;

        if(!$endpoint_set){
            $html = sprintf('<li class="active"><a href="%1$s">%2$s</a></li>', get_permalink(), 'Home') . $html;
        } else {
            $html = sprintf('<li><a href="%1$s">%2$s</a></li>', get_permalink(), 'Home') . $html;
        }



        return $html;

    }

    public static function get_tournament_players($attr) {

        extract(shortcode_atts(array(
            'tournament_id' => '',
            'output'        => 'html',
            'autoreload'    => false
        ), $attr));

        $data = array();

        $args = array(
            'connected_type'   => 'tournament_players',
            'connected_items'  => $tournament_id,
            'nopaging'         => true,
            'suppress_filters' => false
        );

        $players = get_posts($args);

        for ($row = 0; $row < count($players); $row++) {

            $array = '';

            $data[$row] = playerCPT::player_return_format_tourney($players[$row],$tournament_id);


        }

        switch($output){

            case "json":

                wp_send_json($data);

                break;

            case "html" :

                self::player_listing_js_deps();

                $matchopts = array($tournament_id,$autoreload);

                return self::player_listing_template($matchopts);


                break;

            case "raw" :

                return $data;

                break;
        }

    }

    public static function get_tournament_date($post_id = null){

        //strip out acf functions, move html out of function

        $tournament = get_post($post_id);

        $format_in = 'Ymd';

        $rundate = new DateTime( date("Y-m-d", strtotime(get_field('run_date', $tournament->ID ))), new DateTimeZone('UTC'));

        //check : is in time, because if not will mess datetime object up.
        if(strpos(get_field('run_time', $tournament->ID ), ':') !== false){
            $time = explode(':', get_field('run_time', $tournament->ID ));
            $rundate->setTime($time[0], $time[1]);
        }

        if(get_field('tournament_status', $tournament->ID ) === '0' || get_field('tournament_status', $tournament->ID ) === '4'){
            if(get_field('run_date', $tournament->ID ) && get_field('run_time', $tournament->ID )){



                echo '<span  itemprop="startDate" content="'.$rundate->format('c').'">'.$rundate->format('l jS F Y') . '</span> @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a>';
                echo '<meta itemprop="eventStatus" content="http://schema.org/EventScheduled">';
            }
            else{
                echo '<h4 style="margin-top:15px;">To be announced</h4>';

            }
        }
        else
        {
            if(get_field('tournament_status', $tournament->ID ) === '1'){
                if(get_field('run_date', $tournament->ID ) && get_field('run_time', $tournament->ID )){

                    echo '<span  itemprop="startDate" content="'.$rundate->format('c').'">'.$rundate->format('l jS F Y') . '</span> @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a>';
                    echo '<meta itemprop="eventStatus" content="http://schema.org/EventScheduled">';
                }


                echo '<h4 style="margin-top:15px;">LIVE NOW</h4>';
            }
            else{
                if(get_field('tournament_status', $tournament->ID ) === '2'){
                    if(get_field('run_date', $tournament->ID ) && get_field('run_time', $tournament->ID )){

                        echo '<span  itemprop="startDate" content="'.$rundate->format('c').'">'.$rundate->format('l jS F Y') . '</span> @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a>';
                        echo '<meta itemprop="eventStatus" content="http://schema.org/EventScheduled">';
                    }

                    echo '<h4 style="margin-top:15px;">CANCELLED</h4>';
                }
                else{

                    if(get_field('run_date', $tournament->ID ) && get_field('run_time', $tournament->ID )){

                        echo '<span  itemprop="startDate" content="'.$rundate->format('c').'">'.$rundate->format('l jS F Y') . '</span> @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a>';
                        echo '<meta itemprop="eventStatus" content="http://schema.org/EventScheduled">';
                    }
                    echo '<h4 style="margin-top:15px;">Finished</h4>';
                }
            }
        }


    }

    public static function get_tournament($attr) {

        extract(shortcode_atts(array(
            'tournament_id' => '',
            'output'        => 'html',
            'return'        => ''
        ), $attr));

        $tournament = get_post($tournament_id);

        if($tournament == null){
            $data = array('error', 'Tournament not found, please check the tournament ID');
        } else {
            $data = self::tournament_return_format($tournament, $data, $return);
        }



        //turned off because they should be doing /api/tournament/345533/players if they want this, same with matches, also limits overhead
        //$data['players']       = self::get_tournament_players(array('tournament_id' => $tournament->ID, 'output' => 'raw'));

        switch($output){

            case "json":

                wp_send_json($data);

                break;

            case "html" :


                break;

            case "raw" :

                return $data;

                break;
        }

    }

    public static function get_tournaments($attr) {

        extract(shortcode_atts(array(
            'status' => '',
            'output'        => 'html'
        ), $attr));

        //todo strong definiton of tournament status
        $args = array(
            'post_type' => self::$post_type,
            'posts_per_page' => -1
        );

        $tournaments = get_posts($args);

        foreach($tournaments as $tournament){

            $data[] = self::tournament_return_format($tournament, array(), array('results' => false));

        }

        switch($output){

            case "json":

                wp_send_json($data);

                break;

            case "html" :


                break;

            case "raw" :

                return $data;

                break;
        }
    }

    //old remove
    public static function tournament_return_format($tournament, $data = array(), $return = array('results' => true, 'prize' => true)){

        $to = new tournamentCPT();

        $signup_status = 'Open';

        if(!self::is_tournament_signup_open($tournament->ID)){
            $signup_status = 'Closed';
        }

        $data['ID']                      = $tournament->ID;
        $data['name']                    = $tournament->post_title;
        $data['description']             = $tournament->post_title;
        $data['date']                    = date('c', strtotime(get_post_meta($tournament->ID, 'run_date', true). ' ' .get_post_meta($tournament->ID, 'run_time', true)));
        $data['time']                    = get_post_meta($tournament->ID, 'run_time', true);
        $data['format']                  = get_post_meta($tournament->ID, 'tournament_type', true);
        $data['slots']                   = get_post_meta($tournament->ID, 'slots', true);
        $data['slots_taken']             = count(get_tournament_players($tournament->ID));
        $data['signup_url']              = get_permalink($tournament->ID) . '/signup';
        $data['url']                     = get_permalink($tournament->ID);
        $data['signup_status']           = $signup_status;
        $data['challonge_tournament_id'] = $to->get_the_challonge_tournament_id($tournament->ID);


        if(true && $return['prize']){
            $data['prize']         = self::get_tournament_prizes($tournament->ID);
        }
        //tournament completed.
        if(true && $return['results']){
            $data['result'] = self::get_tournament_winner_v2($tournament->ID);
        }

        return $data;

    }

    public static function get_tournament_prizes($tournament_id){

        $prizes_array = get_post_meta($tournament_id, 'prize_tiers', true);

        for($row = 0; $row < $prizes_array; $row ++){

            $prizes[] = array('place' => get_post_meta($tournament_id, 'prize_tiers_'.$row.'_place', true), 'prize' => get_post_meta($tournament_id, 'prize_tiers_'.$row.'_prize', true));

        }

        return $prizes;

    }

    public static function get_tournament_winner($tournament_id){

        $result_array = array();

        $matches = new WP_Query( array(
            'post_type' => matchCPT::$post_type,
            'connected_type' => 'tournament_matches',
            'connected_items' => $tournament_id,
            'nopaging' => true,
            )
        );

        p2p_type( 'match_players' )->each_connected( $matches, array(), playerCPT::$post_type );

        while ( $matches->have_posts() ) : $matches->the_post();

            //var_dump($matches->post);

            foreach ( $matches->post->player as $post ) : setup_postdata( $post );

            $result_array[$post->ID] += p2p_get_meta($post->p2p_id, 'winner', true);

            endforeach;

            wp_reset_postdata();

        endwhile;

        wp_reset_postdata();

        arsort($result_array);

    }


    public static function get_tournament_winner_v2($tournament_id, $fields = array(), $Where = array(), $limit = ''){

        global $wpdb;

        $fields[] = "(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p.p2p_to AND meta_key = 'pastats_player_id' LIMIT 1 ) AS pastats_player_id";
        $fields[] = "posts.post_title AS player_ign";
        $fields[] = "count(p2pm.meta_value) AS wins";

        $from = "FROM wp_p2p as p2p";

        $join = array(
            "INNER JOIN $wpdb->posts as posts ON posts.ID = p2p.p2p_to",
            "LEFT JOIN $wpdb->p2pmeta as p2pm ON p2pm.p2p_id = p2p.p2p_id"
        );

        $where[] = "p2p_from IN(SELECT p2p_to
                FROM $wpdb->p2p
                WHERE p2p_from = %s AND p2p_type = 'tournament_matches')";
        $where[] = "AND p2p_type = 'match_players'";
        $where[] = "AND p2pm.meta_key = 'winner'";

        $statement_String = sprintf('SELECT %s %s %s WHERE %s GROUP BY p2p_to ORDER BY wins DESC %s', implode(', ', $fields), $from, implode(' ', $join), implode(' ', $where), $limit);


        $statement = $wpdb->prepare(
            $statement_String,
            $tournament_id
        );

        //echo $statement;
        //if user is admin remove cache and serve fresh results.
        if(DW_Helper::is_site_administrator()){
            delete_transient('tournament_result_' . $tournament_id);
        }

        $result = get_transient( 'tournament_result_' .$tournament_id );

        if ( empty( $result ) ){
            $result = $wpdb->get_results($statement);

            set_transient( 'tournament_result_' .$tournament_id, $result, 12 * HOUR_IN_SECONDS );
        }

        return $result;

    }

    public static function delete_tournament_caches($post_id){

        if ( wp_is_post_revision( $post_id ) )
            return;

        if(function_exists('apc_clear_cache'))
            apc_clear_cache();

        if ( tournamentCPT::$post_type == get_post_type($post_id) ) {
            delete_transient( 'tournament_' .$post_id. '_players' );
        }


    }

    //moved to signup class
    public static function players_excluded_from_tournament($tournament_id){

        global $wpdb;

        $excluded_players_list = $wpdb->query(
            $wpdb->prepare(
                "
                SELECT
                user_email
                    FROM $wpdb->users AS user WHERE user.ID IN ( SELECT ( SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'user_id') FROM wp_p2p  WHERE p2p_type = 'tournament_excluded_players' AND p2p_from = %s)
                ",
                $tournament_id
            )
        );

        return $excluded_players_list;

    }

    public function tournament_json_extend($_post, $post, $context){

        if($post['post_type'] == 'tournament'){

            $remove_fields = array('author', 'parent', 'format', 'slug', 'guid', 'menu_order', 'ping_status', 'sticky', 'content', 'meta' => 'links');

            $tournament_status = self::$tournament_status[get_post_meta($post['ID'], 'tournament_status', true)];

            $tournament_result = [];

            //dont need author
            foreach($remove_fields as $key => $field){
                if(is_string($key)){
                    unset($_post[$key][$field]);
                } else {
                    unset($_post[$field]);
                }
            }

            $matches = p2p_type('tournament_matches')->get_connected($post['ID'], array( 'posts_per_page' => -1));
            $players = p2p_type('tournament_players')->get_connected($post['ID'], array( 'posts_per_page' => -1 ));

            foreach ($players->posts as $player) {

                $avatar =  playerCPT::get_player_avatar_src($player->ID, [20, 20]);

                $result = [];

                $player_details = array(
                    'wp_player_id'       => $player->ID,
                    'player_name'        => $player->post_title,
                    'pa_stats_player_id' => get_post_meta($player->ID, 'pastats_player_id', true),
                    'url'                => get_permalink($player->ID),
                    'status'             => p2p_get_meta($player->p2p_id, 'status', true),
                    'player_avatar'             => $avatar[0]
                );

                //tournament finished
                if($tournament_status == self::$tournament_status[3]){

                    $no_rank = null;

                    $player_finish = p2p_get_meta($player->p2p_id, 'result', true);

                    if(!empty($player_finish)){
                        $tournament_result[$player_finish] = $player_details;
                    }

                    $player_details = array_merge($player_details, [
                        'finish' => ( $player_finish ? $player_finish : $no_rank )
                    ]);

                }

                if(get_tournament_type($post['ID']) == 'teamarmies'){

                    $player_details = array_merge($player_details, [
                        'team_name' => p2p_get_meta($player->p2p_id, 'team_name', true)
                    ]);
                }

                $match_players[] = $player_details;

            }



            $date = get_post_meta($post['ID'], 'run_date', true);
            $time = get_post_meta($post['ID'], 'run_time', true);

            $currentTime = DateTime::createFromFormat( 'U', $timestamp );

            $date = new DateTime($date);

//            $date->setTimestamp(strtotime($date));

            $timeArray = explode(':', $time);

            $date->setTime($timeArray[0], $timeArray[1]);

//            $date->format('Y-m-d H:i:s');

            $_post['status'] = $tournament_status;
            $_post['meta']['total_players'] = count($match_players);
            $_post['meta']['total_matches'] = count($matches->posts);
            $_post['meta']['players']        = $match_players;
            $_post['meta']['tournament_date'] = get_post_meta($post['ID'], 'run_date', true);
            $_post['meta']['tournament_starttime'] = get_post_meta($post['ID'], 'run_time', true);
            $_post['meta']['tournament_datetime'] = $date->getTimestamp();

            if(  ($challonge_id = get_post_meta($post['ID'], 'challonge_tournament_link', true)) > 0)
                $_post['meta']['challonge_id'] = $challonge_id;



            $_post['meta']['tournament_prizes'] = self::get_tournament_prize_tiers($post['ID']);



            $_post['meta']['signup_open'] = is_tournament_signup_open($post['ID']);


            if( have_rows('fixtures', $post['ID']) ) {

                $fixture_match_count = [];

                foreach($matches->posts as $match){

                    $fixture_match_count[p2p_get_meta($match->p2p_id, 'match_fixture', true)] ++;

                }

                // loop through the rows of data
                while (have_rows('fixtures', $post['ID'])) { the_row();

                    // display a sub field value
                    $name           = get_sub_field('name', $post['ID']);
                    $date_time      = get_sub_field('time_and_date', $post['ID']);
                    $fixture_status = get_sub_field('fixture_status', $post['ID']);

                    if(!empty($date_time)) {

                        $fixtures[] = [
                            'date'    => strtotime($date_time),
                            'name'    => $name,
                            'status'  => self::$tournament_status[$fixture_status],
                            'matches' => $fixture_match_count[strtotime($date_time)]
                        ];

                    }

                }

                if(count($fixtures) > 0){
                    $_post['meta']['fixtures'] = $fixtures;
                }

            }

            //tournament finished add winner and other information
            if($tournament_status == self::$tournament_status[3]){
                ksort($tournament_result);
                $_post['meta']['result'] = $tournament_result;
                $_post['meta']['awards'] = '';
            }



        }

        return $_post;

    }

    public function tournament_json_extend_v2($_post, $post, $context){

        if($post['post_type'] == 'tournament' && $context == 'view'){

            global $wpdb;

            $remove_fields = array('author', 'parent', 'format', 'slug', 'guid', 'menu_order', 'ping_status', 'sticky', 'content', 'meta' => 'links');

            $tournament_status = self::$tournament_status[get_post_meta($post['ID'], 'tournament_status', true)];


            $tournament_signup = new WPTM_Tournament_Signup();

            $tournament_result = [];
            $tournament_id = $post['ID'];

            //dont need author
            foreach($remove_fields as $key => $field){
                if(is_string($key)){
                    unset($_post[$key][$field]);
                } else {
                    unset($_post[$field]);
                }
            }

            //3962 clanwars

            $player_query = $wpdb->prepare(
                "
                SELECT
                p2p_id,
                $wpdb->posts.ID,
                $wpdb->posts.post_title,
                (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'pastats_player_id' AND $wpdb->postmeta.post_id = $wpdb->p2p.p2p_to) AS pastats_player_id,
                (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'pastats_player_id' AND $wpdb->postmeta.post_id = $wpdb->p2p.p2p_to) AS player_clan,
                (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'status' AND p2p_id = $wpdb->p2p.p2p_id) AS player_tournament_status,
                (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'result' AND p2p_id = $wpdb->p2p.p2p_id) AS player_finish,
                (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'team_name' AND p2p_id = $wpdb->p2p.p2p_id) AS team_name,
                (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'clan' AND $wpdb->postmeta.post_id = $wpdb->p2p.p2p_to) AS clan_name
                    FROM $wpdb->p2p
                        LEFT JOIN $wpdb->posts ON p2p_to = $wpdb->posts.ID
                            WHERE p2p_from = %s && p2p_type = 'tournament_players'
                ",
                $tournament_id
            );

            $players = $wpdb->get_results( $player_query );

            $match_query = $wpdb->prepare(
                "
                SELECT
                p2p_id,
                $wpdb->posts.ID, 
                $wpdb->posts.post_title, 
                (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'match_fixture' AND p2p_id = $wpdb->p2p.p2p_id) AS match_fixture
                    FROM $wpdb->p2p 
                        LEFT JOIN $wpdb->posts ON p2p_to = $wpdb->posts.ID
                            WHERE p2p_from = %s && p2p_type = 'tournament_matches'
                ",
                $tournament_id
            );

            $matches = $wpdb->get_results( $match_query );


            foreach ($players as $player) {

                $result = [];

                $player_details = array(
                    'wp_player_id'       => $player->ID,
                    'player_name'        => $player->post_title,
                    'pa_stats_player_id' => $player->pastats_player_id,
                    'url'                => get_permalink($player->ID),
                    'status'             => $player->player_tournament_status
                );

                //tournament finished
                if($tournament_status == self::$tournament_status[3]){

                    $no_rank = null;

                    $player_finish = $player->player_finish;

                    if(!empty($player_finish)){
                        $tournament_result[$player_finish] = $player_details;
                    }

                    $player_details = array_merge($player_details, [
                        'finish' => ( $player_finish ? $player_finish : $no_rank )
                    ]);

                }

                if(get_tournament_type($post['ID']) == 'teamarmies'){

                    $player_details = array_merge($player_details, [
                        'team_name' => $player->team_name
                    ]);
                }

                if(get_tournament_type($post['ID']) == 'clanwars'){

                    $player_details = array_merge($player_details, [
                        'team_name' => $player->clan_name
                    ]);
                }

                $match_players[] = $player_details;

            }


            $date = get_post_meta($post['ID'], 'run_date', true);
            $time = get_post_meta($post['ID'], 'run_time', true);

            $currentTime = DateTime::createFromFormat( 'U', $timestamp );

            $date = new DateTime($date);

//            $date->setTimestamp(strtotime($date));

            $timeArray = explode(':', $time);

            $date->setTime($timeArray[0], $timeArray[1]);

//            $date->format('Y-m-d H:i:s');

            $_post['status'] = $tournament_status;
            $_post['meta']['total_players'] = count($match_players);
            $_post['meta']['total_matches'] = count($matches);
            $_post['meta']['players']        = $match_players;
            $_post['meta']['tournament_date'] = get_post_meta($post['ID'], 'run_date', true);
            $_post['meta']['tournament_starttime'] = get_post_meta($post['ID'], 'run_time', true);
            $_post['meta']['tournament_datetime'] = $date->getTimestamp();

            if(  ($challonge_id = get_post_meta($post['ID'], 'challonge_tournament_link', true)) > 0)
                $_post['meta']['challonge_id'] = $challonge_id;


            $_post['meta']['tournament_prizes'] = self::get_tournament_prize_tiers_v2($post['ID']);


            $_post['meta']['signup_open'] = $tournament_signup->is_tournament_signup_open($post['ID']);

            $tournament_fixtures = tournamentCPT::get_tournament_fixtures($tournament_id);

            if( $tournament_fixtures ) {

                $fixture_match_count = [];

                foreach($matches as $match){

                    $fixture_match_count[$match->match_fixture] ++;

                }

                // loop through the rows of data
                foreach ($tournament_fixtures as $fixture) {

                    if(!empty($date_time)) {

                        $fixtures[] = [
                            'date'    => $fixture->fixture_date,
                            'name'    => $fixture->fixture_name,
                            'status'  => self::$tournament_status[$fixture->fixture_status],
                            'matches' => $fixture_match_count[strtotime($fixture->fixture_date)]
                        ];

                    }

                }

                if(count($fixtures) > 0){
                    $_post['meta']['fixtures'] = $fixtures;
                }

            }

            //tournament finished add winner and other information
            if($tournament_status == self::$tournament_status[3]){
                ksort($tournament_result);
                $_post['meta']['result'] = $tournament_result;
                $_post['meta']['awards'] = '';
            }

        }

        return $_post;
    }

    public static function p2p_display_clan($connection, $direction){

        global $wpdb;

        $query = $wpdb->prepare("SELECT (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'clan' AND post_id = p2p.p2p_to) As clan FROM $wpdb->p2p AS p2p WHERE p2p_id = %s AND p2p_type = 'tournament_players'", $direction->name[1]);

        $clan = $wpdb->get_var($query);

        return $clan;

    }

    public static function p2p_display_clan_contact($connection, $direction){

        global $wpdb;

        $query = $wpdb->prepare("SELECT (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'clan_contact' AND post_id = p2p.p2p_to) As clan FROM $wpdb->p2p AS p2p WHERE p2p_id = %s AND p2p_type = 'tournament_players'", $direction->name[1]);

        $clan = $wpdb->get_var($query);

        return $clan;

    }

    public static function tournament_api_filter($wp_query){

        if(isset($wp_query->query_vars['tournament_players']) && in_array('player', $wp_query->query_vars['post_type'])){

            $tournament_id = $wp_query->query_vars['tournament_players'];

            $wp_query->set('connected_type', 'tournament_players');
            $wp_query->set('connected_items', $tournament_id);
            $wp_query->set('connected_meta', [[ 'key' => 'status', 'value' => ['Active', 'Reserve'], 'compare' => 'IN']]);
            $wp_query->set('nopaging', true);
            $wp_query->set('suppress_filters', false);

            if(isset($wp_query->query_vars['clan'])){
                $clan = $wp_query->query_vars['clan'];
                $wp_query->set('meta_query', [[ 'key' => 'clan', 'value' => $clan]]);
            }
        }

        if( $wp_query->query_vars['connected_type'] === 'tournament_players' ){
//
//            causes doubling
//            $wp_query->set('connected_orderby', 'date');
//            $wp_query->set('connected_order', 'asc');

        }

        return $wp_query;

    }

    public static function pre_tournament_api_filter($wp_query){

        if(isset($wp_query->query_vars['tournament_challonge']) && in_array(self::$post_type, $wp_query->query_vars['post_type'])){

            if($wp_query->query_vars['tournament_challonge'] === "true"){
                $wp_query->set('meta_query', [[ 'key' => 'challonge_tournament_link', 'value' => '0', 'compare' => '>']]);
            } elseif($wp_query->query_vars['tournament_challonge'] === "false"){
                //$wp_query->query['meta_query'] = [[ 'key' => 'challonge_tournament_link', 'value' => '0', 'compare' => '='] ];
                $wp_query->set('meta_query', [[ 'key' => 'challonge_tournament_link', 'value' => '0', 'compare' => '='] ]);
            }

        }

    }

    public static function get_tournament_winners($tournament_id){



    }

    public static function tournament_fixtures(){

        global $post;

        $fixtures = [];

        if( isset($_GET['post']) ){
            $tournament_id = $_GET['post'];
        } else if(isset($_POST['post_ID'])){
            $tournament_id = $_POST['post_ID'];
        }

        if(!function_exists('have_Rows'))
            return false;

        if( have_rows('fixtures', $tournament_id) ) {

            // loop through the rows of data
            while (have_rows('fixtures', $tournament_id)) { the_row();

                // display a sub field value
                $name                 = get_sub_field('name', $tournament_id);
                $date_time            = get_sub_field('time_and_date', $tournament_id);

                $fixtures[strtotime($date_time)] = $name;

            }

        }

        return $fixtures;

    }

    public static function filter_page_js_vars($args, $post_id){

        global $post, $current_user; get_currentuserinfo();

        if(false !== ( $player_profile_id = playerCPT::get_user_player_profile_id($current_user->ID) )){
            $args['player_profile_id'] = $player_profile_id;
        }

        return $args;
    }

    public static function allow_withdraw($tournament_id){

        //override, only allow tournaments in signup phase.
        if(get_post_meta($tournament_id, 'tournament_status', true) != 0)
            return false;

        if(get_post_meta($tournament_id, 'allow_withdraw', true))
            return true;

        return false;
    }

    public static function get_tournament_prize_tiers($tournament_id = 0){


        $result = [];
        $position = 1;

        if(!function_exists('have_Rows'))
            return false;

        if(!empty($tournament_id)){

            while ( have_rows('prize_tiers', $tournament_id) ) {

                the_row();

                $result[$position] = get_sub_field('place');

                $position++;

            }

        } else {

            return array_merge($result, range(1, 10) );

        }

        return $result;

    }

    public static function get_tournament_prize_tiers_v2($tournament_id){

        global $wpdb;

        $prize_query = $wpdb->prepare(
            "
                SELECT DISTINCT
                SUBSTRING(meta_key,1,13) AS prize_group,
                GROUP_CONCAT(CASE
                    WHEN meta_key LIKE '%place' THEN meta_value
                END) as place,
                GROUP_CONCAT(CASE
                    WHEN meta_key LIKE '%prize' THEN meta_value
                END) as prize
                    FROM $wpdb->postmeta
                        WHERE post_id = 4199 AND meta_key LIKE 'prize_tiers_%'
                          GROUP by prize_group
                ",
            $tournament_id
        );

        $prizes = $wpdb->get_results( $prize_query );

        return $prizes;

    }

    public static function get_tournament_fixtures($tournament_id){

        global $wpdb;

        $fixtures = $wpdb->get_results(
            "
                SELECT DISTINCT
                SUBSTRING(meta_key,1,10) AS fixture_group,
                GROUP_CONCAT(CASE
                  WHEN meta_key LIKE '%status' THEN meta_value
                END) as fixture_status,
                CASE
                  WHEN meta_key LIKE '%name' THEN meta_value
                END as fixture_name,
                GROUP_CONCAT(CASE
                  WHEN meta_key LIKE '%date' THEN meta_value
                END) as fixture_date
                    FROM wp_postmeta
                      WHERE post_id = $tournament_id AND meta_key LIKE 'fixtures_%'
                        GROUP by fixture_group
                "

        );

        return $fixtures;

    }

    public static function get_tournament_player_count($tournament_id, $status = '') {

        global $wpdb;

        if (empty($status))
            $status = tournamentCPT::$tournament_player_status;
//
//        $query = "SELECT
//                  COUNT($wpdb->posts.ID) as total_players
//                    FROM {$wpdb->prefix}p2p
//                        LEFT JOIN $wpdb->posts ON p2p_to = $wpdb->posts.ID
//                            WHERE p2p_from = %s && p2p_type = 'tournament_players'
//                            AND (SELECT meta_value FROM {$wpdb->prefix}p2pmeta WHERE {$wpdb->prefix}p2pmeta.meta_key = 'status'
//                            AND {$wpdb->prefix}p2pmeta.p2p_id = {$wpdb->prefix}p2p.p2p_id) IN ('" . implode("', '", $status) . "')";
//
//        $player_count_query = $wpdb->prepare($query,
//            $tournament_id
//        );
//
//
//        $player_count = $wpdb->get_var($player_count_query);
//

        $players = p2p_type( 'tournament_players' )->get_connected( $tournament_id, [
            'connected_meta' => [
                [
                    'key' => 'status',
                    'value' => $status
                ]
            ],
            'posts_per_page' => -1
        ] );

        return count($players->posts);


    }

    private static function amend_tournament_object($object){

        if($object->post_type == tournamentCPT::$post_type)
            $object->tournament_status = get_post_meta($object->ID, 'tournament_status', true);

    }
}
