<?php


class clans {

    public static function get_clans_listing(){

        global $wpdb;

        $clans = $wpdb->get_results("SELECT meta_value as clan_name FROM $wpdb->postmeta WHERE meta_key = 'clan' group by meta_value");

        return $clans;

    }



}

