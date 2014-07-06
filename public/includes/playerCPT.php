<?php

class playerCPT {

    public static $post_type = 'player';

    function __construct() {

        add_action('init', array($this, 'register_cpt_player'));

        add_action( 'user_register', array( $this, 'action_new_player_profile' ) );
        add_action( 'after_setup_theme', array( $this, 'add_news_caps_to_admin') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

//        //admin menu removed create new player for non admins
        add_action( 'admin_head',  array( $this, 'hide_new_player') );
        add_action( 'load-post-new.php',  array( $this, 'disable_new_player') );

//      change labels
        add_action( 'admin_init',   array( $this, 'change_player_object_label') );

        add_action( 'admin_menu', array( $this, 'prefix_remove_menu_pages') );

        add_action( 'update_post_meta', array( $this, 'link_player_to_user'), 10, 4 );

        add_action('wp_ajax_player_missing_pa_stats_id', array($this,'player_missing_pa_stats_id'));

        add_action( 'profile_update', array( $this, 'delete_user_caches'), 10, 2 );



    }


    function register_cpt_player() {

        $playerLabel = array(
            'name'               => __('Players'),
            'menu_name'          => __('Players'),
            'all_items'          => __('All Players'),
            'singular_name'      => __('All Players'),
            'add_new_item'       => __('Add New Player'),
            'edit_item'          => __('Edit Player'),
            'new_item'           => __('New Player'),
            'view_item'          => __('View Player'),
            'search_items'       => __('Search Players'),
            'not_found'          => __('No Players found'),
            'not_found_in_trash' => __('No Players found in trash')
        );

        $playerArgs = array(
            'labels'              => $playerLabel,
            'description'         => 'Tournament Players',
            'public'              => true,
            'has_archive'         => true,
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array('title', 'author'),
            'capability_type'     => array(self::$post_type,self::$post_type.'s'),
            'map_meta_cap'        => true,
        );

        register_post_type( self::$post_type, $playerArgs );

    }

    public function register_p2p_connections() {



    }

    public function action_new_player_profile($user_id){




    }

    public static function get_player_by($id, $switch = 'pastats_player_id'){

        switch($switch){

            case "pastats_player_id":

                $player = DW_Helper::get_post_by_meta('pastats_player_id', $id);

                break;

            case "challonge_participant_id":

                $player = DW_Helper::get_post_by_meta('challonge_participant_id', $id);

                break;

        }

        return $player;

    }

    public static function get_player($attr) {

        extract(shortcode_atts(array(
            'player_id' => '',
            'by'        => 'pastats_player_id',
            'output'    => 'html'
        ), $attr));

        $player = self::get_player_by($player_id, $by);

        $data = self::player_return_format($player);

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

    public static function player_return_format($player, $data = array(), $return = array('tournaments' => true)){

        $data['id']                 = $player->ID;
        $data['name']               = $player->post_title;
        $data['clan']               = get_post_meta($player->ID, 'clan', true);
        $data['pa_stats_player_id'] = get_post_meta($player->ID, 'pastats_player_id', true);

        $player_profile_image = get_player_avatar($player->ID, $size = 100);

        $data['avatar'] = $player_profile_image;

        if($return['tournaments']){
            $data['player_tournaments']  = self::get_player_entered_tournaments($player->ID);
        }

        return $data;

    }

    public static function player_return_format_tourney($player, $tournament_id, $data = array(), $return = array('tournaments' => true)){

        $data['id']                 = $player->ID;
        $data['name']               = $player->post_title;
        $data['pa_stats_player_id'] = get_post_meta($player->ID, 'pastats_player_id', true);
       
        if($return['tournaments']){
            $data['player_tournaments']  = self::get_player_info_tournament($player->ID, $tournament_id);
        }
        

        return $data;

    }    

    public static function get_player_entered_tournaments($player_id){

        $tournaments = p2p_type( 'tournament_players' )->set_direction( 'to' )->get_connected( $player_id );

        foreach($tournaments->posts as $tournament){
            $player_tournament = array();

            $player_tournament = tournamentCPT::tournament_return_format($tournament, array(), array('results' => false, 'prize' => false));
            $player_tournament['matches'] = self::get_player_tournament_matches($player_id, $tournament->ID);
            $player_tournament['player_result'] = self::get_player_tournament_finish($tournament->ID, $player_id);

            $enter_tournaments[] = $player_tournament;

        }

        return $enter_tournaments;

    }

    public static function get_player_info_tournament($player_id, $tournament_id){
        //$player_tournament = tournamentCPT::tournament_return_format($tournament, array(), array('results' => false, 'prize' => false));
        $player_tournament['matches'] = self::get_player_tournament_matches($player_id, $tournament_id);
        $player_tournament['player_result'] = self::get_player_tournament_finish($tournament_id, $player_id);

        return $player_tournament;
    }    

    public static function get_player_tournament_matches($player_id, $tournament_id){

        $tournament_object = new tournamentCPT();

        $tournament_challonge_id = $tournament_object->get_the_challonge_tournament_id($tournament_id);

        $player_matches = new WP_Query( array(
            'connected_type' => 'match_players',
            'connected_items' => $player_id,
            'meta_query' => array(
                array(
                    'key' => 'challonge_tournament_id',
                    'value' => $tournament_challonge_id
                )
            )
        ) );

        //allows us to add players to the match not sure if thats needed for now turning off
        p2p_type( 'match_players' )->each_connected( $player_matches, array(), playerCPT::$post_type );

        foreach($player_matches->posts as $match){

            $pMatch = array(
                'name' => $match->post_title,
                'pa_stats_match_id' => get_post_meta($match->ID, 'pa_stats_match_id', true),
                'challonge_match_id' => get_post_meta($match->ID, 'challonge_match_id', true),

            );

            foreach($match->player as $player){
                if($player->ID == $player_id){
                    $pMatch['winner'] = false;
                    if(p2p_get_meta($player->p2p_id, 'winner', true)){
                        $pMatch['winner'] = true;
                    }
                }
            }

            $matches[] = $pMatch;

        }

        return $matches;

    }

    public static function get_player_tournament_finish($tournament_id, $player_id){

        $player_tournament_end_place = 'UNRANKED';

        $tournament_prize_places = tournamentCPT::get_tournament_prizes($tournament_id);

        $tournament_results = tournamentCPT::get_tournament_winner_v2($tournament_id, array("posts.ID AS exodus_player_id"), array(), ' LIMIT ' . count($tournament_prize_places) );

        for($place = 1; $place <= count($tournament_prize_places); $place ++){

            if($tournament_results[($place-1)]->exodus_player_id == $player_id){

                $player_tournament_end_place = $place;

            }

        }

        return $player_tournament_end_place;

    }

    function add_news_caps_to_admin() {

        $roles = array(
            get_role('administrator')
        );

        $caps  = array(
            'read',
            'read_player',
            'read_private_players',
            'edit_players',
            'edit_private_players',
            'edit_published_players',
            'edit_others_players',
            'publish_players',
            'delete_players',
            'delete_private_players',
            'delete_published_players',
            'delete_others_players',
        );

        foreach ($roles as $role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
        
    }

    public function hide_new_player() {
        // Hide sidebar link
        global $submenu;

        if(!DW_Helper::is_site_administrator()){

            // Hide link on listing page
            if ((isset($_GET['post_type']) && $_GET['post_type'] == playerCPT::$post_type) || (isset($_GET['post']) && get_post_type($_GET['post']) == playerCPT::$post_type)) {
                echo '<style type="text/css"> #favorite-actions, .add-new-h2, .tablenav { display:none; } </style>';
            }

        }
    }

    public function disable_new_player(){

        if(!DW_Helper::is_site_administrator()){
            if ( get_current_screen()->post_type == playerCPT::$post_type )
                wp_die( "You ain't allowed to do that!" );

        }
    }

    public function change_player_object_label() {
        global $wp_post_types, $post;

        if (!DW_Helper::is_site_administrator()) {

            $current_user = wp_get_current_user();

            $player_profile_id = self::get_user_player_profile_id($current_user->ID);

            if ($_GET['post'] == $player_profile_id) {

                $labels = & $wp_post_types[playerCPT::$post_type]->labels;

                $labels->edit_item = 'Edit My Player Profile';

            }


        }
    }


    public function prefix_remove_menu_pages() {

        if(!DW_Helper::is_site_administrator()){

            remove_menu_page( 'edit.php?post_type=' . playerCPT::$post_type );

            $current_user = wp_get_current_user();

            $player_profile_id = self::get_user_player_profile_id($current_user->ID);

            if($player_profile_id){

                add_menu_page(
                    'My Player Profile',
                    'My Player Profile',
                    'read',
                    '/post.php?post='.$player_profile_id.'&action=edit',
                    '',
                    'dashicons-nametag',
                    15
                );

            }
        }
    }

    public static function get_user_player_profile_id($user_id){

        $player_id = get_user_meta($user_id, 'player_id', true);

        if(!empty($player_id)){
            return $player_id;
        }

        return false;

    }

    public function link_player_to_user($meta_id, $object_id, $meta_key, $_meta_value){

        if(get_post_type($object_id) == self::$post_type){

            if($meta_key == 'user_id'){

                //clean up old links before this value is updated
                $old_user_id = get_post_meta($object_id, 'user_id', true);

                delete_user_meta($old_user_id, 'player_id' , $object_id);

                if($_meta_value == null || $_meta_value == '' || $_meta_value == 'null'){

                    wp_update_post(array('ID' => $object_id, 'post_author' => 3));

                } else {

                    update_user_meta($_meta_value, 'player_id', $object_id);

                    //also need to set this to the post author so user can change there player profile.
                    wp_update_post(array('ID' => $object_id, 'post_author' => $_meta_value));
                }
            }
        }
    }

    public static function get_player_avatar($player_id, $size = 100){

        $player_user_id = get_post_meta($player_id, 'user_id', true);
        $user           = get_userdata($player_user_id);

        //delete_transient( 'player_' .$user->ID. '_avatar' );

        if ( false === ( $user_avatar_img = get_transient( 'player_' .$user->ID. '_avatar_' .$size ) ) ) {

            if (function_exists(get_wp_user_avatar())) {

                $image = get_wp_user_avatar_src($user->ID, $size);

                if ($image[1] < 200 || $image[2] < 200) {
                    $user_avatar_img = get_avatar($user->ID, $size);
                }

                $user_avatar_img = get_wp_user_avatar($user->ID, $size);
            } else {
                $user_avatar_img = get_avatar($user->ID, $size);
            }

            set_transient( 'player_' .$user->ID. '_avatar_' .$size, $user_avatar_img, 12 * HOUR_IN_SECONDS );
        }

        return $user_avatar_img;

    }

    public function player_missing_pa_stats_id(){

        check_ajax_referer( 'missing_pa_stats_id', 'security' );

        $player_id = $_POST['player_id'];

        do_action('player_missing_pa_stats_id', array( 'player_id' => $player_id));

        die();

    }

    function delete_user_caches( $user_id, $old_user_data ) {

        delete_transient( 'player_' .$user_id. '_avatar' );

    }
}
