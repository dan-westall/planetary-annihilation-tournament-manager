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

function get_the_tournament_endpoint(){

    global $wp_query, $post;

    foreach(Planetary_Annihilation_Tournament_Manager::$endpoints as $endpoint){

        if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

            return "$post->post_type-$endpoint";

        }
    }

    return tournamentCPT::$post_type;
}

function is_tournament_signup_open($tournament_id){

    return tournamentCPT::is_tournament_signup_open($tournament_id);

}

function get_match_player_cards($match_id){

    return matchCPT::match_up(array( 'match_id' => $match_id ));

}

function is_tournament_in_progress(){

    $page_id = get_option('page_on_front');

    if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress.php' )
        return true;

    return false;
}

function get_player_avatar($player_id, $size = 100){

     return playerCPT::get_player_avatar($player_id, $size);

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

    public static function is_site_administrator(){

        global $current_user;

        if (!empty($current_user->roles)) {
            foreach ($current_user->roles as $key => $value) {
                if ($value == 'administrator') {
                    return true;
                }
            }
        }

        return false;

    }

}

$DW_helper = new DW_Helper();