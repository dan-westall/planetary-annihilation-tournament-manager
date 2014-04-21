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

    //find if an existing Match exists ? 
    $match_id = 0;
    $qargs = array(
            'post_title'      => $match_name,
            'post_type'       => matchCPT::$post_type,
            'connected_type'  => 'posts_to_pages',
            'connected_items' => $args["wp_post_id"],
            'nopaging'        => true,
    );

    $connected = new WP_Query($qargs);

    if ($connected->have_posts()){
        while ( $connected->have_posts() ){
            $post = $connected->the_post();
            $match_id = $post->ID;
            break;
        }
        //update existing
        $update_match = array(
            'ID' => $match_id,
            'post_content' => 'This an Update'
            );
        wp_update_post( $update_match );
    }
    else{
        //create match 
        $new_match = array(
        'post_type'  => matchCPT::$post_type,
        'post_title' => $match_name,
        'post_name'  => $args["challonge_match_id"],
        'post_status' => 'publish',
        'post_content' => 'start'
        );
        $match_id = wp_insert_post($new_match);

        update_post_meta($match_id, 'challonge_match_id', $args["challonge_match_id"]);
        update_post_meta($match_id, 'challonge_tournament_id', $args["challonge_tournament_id"]);
        //update_post_meta($match_id, 'match_round', $args["match_round"]);
        
        $connection_meta = array(
            'date'                     => current_time('mysql'),
            'challonge_tournament_id'  => $args["challonge_tournament_id"]
        );

        //todo should be be able to link matches to matches so we can create chains? in future.
        $p2p_result = p2p_type('tournament_matches')->connect($args["wp_post_id"], $match_id, $connection_meta);        
    }

    //any other meta we need to attach to matches?????
    
    //update_post_meta($match_id, 'challonge_tournament_id', $args["challonge_tournament_id"]);
    //update_post_meta($match_id, 'pa_stats_match_id', $args["pa_stats_match_id"]);

    


    //todo should planets from the planetCPT be attach so on the match page we can show the planet....

    //attach players to this match
    /*
    foreach($args["players"] as $key => $player ){

        //team is simple int, for example if its a ffa each player team would just be a int in a series, if team play, 2 players would be team int 1 and 2 team int 2
        $connection_meta = array(
            'date'                     => current_time('mysql'),
            'challonge_tournament_id'  => $challonge_tournament_id,
            'team'                     => $player['team']
        );

        $wp_player_id  = get_player_by($player['pastats_id']);

        //todo add error if $wp_player_id comes back false or empty take steps either add the play to the system or return error message

        if($player['winner']){
            $connection_meta['winner'] = true;
        }

        $p2p_result = p2p_type('match_players')->connect($match_id, $wp_player_id, $connection_meta);

    }
    */

    return "match ". $args["match_letter"] ." added and got wp-id ". $match_id;

}