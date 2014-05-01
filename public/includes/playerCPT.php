<?php

class playerCPT {

    public static $post_type = 'player';

    function __construct() {

        add_action('init', array($this, 'register_cpt_player'));

        add_action( 'user_register', array( $this, 'action_new_player_profile' ) );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

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
            'exclude_from_search' => true,
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array('title')
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

        }

        return $player;

    }

    public static function get_player($attr) {

        extract(shortcode_atts(array(
            'player_id' => '',
            'by' => '',
            'output'        => 'html'
        ), $attr));

        $player = self::get_player_by($player_id);

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

        $data['name']               = $player->post_title;
        $data['clan']               = get_post_meta($player->ID, 'clan', true);
        $data['pa_stats_player_id'] = get_post_meta($player->ID, 'pa_stats_player_id', true);

        if($return['tournaments']){
            $data['player_tournaments']  = self::get_player_entered_tournaments($player->ID);
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
}