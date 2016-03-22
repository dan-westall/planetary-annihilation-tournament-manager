<?php


namespace Helper\Tournaments;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WPTM_Tournament_Helper
 */
class WPTM_Tournaments {

    private $tournament_id;
    private $tournament_status;

    /**
     * @return mixed
     */
    public function get_tournament_id() {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function set_tournament_id( $tournament_id ) {
        $this->tournament_id = $tournament_id;

        return $this;
    }

    public function get_tournament_status() {

        $tournament_status = \tournamentCPT::$tournament_status[ get_post_meta( $this->get_tournament_id(), 'tournament_status', true) ];

        return $tournament_status;

    }

    public function set_tournament_status( $tournament_status ){

        update_post_meta( $this->get_tournament_id(), 'tournament_status', $tournament_status );

        return $this;

    }

    function __construct( ) {


    }

    public function get_tourament_players(  $args = [], $status = [] ){

        if( empty($status))
            $status = [ \tournamentCPT::$tournament_player_status[0], \tournamentCPT::$tournament_player_status[1] ];

        $args = array_merge_recursive( [
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
        ], $args );

        //move to wp_query
        $players = get_posts( $args );

        return $players;

    }

    public function get_tournament_matches(){

        $matches = new WP_Query([
            'connected_type'   => 'tournament_matches',
            'connected_items'  => $this->get_tournament_id(),
            'nopaging'         => true,
            'suppress_filters' => false
        ]);

        wp_reset_postdata();

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


    //todo this should move to a extended user object one day
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

    public function get_tournament_type(){

        return get_post_meta($this->get_tournament_id(), 'tournament_format', true);

    }

    public function get_tournament_status_id(){

        return apply_filters( 'wptm_tournament_status', get_post_meta( $this->get_tournament_id(), 'tournament_status', true), $this->get_tournament_id() );

    }

}

