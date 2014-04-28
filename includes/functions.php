<?php

function get_tournament_players($tournament_id){

    $players = get_posts(array(
        'connected_type'   => 'tournament_players',
        'connected_items'  => $tournament_id,
        'nopaging'         => true,
        'suppress_filters' => false
    ));

    return $players;

}

function get_tournament_matches($tournament_id){
    $matches = get_posts(array(
        'connected_type'   => 'tournament_matches',
        'connected_items'  => $tournament_id,
        'nopaging'         => true,
        'suppress_filters' => false
    ));

    return $matches;

}

function is_tournament_signup_open($tournament_id){

    return tournamentCPT::is_tournament_signup_open($tournament_id);

}

class DW_Helper {

    public static function get_post_by_meta($meta_key, $meta_value){

        global $wpdb;

        $post_id = $wpdb->get_var($wpdb->prepare(
            "
                SELECT post_id
                FROM $wpdb->postmeta
                WHERE meta_value = %s AND meta_key = %s LIMIT 1
            ",
            $meta_value,
            $meta_key
        ));

        if($post_id)
            return get_post($post_id);

        return false;

    }

}

$DW_helper = new DW_Helper();