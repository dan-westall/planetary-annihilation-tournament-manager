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
    $wp_player_id1  = playerCPT::get_player_by($args['player_1_pastats_id'])->ID;
    $wp_player_id2  = playerCPT::get_player_by($args['player_2_pastats_id'])->ID;
    $connection_meta1 = array(
        'date'                     => current_time('mysql'),
        'challonge_tournament_id'  => $challonge_tournament_id,
        'team'                     => 1
    );
    $connection_meta2 = array(
        'date'                     => current_time('mysql'),
        'challonge_tournament_id'  => $challonge_tournament_id,
        'team'                     => 2
    );    
    
    //find if an existing Match exists ? 
    $match_id = 0;

    //name is unique
    $qargs = array(
        'name'           => $args["challonge_match_id"],
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

        //update existing
        $update_match = array(
            'ID'           => $match_id,
            'post_content' => 'This an Update'
        );
        wp_update_post($update_match);

        $p2pplayer1 = p2p_type('match_players')->get_p2p_id($match_id, $wp_player_id1);
        $p2pplayer2 = p2p_type('match_players')->get_p2p_id($match_id, $wp_player_id2);
        //return $p2pplayer1 . " " . $p2pplayer2;
        if($p2pplayer1 == ""){
            $p2p_result1 = p2p_type('match_players')->connect($match_id, $wp_player_id1, $connection_meta1);
        }
        if($p2pplayer2 == ""){
            $p2p_result2 = p2p_type('match_players')->connect($match_id, $wp_player_id2, $connection_meta2);
        }        

    } else {

        //create match 
        $new_match = array(
            'post_type'    => matchCPT::$post_type,
            'post_title'   => $match_name,
            'post_name'    => $args["challonge_match_id"],
            'post_status'  => 'publish',
            'post_content' => 'start'
        );

        $match_id  = wp_insert_post($new_match);

        update_post_meta($match_id, 'challonge_match_id', $args["challonge_match_id"]);
        update_post_meta($match_id, 'challonge_tournament_id', $args["challonge_tournament_id"]);
        //update_post_meta($match_id, 'match_round', $args["match_round"]);

        $connection_meta = array(
            'date'                    => current_time('mysql'),
            'challonge_tournament_id' => $args["challonge_tournament_id"]
        );

        //todo should be be able to link matches to matches so we can create chains? in future.
        $p2p_result = p2p_type('tournament_matches')->connect($args["wp_post_id"], $match_id, $connection_meta);
        //team is simple int, for example if its a ffa each player team would just be a int in a series, if team play, 2 players would be team int 1 and 2 team int 2

        $p2p_result1 = p2p_type('match_players')->connect($match_id, $wp_player_id1, $connection_meta1);
        $p2p_result2 = p2p_type('match_players')->connect($match_id, $wp_player_id2, $connection_meta2);        
    }
  
    foreach($args["pastatsmatches"] as $key => $pamatch){
        update_post_meta($match_id, 'pa_stats_match_id', $pamatch["gameId"]);
        update_post_meta($match_id, 'pa_stats_start', $pamatch["start"]);
        if($pamatch["winner"] != ''){
            $winner_id = playerCPT::get_player_by($pamatch["winner"])->ID;
            $p2pwinner = p2p_type('match_players')->get_p2p_id($match_id, $winner_id); 
            p2p_update_meta($p2pwinner, 'winner', true);
            if($args["winner"] === $args["player_1_pastats_id"]){
                $loser_id = playerCPT::get_player_by($args["player_2_pastats_id"])->ID;
                $p2ploser = p2p_type('match_players')->get_p2p_id($match_id, $loser_id); 
                p2p_update_meta($p2ploser, 'winner', false);
            }
            else{
                $loser_id = playerCPT::get_player_by($args["player_1_pastats_id"])->ID;
                $p2ploser = p2p_type('match_players')->get_p2p_id($match_id, $loser_id); 
                p2p_update_meta($p2ploser, 'winner', false);
            }

        }
        //return $key . " " . $pamatch["winner"];
        break;
    }

    return "match ". $args["match_letter"] ." added and got wp-id ". $match_id;
}