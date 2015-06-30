<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WPTM_Tournament_Helper
 */
class WPTM_Tournament_Helper {

    private $tournament_id;

    /**
     * @return mixed
     */
    public function get_tournament_id() {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function set_tournament_id($tournament_id) {
        $this->tournament_id = $tournament_id;
    }


    function __construct($tournmanet_id) {

        $this->set_tournament_id($tournmanet_id);

    }

    public function get_tourament_players( $args = [], $status = [ tournamentCPT::$tournament_player_status[0], tournamentCPT::$tournament_player_status[1] ] ){

        $players = get_posts( array_merge( [
            'connected_type'   => 'tournament_players',
            'connected_items'  => $this->get_tournament_id(),
            'connected_meta' => [
                [
                    'key' => 'status',
                    'value' => $status,
                    'compare' => 'IN'
                ]
            ],
            'nopaging'         => true,
            'suppress_filters' => false
        ], $args ) );

        return $players;

    }

    public function get_tournament_matches(){

        $matches = get_posts( [
            'connected_type'   => 'tournament_matches',
            'connected_items'  => $this->get_tournament_id(),
            'nopaging'         => true,
            'suppress_filters' => false
        ] );

        return $matches;

    }

    public function is_tournament_in_progress(){

        global $wpdb;

        $page_id = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'page_on_front'" );

        if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress-2.php'  || get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress.php')
            return true;

        return false;
    }

    /**
     * @return int
     */
    public static function is_tournament_in_progress_v2( $tournament_id = 0 ){

        $tournament_id = 0;

        return (int) $tournament_id;
    }


    /**
     * @param $tournament_id
     * @param $clan_tag
     * @return bool
     */
    public function is_tournament_clan_contact($tournament_id, $clan_tag){

        global $current_user;

        $player_id = $current_user->player_id;

        $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

        $clan_contact = p2p_get_meta( $p2p_id, 'clan_contact', true );
        $current_clan = get_current_user_clan();

        if ( $clan_contact == 1 && $current_clan === $clan_tag)
            return true;

        return false;

    }

    public function get_tournament_type($tournament_id){

        return get_post_meta($tournament_id, 'tournament_format', true);

    }

}
