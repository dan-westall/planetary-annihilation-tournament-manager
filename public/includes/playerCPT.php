<?php

class playerCPT {

    public static $post_type = 'player';

    function __construct() {

        add_action('init', array($this, 'register_cpt_player'));

        //add_action( 'user_register', array( $this, 'action_new_player_profile' ) );
        add_action( 'after_setup_theme', array( $this, 'add_news_caps_to_admin') );

        add_action( 'after_setup_theme', array( $this, 'ctp_permission') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

//        //admin menu removed create new player for non admins
        add_action( 'admin_head',  array( $this, 'hide_new_player') );
        add_action( 'load-post-new.php',  array( $this, 'disable_new_player') );

//      change labels
        add_action( 'admin_init',   array( $this, 'change_player_object_label') );

        add_action( 'admin_menu', array( $this, 'prefix_remove_menu_pages') );

        add_action( 'update_post_meta', array( $this, 'link_player_to_user'), 10, 4 );

        add_action( 'wp_ajax_player_missing_pa_stats_id', array($this,'player_missing_pa_stats_id'));

        add_action( 'profile_update', array( $this, 'delete_user_caches'), 10, 2 );


        //shortcut for pastats_id to player id
        add_action( 'pre_get_posts', array( $this, 'pastats_converstion'), 10, 2 );
        add_filter( 'query_vars', array( $this, 'pastats_queryvars'), 10, 1 );

        add_filter( 'json_prepare_post',  array( $this, 'extend_json_api' ), 100, 3 );

        add_action( 'template_redirect',  array( $this, 'pastats_player_id_to_profile' ), 100, 3 );

        add_action( 'pre_get_posts', array( $this, 'archive_pagination_limit' ), 10, 1);
        //add_action( 'posts_fields', array( $this, 'extend_player_object'), 10, 100 );

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
            'has_archive'         => 'players',
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array('title', 'author'),
            'capability_type'     => array(self::$post_type,self::$post_type.'s'),
            'map_meta_cap'        => true,
        );

        register_post_type( self::$post_type, $playerArgs );

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

    public function register_p2p_connections() {



    }

    public static function new_player_profile($user_id, $values, $tournament_id){

        // Insert the post into the database

        $player_profile_args = [
            'post_title'  => wp_strip_all_tags($values['inGameName']),
            'post_name'   => wp_unique_post_slug(sanitize_title($values['inGameName'])),
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_type'   => playerCPT::$post_type
        ];

        $player_id = wp_insert_post($player_profile_args, true);

        if(!is_wp_error($player_id)) {

            //malformed user profile check
            if(get_the_title($player_id) != wp_strip_all_tags($values['inGameName']) || get_the_title($player_id) == get_the_title($tournament_id) ){

                $player_profile_args['new_user_id'] = $player_id;
                $player_profile_args['POST_ARRAY'] = $_POST;

                $_POST['signup_data']['player_profile_args'] = $player_profile_args;

                throw new Exception('Malformed user profile created, Signup process halted, Staff have been notifed.');

            }

            update_post_meta($player_id, 'player_email', $values['email']['value']);
            update_post_meta($player_id, 'user_id', $user_id);

            update_user_meta($user_id, 'player_id', $player_id);

            do_action('new_player_profile');

        }

        return $player_id;

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

            if (isset($_GET['post']) && $_GET['post'] == $player_profile_id) {

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

    public static function get_player_avatar($player_id = 0, $size = 'player-profile-thumbnail', $user_id = 0){

        //todo i hate this function!

        $default_avatar    = 1886;

        $player_user_id    = get_post_meta($player_id, 'user_id', true);
        $user              = $player_user_id ? get_userdata($player_user_id) : get_userdata($user_id);
        $transient_key     = sprintf('player_%s_avatar_%s', $player_id, $size);
        $logged_in_user_id = get_current_user_id();

        if(!function_exists('get_wp_user_avatar')) {

            if (is_user_logged_in() && DW_Helper::is_site_administrator())
                delete_transient($transient_key);

            if (false === ($user_avatar_img = get_transient($transient_key))) {

                if (($player_user_id == 'null' || $player_user_id == false) && $player_id !== 0) {
                    $user_avatar_img = wp_get_attachment_image($default_avatar, $size);
                } else {

                    if ('' === ($user_avatar_id = get_user_meta($user->ID, 'wp_user_avatar', true))) {
                        $user_avatar_img = wp_get_attachment_image($default_avatar, $size);
                    } else {
                        $user_avatar_img = wp_get_attachment_image($user_avatar_id, $size);
                    }

                }

                set_transient($transient_key, $user_avatar_img, 1 * HOUR_IN_SECONDS);
            }

        } else {

            return get_wp_user_avatar($user->ID, $size);

        }

        return $user_avatar_img;

    }


    public static function get_player_avatar_src($player_id = 0, $size = 'player-profile-thumbnail', $user_id = 0){

        $default_avatar    = 1886;

        $player_user_id    = get_post_meta($player_id, 'user_id', true);
        $user              = $player_user_id ? get_userdata($player_user_id) : get_userdata($user_id);
        $transient_key     = sprintf('player_%s_avatar_%s_src', $player_id, $size);
        $logged_in_user_id = get_current_user_id();

        if(!function_exists('get_wp_user_avatar')) {


            if(is_user_logged_in() && DW_Helper::is_site_administrator())
            delete_transient( $transient_key );

            if ( false === ( $user_avatar_img = get_transient( $transient_key ) ) ) {

                if(($player_user_id == 'null' || $player_user_id ==  false) && $player_id !== 0) {
                    $user_avatar_img = wp_get_attachment_image_src($default_avatar, $size);
                } else {

                    if (function_exists('get_wp_user_avatar')) {
                        $user_avatar_img = get_wp_user_avatar($user->ID, $size);
                    } else {

                        if('' === ($user_avatar_id = get_user_meta($user->ID, 'wp_user_avatar', true))){
                            $user_avatar_img = wp_get_attachment_image_src($default_avatar, $size);
                        } else {
                            $user_avatar_img = wp_get_attachment_image_src($user_avatar_id, $size);
                        }
                    }

                }

                set_transient( $transient_key, $user_avatar_img, 1 * HOUR_IN_SECONDS );
            }

        } else {

            return get_wp_user_avatar_src($user->ID, $size);

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
        delete_transient( 'player_' .$user_id. '_avatar_player-profile-thumbnail' );
        delete_transient( 'player_' .$user_id. '_avatar_small-player-profile-thumbnail' );

    }

    public function pastats_converstion($query){

        if($query->get('pastats_player_id')){
            $meta_query = array(
                array(
                    'key'   => 'pastats_player_id',
                    'value' => $query->get('pastats_player_id')
                )
            );

            $query->set( 'meta_query', $meta_query);
            $query->set( 'posts_per_page', 1 );

        }

    }

    public function pastats_queryvars($qvars){
        $qvars[] = 'pastats_player_id';
        return $qvars;
    }

    public function extend_json_api($_post, $post, $context){

        global $wp_query;

        if($post['post_type'] == self::$post_type){

            $remove_fields = array('status', 'date', 'modified', 'comment_status', 'date_tz', 'date_gmt', 'modified_tz', 'modified_gmt', 'author', 'parent', 'format', 'slug', 'guid', 'excerpt', 'menu_order', 'ping_status', 'sticky', 'content', 'category', 'post_excerpt', 'tags_input', 'terms');

            //dont need author
            foreach($remove_fields as $field){
                unset($_post[$field]);

            }

            unset($_post['meta']['links']);

            $_post['meta']['clan']        = get_post_meta($post['ID'], 'clan', true);

            if ( false === ( $win_percentage = get_transient( 'statistic_win_percentage_' .$post['ID']. '_api'  ) ) ) {

                $statistic = new statistic();
                $statistic->add_player($post['ID']);
                $statistic->get_matches();

                $win_percentage = $statistic->average_win_rate('%3$s');

                set_transient( 'statistic_win_percentage_' .$post['ID']. '_api', $win_percentage, ( HOUR_IN_SECONDS / 1 ) );

            }

            $_post['meta']['statistic']['wins_percentage']        = (int) $win_percentage;

            if(isset($_GET['filter']['tournament_players'])){
                $_post['meta']['statistic']['tournament_plays']        = 0;
            }

            if($pa_stats_id = get_post_meta($post['ID'], 'pastats_player_id', true)){
                $_post['meta']['pastats_player_id'] = $pa_stats_id;
            }

            if(get_post_meta($post['ID'], 'user_id', true)){

                delete_transient('player_user_avatar_' .$post['ID']. '_src');

                if ( false === ( $avatar_src = get_transient( 'player_user_avatar_' .$post['ID']. '_src'  ) ) ) {

                    $avatar_src = get_wp_user_avatar_src($post->user_id, 'medium-player-profile-thumbnail');

                    set_transient( 'player_user_avatar_' .$post['ID']. '_src', $avatar_src, ( HOUR_IN_SECONDS / 1 ) );

                }

            }

            $_post['avatar'] = $avatar_src;

        }

        return $_post;

    }

    public function pastats_player_id_to_profile(){

        if(isset($_GET['pastats_player_id']) && !empty($_GET['pastats_player_id'])){

            $player = DW_Helper::get_post_by_meta('pastats_player_id', $_GET['pastats_player_id']);

            if($player){

                wp_redirect(get_permalink($player->ID));

                exit;

            } else {

                wp_redirect('http://pastats.com/player?player='.$_GET['pastats_player_id']);

                exit;

            }

        }

    }

    public function extend_player_object($fields, $query){

        global $wpdb;

        if($query->query_vars['post_type'][0] == self::$post_type && $query->is_main_query()){

            $new_fields[]  = "(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $wpdb->posts.ID AND meta_key = 'clan') AS clan";
            $new_fields[]  = "IFNULL((SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $wpdb->posts.ID AND meta_key = 'user_id'), 0) AS user_id";

            $fields .= ', '.implode(', ', $new_fields);
        }

        return $fields;
    }

    public static function archive_pagination_limit($wp_query){

        if ($wp_query->is_main_query() && $wp_query->is_archive()){
            if($wp_query->query_vars['post_type'] == playerCPT::$post_type){
                $wp_query->set('posts_per_page', 45);
                $wp_query->set('orderby', 'title');
                $wp_query->set('order', 'ASC');
            }
        }

        return $wp_query;

    }

}
