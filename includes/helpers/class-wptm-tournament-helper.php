<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Tournament_Helper {

    private $tournament_id;

    /**
     * @return mixed
     */
    public function getTournamentId() {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function setTournamentId($tournament_id) {
        $this->tournament_id = $tournament_id;
    }


    function __construct($tournmanet_id) {

        $this->setTournamentId($tournmanet_id);

    }

    public function get_tourament_players(){

        $arg = array_merge(array(
            'connected_type'   => 'tournament_players',
            'connected_items'  => $this->getTournamentId(),
            'connected_meta' => array(
                array(
                    'key' => 'status',
                    'value' => $status,
                    'compare' => 'IN'
                )
            ),
            'nopaging'         => true,
            'suppress_filters' => false
        ), $args);

        $players = get_posts($arg);

        return $players;

    }

    public function get_tournament_matches(){

        $matches = get_posts(array(
            'connected_type'   => 'tournament_matches',
            'connected_items'  => $this->getTournamentId(),
            'nopaging'         => true,
            'suppress_filters' => false
        ));

        return $matches;

    }

    public function is_tournament_in_progress(){

        global $wpdb;

        $page_id = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'page_on_front'" );

        if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress-2.php'  || get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress.php')
            return true;

        return false;
    }

    public static function is_tournament_in_progress_v2(){

        $tourmanet_site_status = get_option('tourmanet_in_progress');



    }

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
