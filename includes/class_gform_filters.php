<?php


class gform_filters {

    function __construct() {

        add_filter( 'gform_field_value_player_profile_name',  array( $this, 'player_profile_name' ) );
        add_filter( 'gform_field_value_clan',  array( $this, 'player_profile_clan' ) );

    }


    function player_profile_name($value){

        global $current_user;

        get_currentuserinfo();

        if(empty($current_user->player_id))
            return '';

        return get_the_title($current_user->player_id);
    }

    function player_profile_clan($value){

        global $current_user;

        get_currentuserinfo();

        return get_post_meta($current_user->player_id, 'clan', true);
    }
}