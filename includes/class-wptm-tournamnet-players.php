<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WPTM_Tournament_Players
 */
class WPTM_Tournament_Players {


    /**
     * WPTM_Tournament_Players constructor.
     */
    public function __construct() {

        add_action( 'tournament_player_withdrawn', [ $this, 'player_reserve_to_active' ] );
        add_action( 'tournament_signup_Reserve', [ $this, 'reset_reserve_quote_position' ] );

        add_action( 'tournament_player_Reserve_to_Active', [ $this, 'reset_reserve_quote_position'], 10, 2 );

    }


    /**
     * @param $player_id
     * @param $tournament_id
     */
    public function player_reserve_to_active( $player_id, $tournament_id ) {

        //find next reserve player, with quote number of 1
        $tournament = new WPTM_Tournament_Helper($tournament_id);

        //fetch tournament players order by there queue
        $tournament_player = $tournament->get_tourament_players(['connected_orderby' => 'reserve_position', 'connected_meta' => ['key' => 'reserve_position', 'value' => 1] ], [tournamentCPT::$tournament_player_status[1]]);

        //set player with 1 queue to active
        p2p_update_meta( $tournament_player[0]->p2p_id, 'status', tournamentCPT::$tournament_player_status[0] );

        //reset all reserve players positions
        do_action('tournament_player_Reserve_to_Active', $player_id, $tournament_id);

        //hook for cache clear
        do_action( "tournament_state_change", $tournament_id );

    }


    /**
     * @param $player_id
     * @param $tournament_id
     */
    public static function reset_reserve_quote_position( $player_id, $tournament_id ) {

        $tournament = new WPTM_Tournament_Helper($tournament_id);
        $reserve_position = 1;

        $tournament_reserve_players = $tournament->get_tourament_players([ 'connected_orderby' => 'reserve_position' ], [tournamentCPT::$tournament_player_status[1]]);

        foreach($tournament_reserve_players as $player){

            p2p_update_meta( $player->p2p_id, 'reserve_position', $reserve_position);

            $reserve_position ++;

        }

    }



}