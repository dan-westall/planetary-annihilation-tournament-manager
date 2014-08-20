<?php


class PLTM_shortcodes {

    function __construct() {

        add_shortcode( 'profile_name', [ $this, 'get_profile_name' ] );

    }

    public function get_profile_name($atts){

        global $current_user;

        extract( shortcode_atts(
                array(

                ), $atts )
        );


        get_currentuserinfo();


        return '>>'.get_the_title($current_user->player_id);

    }

}