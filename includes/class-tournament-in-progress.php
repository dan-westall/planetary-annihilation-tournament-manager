<?php

class tournament_in_progress {

    public static function register() {

        $plugin = new self();


        add_action( 'save_post',  array( $this, 'realtime_update_live_page'), 10, 1 );


    }


    public static function realtime_update_live_page(){

    }

}