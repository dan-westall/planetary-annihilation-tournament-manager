<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Challonge {

    function __constructor(){



    }

    public function init() {

        $plugin = new self();

        add_action( 'wp_ajax_reset_tournament', [ $plugin, 'reset_tournament'] );
        add_action( 'wp_ajax_nopriv_reset_tournament',  [ $plugin, 'reset_tournament' ] );

    }

    public function reset_tournament($tournament_id){

        check_ajax_referer( 'reset-tournament' );

        if ( ! current_user_can( 'manage_options' ) ) {

            return;

        }

        //if theres no challonge id exit function
        if(false === ($challonge_tournament_id  = tournamentCPT::get_the_challonge_tournament_id($tournament_id))){

            return false;

        }

        $challonge_api_key = WP_Tournament_Manager::fetch_challonge_API();

        $tournament_player = get_tournament_players($tournament_id, array(tournamentCPT::$tournament_player_status[0], tournamentCPT::$tournament_player_status[1]));

        try {

            $challonge = new ChallongeAPI($challonge_api_key);

            $challonge->verify_ssl = false;

            $participants = $challonge->getParticipants($challonge_tournament_id);

            foreach( $participants as $participant ) {

                $challonge->deleteParticipant($challonge_tournament_id, $participant->id);

            }

            foreach ( $tournament_player as $player ) {


                
            }



        } catch (Exception $e){

            do_action('challonge_error', $challonge_tournament_id, $tournament_id, $e);


            wp_send_json_error($e->getMessage());

        }

        wp_send_json_success();

    }

    public function register_meta_box(){

        global $post;

        $tournament = new WPTM_Tournament_Helper( $post->ID );

        if( !in_array( $tournament->get_tournament_status(), [ tournamentCPT::$tournament_status[0], tournamentCPT::$tournament_status[4] ] ) ) {
            return;
        }

        add_meta_box(
            'generate_match_submit',

            __( 'Match Generation',
                'wp-tournament-manager' ),
            [ $this, 'generate_match_submit' ],
            tournamentCPT::$post_type, 'side', 'core' );

    }

    public function generate_match_submit( $post ) {

        wp_enqueue_script('WPTM-Match-Generator');

        require_once WPTM_PLUGIN_DIR . '/admin/views/widget-match-generator.php';

    }
}