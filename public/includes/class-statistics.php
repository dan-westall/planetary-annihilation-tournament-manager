<?php

class statistic {

    var $team = array();
    var $players = array();

    protected $wpdb;

    function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function add_team($team = array()){

        $this->team[] = '';

    }

    public function add_player($player_id){

        $this->players[] = '';

    }

    public function get_matches(){

        $player_matches = p2p_type( 'match_players' )->set_direction( 'to' )->get_connected( $this->players[0] );

        $player_matches->posts[0];

        foreach($player_matches->posts as $match){

            $match_lengths[] = ( get_post_meta( $match->ID, 'pa_stats_stop', true ) - get_post_meta( $match->ID, 'pa_stats_start', true ) );

        }

        return $match_lengths;

    }

    public function quickest_match(){

        $matches = $this->get_matches();

        asort($matches);

        $match_time = array_pop($matches);

        printf(
            '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>',
            __('Longest Match'),
            '',
            ''
        );

    }

    public function longest_match(){

        $matches = $this->get_matches();

        asort($matches);

        $match_time = array_pop($matches);

        printf(
            '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>',
            __('Longest Match'),
            '',
            ''
        );

    }

    public function average_match(){

        $matches = $this->get_matches();

        foreach($matches as $match){

            $total_time += $match;

        }

        $match_average = ( $total_time / count( $matches ) );

        printf(
            '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>',
            __('Longest Match'),
            '',
            ''
        );

    }

    public function total_matches(){

        $matches = $this->get_matches();

        printf(
            '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>',
            __('Longest Match'),
            '',
            count($matches)
        );

    }

    public function total_wins(){



    }

    public function total_loses(){

    }

    public function average_win_rate(){

    }

    public function set_statistic(){


        if(count($this->players)){




        }

    }



}