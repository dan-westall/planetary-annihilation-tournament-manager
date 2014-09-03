<?php

class tournamentCPT {

    public static $post_type = 'tournament';

    public static $tournament_status = array('Signup', 'In Progress', 'Cancelled', 'Finished', 'Preparation');

    public static $tournament_format = array('standard' => 'Standard', 'clanwars' => 'Clan Wars', 'kotp' => 'King of the planet', 'teamtournament' => 'Team Tournament');

    public static $tournament_player_status = array( 'Active', 'Reserve', 'No Show', 'Banned', 'Disqualify');

    private $player_tournament_status = '';

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_tournament') );
        add_action( 'init', array( $this, 'register_cpt_taxonomies') );
        
        add_action( 'widgets_init', array( $this, 'register_tournament_sidebar') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );
        add_action( 'p2p_created_connection', array( $this, 'action_p2p_new_connection' ) );
        add_action( 'p2p_delete_connections', array( $this, 'action_p2p_delete_connection' ) );
        add_filter( 'p2p_connectable_args', array( $this, 'filter_p2p_tournament_player_requirements' ) );

        add_action( 'gform_after_submission', array( $this, 'signup_tournament_player'), 10, 2);
        add_filter( 'gform_validation', array( $this, 'signup_form_validation'), 10, 4);
        add_filter( 'gform_validation_message', array( $this, 'signup_form_validation_message'), 10, 2);
        //add_action( 'gform_confirmation', array( $this, 'signup_custom_confirmation'), 10, 2);

        add_action( 'template_include', array( $this, 'load_endpoint_template')  );
        //todo sync players from challonge to wordpress, fair bit of work, need to refactor signup_tournament_player to make it happen
        //add_action( 'save_post', array( $this, 'action_challonge_sync_check') );

        add_shortcode('tournament-players', array( $this, 'get_tournament_players') );

        add_action( 'save_post',  array( $this, 'delete_tournament_caches') );

        add_filter( 'tournament_rounds', array( $this, 'filter_tournament_rounds' ) );
        //add_filter( 'the_title', array( $this, 'filter_endpoint_titles'), 10, 2 );
        //add_filter( 'single_template', array( $this, 'single_tournament_template') );
        add_filter( 'post_updated_messages', array( $this, 'filter_post_type_feedback_messages') );

        add_filter( 'acf/load_field/name=challonge_tournament_link', array( $this, 'filter_challonge_tournament_listing') );
        add_filter( 'acf/load_field/name=tournament_status', array( $this, 'filter_tournament_status') );
        add_filter( 'acf/load_field/name=tournament_format', array( $this, 'filter_tournament_format') );

        add_filter( 'json_prepare_post',  array( $this, 'tournament_json_extend' ), 50, 3 );

        add_action( 'parse_query',   array( $this, 'tournament_api_filter'));


    }

    function register_cpt_tournament(){

        $tournamentLabel = array(
            'name'               => __('Tournaments'),
            'menu_name'          => __('Tournament'),
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
            'labels'                  => $tournamentLabel,
                'description'         => 'Description',
                'public'              => true,
                'has_archive'         => true,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'menu_position'       => 10,
                'menu_icon'           => 'dashicons-networking',
                'supports'            => array('title', 'editor', 'thumbnail')
            );

        register_post_type( self::$post_type, $tournamentArgs );

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

//        p2p_register_connection_type( array(
//            'name' => 'tournament_planets',
//            'from' => self::$post_type,
//            'to' => planetCPT::$post_type,
//            'sortable' => 'from',
//            'admin_box' => array(
//                'show' => 'from',
//                'context' => 'advanced'
//            ),
//            'title' => array(
//                'from' => __( 'Tournament Planets', 'my-textdomain' )
//            ),
//            'fields' => array(
//                'role' => array(
//                    'title' => 'Round',
//                    'type' => 'select',
//                    'values' => apply_filters('tournament_rounds', array( ) )
//                )
//            )
//        ) );


        p2p_register_connection_type( array(
            'name' => 'tournament_players',
            'from' => self::$post_type,
            'to' => 'player',
            'sortable' => 'from',
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
                ),
                'note' => array(
                    'title' => 'Note',
                    'type' => 'text',
                )
            )
        ) );

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


        p2p_register_connection_type( array(
            'name' => 'tournament_matches',
            'from' => self::$post_type,
            'to' => matchCPT::$post_type,
            'admin_box' => array(
                'show' => 'any',
                'context' => 'side'
            )
        ) );

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
        /*
        TODO ADD brackets-full
        */

        return $template_path;
    }

    public static function tournament_endpoint_sections(){

        global $wp_query, $post;

        $template_path = PLTM_PLUGIN_DIR . "/includes/templates/section-content.php";

        foreach(Planetary_Annihilation_Tournament_Manager::$tournament_endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

                if(file_exists(PLTM_PLUGIN_DIR . "/includes/templates/single-$post->post_type-$endpoint.php")){

                    $template_path = PLTM_PLUGIN_DIR . "/includes/templates/single-$post->post_type-$endpoint.php";

                }

            }
        }

        include($template_path);

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

        foreach(Planetary_Annihilation_Tournament_Manager::$tournament_endpoints as $endpoint){

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

    public function signup_custom_confirmation($confirmation, $form, $lead, $ajax){

        $signup_form_id          = get_field('standard_tournament_signup_form', 'option');
        $tournament_id           = url_to_postid($entry['source_url']);

        if ($tournament_id === 0 || $tournament_closed !== false)
            return $confirmation;

        foreach( $form['fields'] as $field ) {
            $values[$field['field_mapField']] = array(
                'id'    => $field['id'],
                'label' => $field['label'],
                'value' => $entry[ $field['id'] ],
            );
        }

        $confirmation = 'Great news, your already signed up to this tournament';

        return $confirmation;
    }

    public function signup_form_validation($validation_result){

        global $wpdb;

        $values = array();

        $signup_form_id    = get_field('standard_tournament_signup_form', 'option');
        $tournament_id     = url_to_postid($_SERVER['HTTP_REFERER']);
        $tournament_closed = get_field('signup_closed', $tournament_id);
        $players           = p2p_type('tournament_players')->get_connected($tournament_id);

        if ($tournament_id === 0 || $tournament_closed !== false)
            return $validation_result;

        if ($signup_form_id && get_field('signup_form')) {
            $signup_form_id = get_field('signup_form');
        }

        if ($signup_form_id != $validation_result['form']['id'])
            return $validation_result;

        if (!is_tournament_signup_open($tournament_id)){
            $validation_result['is_valid'] = false;
        }

        foreach( $validation_result['form']['fields'] as $field ) {
            $values[$field['field_mapField']] = array(
                'id'    => $field['id'],
                'label' => $field['label'],
                'value' => $_POST[ 'input_' . $field['id'] ],
            );
        }

        $player = $wpdb->get_row( $wpdb->prepare("SELECT user_email, ID AS user_id, (SELECT meta_value FROM wp_usermeta  WHERE user_id = user.ID AND meta_key = 'player_id') AS player_id  FROM $wpdb->users AS user WHERE user_email = %s", $values['email']['value']) );


        //$player = DW_Helper::get_post_by_meta('player_email', $values['email']['value']);

        //is player
        if(isset($player->player_id)){

            $p2p_id = p2p_type('tournament_players')->get_p2p_id($tournament_id, $player->player_id);

            //is linked to tournament
            if ($p2p_id) {

                $validation_result['is_valid'] = false;
                $validation_result['form']['cssClass'] = 'already-in-tournament';

            }

        }

        if(!is_user_logged_in() && isset($player->user_email)){

            $validation_result['is_valid'] = false;
            $validation_result['form']['cssClass'] = 'please-login-to-signup';

        }


        if (in_array($values['email']['value'], self::players_excluded_from_tournament($tournament_id))){

            $validation_result['is_valid'] = false;
            $validation_result['form']['cssClass'] = 'player-is-excluded';

        }

        return $validation_result;

    }

    public function signup_form_validation_message($message, $form){

        if(strpos($form['cssClass'], 'already-in-tournament') !== false)
            $message = '<span class="positive-message">Great news, your already signed up to this tournament!. No need to signup again.</span>';

        if(strpos($form['cssClass'], 'player-is-excluded') !== false)
            $message = '<span class="validation_error">Very Sorry but you are excluded from this tournament, if you think this is in error please contact us via the contact form.</span>';

        if(strpos($form['cssClass'], 'please-login-to-signup') !== false)
            $message = '<span class="validation_error">Please login to your account to signup.</span> <a href="'. wp_login_url( get_permalink() ).'/signup" title="Login">Login</a>';

        return $message;

    }

    public function signup_tournament_player($entry, $form) {

        global $wpdb;

        $signup_form_id           = get_field('standard_tournament_signup_form', 'option');
        $tournament_id            = url_to_postid($entry['source_url']);
        $challonge_tournament_id  = $this->get_the_challonge_tournament_id($tournament_id);
        $tournament_slots         = get_post_meta($tournament_id, 'slots', true);
        $tournament_reserve_slots = get_post_meta($tournament_id, 'reserve_slots', true);
        $total_tournament_slots   = ($tournament_slots + $tournament_reserve_slots);
        $connection_meta = [];

        //if tournament 0 bin
        if ($tournament_id === 0)
            return false;

        //if returns false stop
        if(!self::is_tournament_signup_open($tournament_id))
            return false;

        if ($signup_form_id && get_field('signup_form')) {
            $signup_form_id = get_field('signup_form');
        }

        if ($signup_form_id != $entry['form_id'])
            return false;

        //todo move out to general function file as this is a useful snippit
        foreach ($form['fields'] as $field) {
            $values[$field['field_mapField']] = array(
                'id'    => $field['id'],
                'label' => $field['label'],
                'value' => $entry[$field['id']],
            );
        }

        //if email is in excluded players bin
        if (in_array($values['email']['value'], self::players_excluded_from_tournament($tournament_id)))
            return false;

        $user = $wpdb->get_row( $wpdb->prepare("SELECT user_email, ID AS user_id, (SELECT meta_value FROM wp_usermeta  WHERE user_id = user.ID AND meta_key = 'player_id') AS player_id  FROM $wpdb->users AS user WHERE user_email = %s", $values['email']['value']) );



        //existing player
        if (!empty($user)) {

            $player_id = $user->player_id;

            //check to make sure they are not aleady in tournament
            $p2p_id = p2p_type('tournament_players')->get_p2p_id($tournament_id, $player_id);

            if ($p2p_id) {
                return $form;
            }

            wp_update_post( ['ID' => $player_id, 'post_title' => $values['ign']['value'] ] );

        } else {

            //new user accounts have been created to provide features going forward
            $user = get_user_by( 'email', $values['email']['value'] );

            if(!$user){

                $password = wp_generate_password();

                $userdata = array(
                    'user_login' => $values['ign']['value'],
                    'user_email' => $values['email']['value'],
                    'user_pass'  => $password
                );

                $user_id = wp_insert_user($userdata);

                wp_new_user_notification($user_id, $password);

            } else {
                $user_id = $user->ID;
            }

            $player_id = playerCPT::action_new_player_profile($user_id, $values);

        }

        if($values['clan']['value'])
            $connection_meta['clan'] = $values['clan']['value'];

        //add player to current challonge tournament
        if($challonge_tournament_id){
            $challonge_result = $this->challonge_add_player_to_tournament($challonge_tournament_id, $values['email']['value'], $values['ign']['value']);

            $connection_meta = ['challonge_tournament_id' => $challonge_tournament_id, 'challonge_result' => $challonge_result];
        }

        $p2p_result = $this->action_add_player_to_tournament($player_id, $tournament_id, $connection_meta);

        if ($p2p_result) {

            //$this->player_tournament_status active or reserve

            $action = "tournament_signup_$this->player_tournament_status";

            do_action( $action, array( 'player_id' => $player_id, 'tournament_id' => $tournament_id ) );

        }

        //update details, clan tag ingame

        update_post_meta($player_id, 'clan', $values['clan']['value']);
        update_post_meta($player_id, 'clan_contact', $values['clan_contact']['value']);


    }

    public static function is_tournament_signup_open($tournament_id){

        $tournament_closed        = get_post_meta($tournament_id, 'signup_closed', true);
        $tournament_slots         = get_post_meta($tournament_id, 'slots', true);
        $tournament_reserve_slots = get_post_meta($tournament_id, 'reserve_slots', true);
        $total_tournament_slots   = ($tournament_slots + $tournament_reserve_slots);

        $current_player_total     = count(get_tournament_players($tournament_id, array(self::$tournament_player_status[0], self::$tournament_player_status[1])));

        if($tournament_closed == true){

            return false;

        }

        if($current_player_total >= $total_tournament_slots){

            return false;

        }


        return true;

    }

    public function filter_challonge_tournament_listing($field){

        $c = new ChallongeAPI(Planetary_Annihilation_Tournament_Manager::fetch_challonge_API());

        $form_listing[] = 'Select Tournament';
        $form_listing[] = 'Custom Tournament ID';

        $args = array('subdomain' => 'exodusesports');

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

        return $field;

    }

    public function filter_p2p_tournament_player_requirements($args){

//        switch($_POST['p2p_type']){
//
//            case "tournament_players" :
//
//                //player profiles must have emails for challonge intergration
//                $args['meta_query'] = array(
//                    array(
//                        'key' => 'player_email',
//                        'compare' => 'EXISTS'
//                    )
//                );
//
//                break;
//
//        }

        return $args;

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

    public function challonge_add_player_to_tournament($challonge_tournament_id, $email, $ign){

        $c = new ChallongeAPI(Planetary_Annihilation_Tournament_Manager::fetch_challonge_API());

        $c->verify_ssl = false;

        $params = array(
            'participant[name]'               => $ign
        );

        $participant = $c->createParticipant($challonge_tournament_id, $params);

        $result = json_decode( json_encode( (array) $participant), false );

        return $result;

    }

    public function challonge_remove_player_from_tournament($challonge_tournament_id, $challonge_participant_id){

        $c = new ChallongeAPI(Planetary_Annihilation_Tournament_Manager::fetch_challonge_API());

        $c->verify_ssl = false;

        $participant = $c->deleteParticipant($challonge_tournament_id, $challonge_participant_id);

        $result = json_decode( json_encode( (array) $participant), false );

        return $result;
    }

    public function action_p2p_new_connection($p2p_id){

        $connection = p2p_get_connection( $p2p_id );

        if ( 'tournament_players' == $connection->p2p_type && is_admin()) {

            $tournament_id = $connection->p2p_from;

            $challonge_tournament_id = $this->get_the_challonge_tournament_id($connection->p2p_from);

            $player = get_post($connection->p2p_to);

            $email = get_post_meta($connection->p2p_to, 'player_email', true);
            $ign   = $player->post_title;

            //add player to current challonge tournament
            if($challonge_tournament_id){
                $challonge_result = $this->challonge_add_player_to_tournament($challonge_tournament_id, $email, $ign);

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

    }

    public function action_p2p_delete_connection($p2p_id){

        $connection = p2p_get_connection( $p2p_id );

        if ( 'tournament_players' == $connection->p2p_type && is_admin()) {

            //todo tournament remove reason and history will be to be done.

            $tournament_id = $connection->p2p_from;
            $challonge_tournament_id = $this->get_the_challonge_tournament_id($connection->p2p_from);
            $challonge_participant_id = p2p_get_meta( $connection->p2p_id, 'challonge_participant_id', true );

            if($challonge_tournament_id && !empty($challonge_participant_id)){
                $challonge_result = $this->challonge_remove_player_from_tournament($challonge_tournament_id, $challonge_participant_id);
            }


            //save the return to db as this has useful info in it
            delete_post_meta($connection->p2p_to, 'challonge_data' );

            //easy search
            delete_post_meta($connection->p2p_to, 'challonge_participant_id');

            self::delete_tournament_caches($tournament_id);

        }
    }

    public function action_add_player_to_tournament($player_id, $tournament_id, $connection_meta = []){

        $status                   = self::$tournament_player_status[0];
        $tournament_slots         = get_post_meta($tournament_id, 'slots', true);
        $tournament_reserve_slots = get_post_meta($tournament_id, 'reserve_slots', true);
        $total_tournament_slots   = ($tournament_slots + $tournament_reserve_slots);
        $current_player_count     = count(get_tournament_players($tournament_id, array(self::$tournament_player_status[0], self::$tournament_player_status[1])));

        //TODO not sure the challonge stuff should be in here
        //save the return to db as this has useful info in it

        $connection_meta = array(
            'date'                     => current_time('mysql'),
            'status'                   => $status
        );

        if(isset($connection_meta['challonge_result'])){
            update_post_meta($player_id, 'challonge_data', $connection_meta['challonge_result']);

            //easy search
            update_post_meta($player_id, 'challonge_participant_id', $meta['challonge_result']->id);

            $connection_meta = array_merge($connection_meta, [ 'challonge_tournament_id'  => $connection_meta['challonge_tournament_id'], 'challonge_participant_id' => $connection_meta['challonge_result']->id ] );

        }

        //if therere are more players then slots reserve, any logic should have been done by this point
        if($current_player_count >= $tournament_slots){

            $status = self::$tournament_player_status[1];

        }

        $this->player_tournament_status = $status;

        //if ladder information is entered then add that to the connection meta
        if (false) {
            $connection_meta['ladder'] = 0;
        }

        //player found add player to tornament
        $p2p_result = p2p_type('tournament_players')->connect($tournament_id, $player_id, $connection_meta);

        self::delete_tournament_caches($tournament_id);

        return $p2p_result;

    }

    public function get_the_challonge_tournament_id($post_id){


        //todo move into a single id this is a pain to reverse from challonge id -> tournament id
        if(get_post_meta($post_id, 'challonge_tournament_link',true) == "Custom Tournament ID"){
            $challonge_tournament_id = get_post_meta($post_id, 'custom_tournament_id',true);
        } else {
            $challonge_tournament_id = get_post_meta($post_id, 'challonge_tournament_link',true);
        }

        return $challonge_tournament_id;

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

    //not in use
    public function action_challonge_sync_check($post_id){

        if ( wp_is_post_revision( $post_id ) )
            return;

        $post_meta = get_post_custom($post_id);

        //check to make sure this tournament has been linked to a challonge one
        if( array_key_exists('custom_tournament_id', $post_meta) == true || array_key_exists('custom_tournament_id', $post_meta) == true  ){

            if(get_post_meta($post_id, 'challonge_tournament_link',true) == "Custom Tournament ID"){
                $challonge_tournament_id = get_post_meta($post_id, 'custom_tournament_id',true);
            } else {
                $challonge_tournament_id = get_post_meta($post_id, 'challonge_tournament_link',true);
            }

            $c = new ChallongeAPI(Planetary_Annihilation_Tournament_Manager::fetch_challonge_API());

            $args = array(
                'connected_type' => 'tournament_players',
                'connected_items' => $post_id
            );

            $players = get_posts( $args );

            $wp_total_players = count($players);

            $challonge_tournament_players = json_decode( json_encode( (array) $c->getParticipants($challonge_tournament_id) ), false );

            //if wp players doesnt equal challonge players when sync!
            if($wp_total_players != count($challonge_tournament_players->participant)){



            }

        }

    }

    public static function tournament_menu($post_id = 0){

        global $wp_query;

        $html = '';

        $tournament        = get_post($post_id);
        $tournament_closed = get_post_meta($tournament->ID, 'signup_closed', true);

        $endpoint_set = false;


        foreach (Planetary_Annihilation_Tournament_Manager::$tournament_endpoints as $tournament_endpoint):

            $classes = '';

            if($tournament_endpoint == 'countdown' || $tournament_endpoint == 'brackets-full')
                continue 1;

            if(isset($wp_query->query_vars[$tournament_endpoint])){
                $classes = 'active';
                $endpoint_set = true;
            }


            switch($tournament_endpoint){

                case "signup":

                    if(self::is_tournament_signup_open($tournament->ID)){

                        $html .= sprintf('<li class="%4$s"><a href="%1$s/%2$s">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);

                    }

                    break;

                case "matches":

                    if(get_tournament_matches($tournament->ID)) {

                        $html .= sprintf('<li class="%4$s"><a href="%1$s/%2$s">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);

                    }

                    break;

                case "brackets":

                    $template_path = get_template_directory() . "/brackets/bracket-" . $tournament->ID . ".php";
                    
                    if(file_exists($template_path)){
                        $html .= sprintf('<li class="%4$s"><a href="%1$s/%2$s">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);
                    }
                    else
                    {
                        $bracketlink = get_post_meta($tournament->ID, 'brackets', true);
                        //$html .= $bracketlink;

                        if(strpos($bracketlink,"challonge.com") !== FALSE){
                            $html .= sprintf('<li class="%4$s"><a href="%1$s/%2$s">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint),  $classes);
                        }

                    }

                    break;
                default :

                    $html .= sprintf('<li class="%4$s"><a href="%1$s/%2$s">%3$s</a></li>', get_permalink(), $tournament_endpoint, ucwords($tournament_endpoint), $classes);

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


        //todo is this being used?
        if ( matchCPT::$post_type != get_post_type($post_id) ) {

            $tournament_id = matchCPT::get_match_tournament_id($post_id);

            delete_transient( 'tournament_result_' . $tournament_id );
        }

        if ( tournamentCPT::$post_type == get_post_type($post_id) ) {
            delete_transient( 'tournament_' .$post_id. '_players' );
        }


    }

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

            $remove_fields = array('author', 'parent', 'format', 'slug', 'guid', 'excerpt', 'menu_order', 'ping_status', 'sticky');

            //dont need author
            foreach($remove_fields as $field){
                unset($_post[$field]);
            }

            $matches = p2p_type('tournament_matches')->get_connected($post['ID'], array( 'posts_per_page' => -1));
            $players    = p2p_type('tournament_players')->get_connected($post['ID'], array( 'posts_per_page' => -1 ));

            foreach ($players->posts as $player) {

                $match_players[] = array(
                    'wp_player_id'       => $player->ID,
                    'player_name'        => $player->post_title,
                    'pa_stats_player_id' => get_post_meta($player->ID, 'pastats_player_id', true),
                    'url'                => get_permalink($player->ID)
                );

            }

            $_post['status'] = self::$tournament_status[get_post_meta($post['ID'], 'tournament_status', true)];
            $_post['meta']['total_players'] = count($match_players);
            $_post['meta']['total_matches'] = count($matches->posts);
            $_post['meta']['players']        = $match_players;
            $_post['meta']['tournament_date'] = get_post_meta($post['ID'], 'run_date', true);
            $_post['meta']['signup_open'] = is_tournament_signup_open($post['ID']);

        }

        return $_post;

    }

    public static function p2p_display_clan($connection, $direction){

        global $wpdb;

        $query = $wpdb->prepare("SELECT (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'clan' AND post_id = p2p.p2p_to) As clan FROM $wpdb->p2p AS p2p WHERE p2p_id = %s AND p2p_type = 'tournament_players'", $direction->name[1]);

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

        return $wp_query;

    }

}
