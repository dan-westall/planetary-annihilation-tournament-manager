<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Player_Helper {

    private $player_id;

    /**
     * @return mixed
     */
    public function get_player_id() {
        return $this->player_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function set_player_id($player_id) {
        $this->player_id = $player_id;
    }


    function __construct($player_id) {

        $this->set_player_id($player_id);

    }

    public function has_pa_stats_id(){

        if( true == ( $pa_stats_id = get_post_meta($this->get_player_id(), 'pastats_player_id', true) ) )
            return $pa_stats_id;

        return false;

    }

}
