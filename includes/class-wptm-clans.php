<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Clans {

    public static function get_clans_listing(){

        global $wpdb;

        $clans = $wpdb->get_results("SELECT meta_value as clan_name FROM $wpdb->postmeta WHERE meta_key = 'clan' group by meta_value");

        return $clans;

    }

    public function is_clan_leader($clan_tag){

        global $current_user;

        $player_id = $current_user->player_id;

        if(get_post_meta($player_id, 'clan', true) === $clan_tag && get_post_meta($player_id, 'clan_leader', true))
            return true;

        return false;

    }


}

