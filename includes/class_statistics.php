<?php

class statistic {

    public $team = array();
    public $players = array();
    public $matches = array();

    private $cache;

    function __construct($cache = false) {
        global $wpdb;

        $this->cache = $cache;

    }

    public function add_team($team = array()){

        $this->team[] = '';

    }

    public function add_player($player_id){

        $this->players[] = $player_id;

    }

    public function get_matches(){

        global $wpdb;

        //$query = $wpdb->prepare("SELECT p2p_from FROM $wpdb->p2p WHERE p2p_to = %d AND p2p_type = 'match_players' ORDER BY p2p_id", $this->players[0]);
        //only matches with start stop time
        $query = $wpdb->prepare("SELECT p2p_from FROM $wpdb->p2p AS p2p WHERE p2p_to = %d AND p2p_type = 'match_players' AND (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p.p2p_from AND meta_key = 'pa_stats_start') != '' AND (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p.p2p_from AND meta_key = 'pa_stats_stop') != '' ORDER BY p2p_id", $this->players[0]);

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

        return sprintf(
            $template,
            __('Total Match'),
            '',
            count($this->matches)
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

        if(count($matches > 0)){

            foreach ($matches as $match_id) {

                $p2p_id = p2p_type('match_players')->get_p2p_id($match_id, $this->players[0]);

                $result = p2p_get_meta($p2p_id, 'winner', true);

                if ($result) {
                    $win ++;
                } else {
                    $lose = 0;
                }

            }

        } else {
            $matches = 0;
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



    public function get_tournament_total($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;

        if(empty($this->players)){
            $query = $wpdb->prepare(
                "
                    SELECT
                        count(post.ID) as total_tournaments
                          FROM $wpdb->posts as post WHERE post_type = 'tournament' AND post_status = 'publish'
                    ",
                ''
            );
        } else {
            $query = $wpdb->prepare(
                "
                    SELECT
                        count(Distinct p2p_from) as total_tournaments
                          FROM $wpdb->p2p as p2p WHERE p2p_type = 'tournament_players' AND p2p_to IN ('".implode("', '", $this->players)."')
                    ",
                ''
            );
        }

        if(true)
            delete_transient(__FUNCTION__);

        if ( false === ( $totals = get_transient( __FUNCTION__ ) ) ) {

            $totals = $wpdb->get_row( $query );

            set_transient( __FUNCTION__ , $totals, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Total Tournaments on record'),
            'javascript:void(0);',
            $totals->total_tournaments
        );
    }


    public function set_statistic(){


        if(count($this->players)){




        }

    }


    //dream of doing $statistic->site->match->play->time->average($template);

    public function site_match_average_time($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;

        if ( false === ( $average = get_transient( __FUNCTION__ ) ) ) {

            $query = $wpdb->prepare(
                "
                    SELECT
                        count(post.ID),
                        SEC_TO_TIME(AVG(((SELECT meta_value FROM wp_postmeta WHERE post_id = post.ID AND meta_key = 'pa_stats_stop') - (SELECT meta_value FROM wp_postmeta WHERE post_id = post.ID AND meta_key = 'pa_stats_start')))/1000) AS duration
                          FROM $wpdb->posts as post WHERE post_type = 'match' AND (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = post.ID AND meta_key = 'pa_stats_stop') > 0 AND (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = post.ID AND meta_key = 'pa_stats_start') > 0
                    ",
                ''
            );

            $average = $wpdb->get_row( $query );

            set_transient( __FUNCTION__ , $average, 12 * HOUR_IN_SECONDS );
        }

        $time = new DateTime($average->duration);

        return sprintf(
            $template,
            __('Avg Match Time'),
            'javascript:void(0);',
            $time->format('i'),
            'Mins'
        );

    }

    //dream of doing $statistic->site->match->total($template);

    public function site_match_total($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;


        if ( false === ( $totals = get_transient( __FUNCTION__ ) ) ) {


            $query = $wpdb->prepare(
                "
                    SELECT
                        count(post.ID) as total_matches
                          FROM $wpdb->posts as post WHERE post_type = 'match'
                    ",
                ''
            );

            $totals = $wpdb->get_row( $query );


            set_transient( __FUNCTION__ , $totals, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Total Tournament Matches'),
            'javascript:void(0);',
            $totals->total_matches
        );
    }

    public function site_tournament_total($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;


        if ( false === ( $totals = get_transient( __FUNCTION__ ) ) ) {

            $query = $wpdb->prepare(
                "
                    SELECT
                        count(post.ID) as total_tournaments
                          FROM $wpdb->posts as post WHERE post_type = 'tournament'
                    ",
                ''
            );

            $totals = $wpdb->get_row( $query );

            set_transient( __FUNCTION__ , $totals, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Total Tournaments on record'),
            'javascript:void(0);',
            $totals->total_tournaments
        );
    }

    public function site_average_players_per_tournament($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;

        $total_tournaments_players = 0;

        if ( false === ( $average_players_per_tournament = get_transient( __FUNCTION__ ) ) ) {

            $query = $wpdb->prepare(
                "
                    SELECT
                        post_title AS tournament,
                        count(p2p_to) AS players
                                              FROM $wpdb->p2p LEFT JOIN $wpdb->posts AS post ON p2p_from = post.ID WHERE p2p_type = 'tournament_players' GROUP BY p2p_from
                    ",
                ''
            );

            $tournament_player_totals = $wpdb->get_results( $query );

            $total_tournaments = count($tournament_player_totals);

            foreach($tournament_player_totals as $tournament_totals){
                $total_tournaments_players += $tournament_totals->players;
            }

            $average_players_per_tournament = ceil($total_tournaments_players/$total_tournaments);

            set_transient( __FUNCTION__ , $average_players_per_tournament, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Avg players per tournament'),
            'javascript:void(0);',
            $average_players_per_tournament
        );
    }

    public function site_average_matches_per_tournament($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;

        $total_tournaments_players = 0;

        if ( false === ( $average_players_per_tournament = get_transient( __FUNCTION__ ) ) ) {

            $query = $wpdb->prepare(
                "
                    SELECT
                        post_title AS tournament,
                        count(p2p_to) AS players
                                              FROM $wpdb->p2p LEFT JOIN $wpdb->posts AS post ON p2p_from = post.ID WHERE p2p_type = 'tournament_matches' GROUP BY p2p_from
                    ",
                ''
            );

            $tournament_player_totals = $wpdb->get_results( $query );

            $total_tournaments = count($tournament_player_totals);

            foreach($tournament_player_totals as $tournament_totals){
                $total_tournaments_players += $tournament_totals->players;
            }

            $average_players_per_tournament = ceil($total_tournaments_players/$total_tournaments);

            set_transient( __FUNCTION__ , $average_players_per_tournament, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Avg matches per tournament'),
            'javascript:void(0);',
            $average_players_per_tournament
        );
    }

    public function site_tournaments_match_average_time($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;

        $total_tournaments_players = 0;

        if ( false === ( $average_players_per_tournament = get_transient( __FUNCTION__ ) ) ) {

            $query = $wpdb->prepare(
                "
                SELECT
                    COUNT(p2p_to) as match_total,
                    ceil(SUM(SEC_TO_TIME(((SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'pa_stats_stop') - (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'pa_stats_start'))/1000))/60) AS duration
                        FROM $wpdb->p2p WHERE p2p_type = 'tournament_matches' AND (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'pa_stats_stop') > 0 AND (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'pa_stats_start') > 0 GROUP BY p2p_from
                    ",
                ''
            );

            $tournament_player_totals = $wpdb->get_results( $query );

            $total_tournaments = count($tournament_player_totals);

            foreach($tournament_player_totals as $tournament_totals){
                $total_tournaments_players += $tournament_totals->duration;
            }

            $average_players_per_tournament = ceil($total_tournaments_players/$total_tournaments);

            set_transient( __FUNCTION__ , $average_players_per_tournament, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Avg Tournament play time'),
            'javascript:void(0);',
            ($average_players_per_tournament/60),
            'Hrs'
        );

    }


    public function site_tournaments_longest_match_average($template = '<div><div>%1$s</div><a href="%2$s"><span>%3$s</span></a></div>'){

        global $wpdb;

        $total_tournaments_players = 0;


        if ( false === ( $tournament_player_totals = get_transient( __FUNCTION__ ) ) ) {

            $query = $wpdb->prepare(
                "
            SELECT
                COUNT(p2p_to) as match_total,
                ceil(SUM(SEC_TO_TIME(((SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'pa_stats_stop') - (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'pa_stats_start'))/1000))/60) AS duration
                    FROM $wpdb->p2p WHERE p2p_type = 'tournament_matches' GROUP BY p2p_from ORDER BY duration DESC
                ",
                ''
            );

            $tournament_player_totals = $wpdb->get_results( $query );

            set_transient( __FUNCTION__ , $tournament_player_totals, 12 * HOUR_IN_SECONDS );
        }

        return sprintf(
            $template,
            __('Longest Event play time'),
            'javascript:void(0);',
            ($tournament_player_totals[0]->duration/60),
            'Hrs'
        );

    }


}

