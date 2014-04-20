<?php

add_filter( 'xmlrpc_methods', 'add_xml_rpc_methods' );

function add_xml_rpc_methods( $methods ) {

    $methods['frs.helloWorld'] = 'hello_world';
    $methods['pltm.addMatch'] = 'pltm_add_match';

    return $methods;
}

function hello_world( $params ) {

    global $wp_xmlrpc_server;

    $blog_id  = (int) $params[0]; // not used, but follow in the form of the wordpress built in XML-RPC actions
    $username = $params[1];
    $password = $params[2];
    $args     = $params[3];

    // verify credentials
    if ( ! $wp_xmlrpc_server->login( $username, $password ) ) {
        return $wp_xmlrpc_server->error;
    }

    // check for edit_posts capability (requires contributor role)
    // (obviously not required for this simple example, but just for demonstration purposes)
    if ( ! current_user_can( 'edit_posts' ) )
        return new IXR_Error( 403, __( 'You are not allowed access to details about orders.' ) );

    do_action( 'xmlrpc_call', 'frs.helloWorld' ); // patterned on the core XML-RPC actions

    // required parameter
    if ( ! isset( $args['name'] ) ) return new IXR_Error( 500, __( "Missing parameter 'name'" ) );

    // return success
    return "Hello " . $args['name'] . "!";
}




function pltm_add_match( $data ){
    global $wp_xmlrpc_server;
    $args = json_decode($data,true);

    //todo added more complex naming, for more than 1v1 and teams.. not sure on limit
    $match_name = sprintf('%s vs %s', $args["player_1"], $args["player_2"]);

    $new_match = array(
        'post_type'  => matchCPT::$post_type,
        'post_title' => $match_name,
        'post_name'  => $args["challonge_match_id"]
    );

    $match_id = wp_insert_post($new_match);

    //any other meta we need to attach to matches?????
    update_post_meta($match_id, 'challonge_match_id', $args["challonge_match_id"]);
    update_post_meta($match_id, 'challonge_tournament_id', $args["challonge_tournament_id"]);
    update_post_meta($match_id, 'pa_stats_match_id', $args["pa_stats_match_id"]);

    //todo might be wise to add round? or should we write a function to work it out
    $connection_meta = array(
        'date'                     => current_time('mysql'),
        'challonge_tournament_id'  => $args["challonge_tournament_id"]
    );

    //todo should be be able to link matches to matches so we can create chains? in future.
    $p2p_result = p2p_type('tournament_matches')->connect($args["wp_post_id"], $match_id, $connection_meta);

    //todo should planets from the planetCPT be attach so on the match page we can show the planet....

    //attach players to this match

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

    return "match added";

}



