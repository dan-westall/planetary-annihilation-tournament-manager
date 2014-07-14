<?php

class statistic {

    public $team = array();
    public $players = array();
    public $matches = array();

    function __construct() {
        global $wpdb;

    }

    public function add_team($team = array()){

        $this->team[] = '';

    }

    public function add_player($player_id){

        $this->players[] = $player_id;

    }

    public function get_matches(){

        global $wpdb;

        $query = $wpdb->prepare("SELECT p2p_from FROM $wpdb->p2p WHERE p2p_to = %d AND p2p_type = 'match_players' ORDER BY p2p_id", $this->players[0]);

        $player_matches = $wpdb->get_col($query);

        $this->matches = $player_matches;

    }

    public function quickest_match($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        $matches = $this->get_matches();

        foreach($player_matches->posts as $match){

            $match_lengths[] = ( get_post_meta( $match->ID, 'pa_stats_stop', true ) - get_post_meta( $match->ID, 'pa_stats_start', true ) );

        }

        asort($matches);

        $match_time = array_pop($matches);

        sprintf(
            $template,
            __('Longest Match'),
            '',
            ''
        );

    }

    public function longest_match($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        $matches = $this->get_matches();

        asort($matches);

        $match_time = array_pop($matches);

        return sprintf(
            $template,
            __('Longest Match'),
            '',
            ''
        );

    }

    public function average_match($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        $matches = $this->get_matches();

        foreach($matches as $match){

            $total_time += $match;

        }

        $match_average = ( $total_time / count( $matches ) );

        sprintf(
            $template,
            __('Longest Match'),
            '',
            ''
        );

    }

    public function total_matches($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        $matches = $this->get_matches();

        sprintf(
            $template,
            __('Total Match'),
            '',
            count($matches)
        );

    }

    public function finished_under($time = 720, $template = '<div><div></div><a href="%2$s"><span>%3$s</span></a></div>', $title = '%% of Matches finished within %s minutes'){

        global $wpdb;

        $matches_under = array();
        $classes = '';
        $matches       = $this->matches;
        $total_matches = count($matches);

        foreach($matches as $match_id){

            $match_time_mill = ( get_post_meta( $match_id, 'pa_stats_stop', true ) - get_post_meta( $match_id, 'pa_stats_start', true ) );
            $match_time =  round( ( $match_time_mill / 1000 ) );

            if($match_time <= $time){
                $matches_under[] = $match_id;
            }

        }

        $percentage = round( ( count( $matches_under ) / $total_matches ) * 100 );

        if(strlen($percentage) > 2){
            $classes = 'small-numbers';
        }

        return sprintf(
            $template,
            sprintf($title, ( $time / 60) ),
            '',
            $percentage,
            $classes
        );

    }

    public function total_wins(){



    }

    public function total_loses(){

    }

    public function longest_win_streak($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        $matches       = $this->matches;
        $win = 0;

        foreach($matches as $match_id){

            $p2p_id = p2p_type( 'match_players' )->get_p2p_id( $match_id, $this->players[0] );

            $result = p2p_get_meta( $p2p_id, 'winner', true );

            if($result){
                $win ++;
            } else {

                if($streak < $win){
                    $streak = $win;
                }

                $win = 0;

            }

        }

        return sprintf(
            $template,
            __('Longest Win streak'),
            '',
            $streak
        );


    }

    public function average_win_rate($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        $matches = $this->matches;
        $win     = $lose = 0;

        foreach ($matches as $match_id) {

            $p2p_id = p2p_type('match_players')->get_p2p_id($match_id, $this->players[0]);

            $result = p2p_get_meta($p2p_id, 'winner', true);

            if ($result) {
                $win ++;
            } else {
                $lose = 0;
            }

        }

        $ratio = round( ( $win / count($matches) ) * 100 );

//
        return sprintf(
            $template,
            __('Wins %'),
            '',
            $ratio
        );


    }

    public function set_statistic(){


        if(count($this->players)){




        }

    }



}

