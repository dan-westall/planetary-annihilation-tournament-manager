<?php

add_filter( 'xmlrpc_methods', 'add_xml_rpc_methods' );

function add_xml_rpc_methods( $methods ) {
    $methods['pltm.addMatch'] = 'pltm_add_match';
    //$methods['pltm.addMatch'] = 'pltm_add_match';

    return $methods;
}

function pltm_add_match( $data ){
    global $wp_xmlrpc_server;
    $args = json_decode($data,true);

    //todo added more complex naming, for more than 1v1 and teams.. not sure on limit
    //$match_name = sprintf('%s vs %s', $args["player_1"], $args["player_2"]);
    $match_name = "Match " . $args["match_letter"];

    foreach($args['players'] as &$player){

        $wp_player_id = playerCPT::get_player_by($player['pa_stats_player_id'])->ID;

        $player['wp_player_id'] = $wp_player_id;

    }

    //find if an existing Match exists ?
    $match_id = 0;

    //name is unique
    $qargs = array(
        'name'           => $args["match_id"],
        'post_type'      => matchCPT::$post_type,
        'posts_per_page' => 1

    );

    //some times i find it easier to use get_posts for simple looks like this.
    $match = get_posts($qargs);
    //return Count($match);
    if (!empty($match)) {

        //because we are limiting to 1 and because name or aka post_name is unique

        $post     = $match[0];
        $match_id = $post->ID;

        foreach($args['players'] as &$player){

            //check connection
            $p2p_id = p2p_type('match_players')->get_p2p_id($match_id, $player['wp_player_id']);

            if(!$p2p_id) {

                $p2p_result = p2p_type('match_players')->connect($match_id, $player['wp_player_id'], array(
                    'date'                     => current_time('mysql'),
                    'team'                     => $player['team']
                ));

            }

        }

    } else {

        //create match
        $new_match = array(
            'post_type'    => matchCPT::$post_type,
            'post_title'   => $match_name,
            'post_status'  => 'publish',
            'post_content' => 'start'
        );

        //if match_id was provided but no match was found use it when creating match
        if(isset($args["match_id"])){
            $new_match['post_name'] = $args["match_id"];
        }

        $match_id  = wp_insert_post($new_match);

        //if missing match_id then native id to be used as match ID update post to reflect this
        if(!isset($args["match_id"])){

            wp_update_post( array(
                'ID'        => $match_id,
                'post_name' => $match_id
            ) );

        }

        if(!empty($args["challonge_match_id"])){
            update_post_meta($match_id, 'challonge_match_id', $args["challonge_match_id"]);
        }

        if(!empty($args["challonge_tournament_id"])){
            update_post_meta($match_id, 'challonge_tournament_id', $args["challonge_tournament_id"]);
        }

        $p2p_result = p2p_type('tournament_matches')->connect($args["wp_tournament_id"], $match_id, array(
            'date'                    => current_time('mysql'),
        ));

        foreach($args['players'] as &$player){

            $p2p_result = p2p_type('match_players')->connect($match_id, $player['wp_player_id'], array(
                'date'                     => current_time('mysql'),
                'team'                     => $player['team']
            ));

        }

    }

    update_post_meta($match_id, 'match_round', $args["match_round"]);
    update_post_meta($match_id, 'last_update', $args["last_update"]);
    update_post_meta($match_id, 'favourite', $args["favorite"]);
    update_post_meta($match_id, 'favouritepercent', $args["favoritepercent"]);

    if(!empty($args["twitch"])){
        update_post_meta($match_id, 'twitch', $args["twitch"]);
    }

    if(is_array($args["pastatsmatches"][0])){

        update_post_meta($match_id, 'pa_stats_match_id', $args["pastatsmatches"][0]["gameId"]);
        update_post_meta($match_id, 'pa_stats_start', $args["pastatsmatches"][0]["start"]);
        update_post_meta($match_id, 'pa_stats_stop', $args["pastatsmatches"][0]["end"]);

    }

    //make sure if the winner is a team then all team players have winner set
    $wining_team = array_column($args['players'], 'winner', 'team');

    foreach($args['players'] as &$player){

        //player is part of winning team
        if($wining_team[$player['team']]){

            $p2p_id = p2p_type('match_players')->get_p2p_id($match_id, $player['wp_player_id']);

            p2p_update_meta($p2p_id, 'winner', 1);

            do_action('match_winner_declared', $match_id, $player['wp_player_id']);

        } else {

            $p2p_id = p2p_type('match_players')->get_p2p_id($match_id, $player['wp_player_id']);

            p2p_update_meta($p2p_id, 'winner', 0);

            do_action('match_loser_declared', $match_id, $player['wp_player_id']);
        }

    }

    do_action('match_updated', $match_id);

    return "match ". $args["match_letter"] ." added and got wp-id ". $match_id;

}
