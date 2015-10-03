<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Challonge {

    function __constructor(){



    }

    public function init() {

        $plugin = new self();

        add_action( 'wp_ajax_challonge_resync', [ $plugin, 'challonge_resync'] );

        add_action( 'wptm_widget_player_controls', [ $plugin, 'challonge_sync_ui'] );

        add_action( 'admin_enqueue_scripts',  [ $plugin, 'register_scripts'], 10 , 0 );

        add_action( 'add_meta_boxes', [ $plugin, 'register_meta_box' ] );

    }

    public static function register_scripts(){

        wp_register_script(
            'WPTM-Challonge-Sync', plugins_url( 'admin/assets/js/admin_challonge_sync.js', dirname(__FILE__)  ), array( 'jquery' ), WP_Tournament_Manager::VERSION
        );

    }

    public function challonge_resync($tournament_id){

        $tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : $tournament_id;
        $total_players = 0;

        check_ajax_referer( 'challonge-sync', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {

            return;

        }

        //if theres no challonge id exit function
        if(false === ($challonge_tournament_id  = tournamentCPT::get_the_challonge_tournament_id($tournament_id))){

            return false;

        }

        $challonge_api_key = WP_Tournament_Manager::fetch_challonge_API();

        $tournament_player_active = get_tournament_players(
            $tournament_id,
            [ tournamentCPT::$tournament_player_status[0] ],
            [
                'connected_orderby' => 'date',
                'connected_order' => 'ASC'
            ]
        );

        $tournament_player_reserve = get_tournament_players(
            $tournament_id,
            [ tournamentCPT::$tournament_player_status[1] ],
            [
                'connected_orderby' => 'reserve_position',
                'connected_order' => 'ASC',
                'connected_order_num' => true
            ]
        );

        try {

            $challonge = new ChallongeAPI($challonge_api_key);

            $challonge->verify_ssl = false;

            $participants = $challonge->getParticipants($challonge_tournament_id);

            //if false then challonge tournament is empty
            if ($participants !== false) {

                foreach ($participants as $participant) {

                    $challonge->deleteParticipant($challonge_tournament_id, $participant->id);

                }

            }

//            if( get_tournament_type( $tournament_id )  === 'standard') {

                if ( count( $tournament_player_active ) ) {

                    foreach ( $tournament_player_active as $player ) {

                        WPTM_Tournament_Signup::challonge_add_player_to_tournament( $tournament_id, $player->ID );

                        $total_players ++;

                    }

                }

                if ( count( $tournament_player_reserve ) ) {

                    foreach ( $tournament_player_reserve as $player ) {

                        WPTM_Tournament_Signup::challonge_add_player_to_tournament( $tournament_id, $player->ID );

                        $total_players ++;

                    }

                }

//            } else if (get_tournament_type( $tournament_id ) === 'teamarmies' ) {
//
//                $teams = array();
//
//                foreach ( $tournament_player_active as $player ) {
//
//                    $teams[ strtolower( p2p_get_meta( $player->p2p_id, 'team_name', true ) ) ][] = $player;
//
//                 }
//
//                foreach( $teams as $team_name => $team ) {
//
//
//
//                }
//
//            }

        } catch (Exception $e){

            do_action('challonge_error', $challonge_tournament_id, $tournament_id, $e);

            wp_send_json_error($e->getMessage());

        }

        wp_send_json_success( [ 'message' => sprintf( '%s Players added to challonge.', $total_players ) ] );

    }

    //todo this should be moved into a function that controls meta box's then this class should hook into a action for that meta box
    public function register_meta_box(){

        global $post;

        $tournament = new WPTM_Tournament_Helper( $post->ID );

        if( ! in_array( $tournament->get_tournament_status(), [ tournamentCPT::$tournament_status[0], tournamentCPT::$tournament_status[4] ] ) ) {

            return;

        }

        add_meta_box(

            'challonge-sync',

            __( 'Challonge Sync', 'wp-tournament-manager' ),

            [ $this, 'challonge_sync_ui' ],

            tournamentCPT::$post_type,

            'side',

            'core'

        );

    }

    public function challonge_sync_ui( $post ) {

        wp_enqueue_script('WPTM-Challonge-Sync');

        require_once WPTM_PLUGIN_DIR . '/admin/views/widget-challonge-sync.php';

    }
}