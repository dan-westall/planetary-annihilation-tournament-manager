<?php

function get_tournament_players($tournament_id, $status = array('active')){

    $players = get_posts(array(
        'connected_type'   => 'tournament_players',
        'connected_items'  => $tournament_id,
        'connected_meta' => array(
            array(
                'key' => 'status',
                'value' => $status,
                'compare' => 'IN'
            )
        ),
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

function get_match_commentators($match_id){

    $commentators = get_users( array(
        'connected_type' => 'match_commentators',
        'connected_items' => $match_id
    ) );

    foreach($commentators as $commentator){

        $commentators_str[] = $commentator->display_name;

    }

    return implode(', ', $commentators_str);

}



class DW_Helper {

    public static function get_post_by_meta($meta_key, $meta_value){

        global $wpdb;

        $statment = $wpdb->prepare(
            "
                SELECT post_id
                FROM $wpdb->postmeta
                WHERE meta_value = %s AND meta_key = %s LIMIT 1
            ",
            $meta_value,
            $meta_key
        );

        $post_id = $wpdb->get_var($statment);

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

    public static function generate_post_select($select_id, $post_type, $selected = 0) {
        $post_type_object = get_post_type_object($post_type);
        $label = $post_type_object->label;
        $posts = get_posts(array('post_type'=> $post_type, 'post_status'=> 'publish', 'suppress_filters' => false, 'posts_per_page'=>-1));
        echo '<select name="'. $select_id .'" id="'.$select_id.'">';
        echo '<option value = "" >All '.$label.' </option>';
        foreach ($posts as $post) {
            echo '<option value="', $post->ID, '"', $selected == $post->ID ? ' selected="selected"' : '', '>', $post->post_title, '</option>';
        }
        echo '</select>';
    }

}

$DW_helper = new DW_Helper();