<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Match_Helper {

    private $match_id;

    /**
     * @return mixed
     */
    public function get_tournament_id() {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function set_match_id($match_id) {
        $this->tournament_id = $match_id;
    }


    function __construct($match_id) {

        $this->set_match_id($match_id);

    }


}
