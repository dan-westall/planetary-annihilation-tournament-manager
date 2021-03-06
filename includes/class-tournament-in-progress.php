<?php

    //make path pretty
    require_once(__DIR__ . '/../../../../wp-load.php');


    if (!$wpdb) {
        $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
    } else {
        global $wpdb;
    }



class tournament_in_progress {

    public static function register() {

        $plugin = new self();

        add_action( 'save_post',  [ $plugin, 'realtime_update_live_page'], 10, 1 );
        add_action( 'updated_live_page',  [ $plugin, 'realtime_update_live_page'], 10, 1 );

        add_filter( 'realtime_subscription', [ $plugin, 'get_live_state' ], 10, 2 );


    }

    /**
     *
     */
    function __construct() {


    }

    public static function realtime_update_live_page($post_id){

        if (!class_exists('ZMQContext'))
            return false;

        if(!is_tournament_in_progress())
            return false;

        if(get_post_meta($post_id, '_wp_page_template', true) != 'template-tournament-in-progress-2.php' || get_field('current_match', tournament_in_progress::get_live_page_id()) != $post_id)
            return false;



        //setsub
        $live_state = self::get_live_state();


        //send to realtime
        $context = new ZMQContext();
        $socket  = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://localhost:5555");

        $socket->send(json_encode($live_state));


    }

    public static function get_live_state() {

        global $wpdb;

        $object = [];

        $live_page_id = self::get_live_page_id();

        $current_match_id = $wpdb->get_var( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'current_match' AND post_id = 417" );

        if(!is_tournament_in_progress())
            return ['result' => false, 'Tournament live page is not front page.'];

        if(empty($current_match_id))
            return ['result' => false, 'match ID not set on live page'];

        $current_tournament_id = $wpdb->get_var( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'tournament' AND post_id = 417" );

        $match = get_post($current_match_id, 'ARRAY_A');

        $votes = new userPolling();

        $object_votes = $votes->setObjectId($current_match_id)->get_votes();

        $object['polling'][$current_match_id] = $object_votes;
        $object['subscription']               = 'live';
        $object['current_match']['id']        = $current_match_id;
        $object['current_match']['object']    = matchCPT::extend_json_api($match, $match, 'websocket');

        if (have_rows('small_content_type', $live_page_id)) {

            while (have_rows('small_content_type', $live_page_id)) { the_row();

                if (get_row_layout() == 'guest_analyst') {

                    $analyst = get_sub_field('analyst');

                    $object['small_content']['type']   = 'guest_analyst';
                    $object['small_content']['object'] = [ 'display_name' => $analyst['display_name'], 'avatar' => $analyst['user_avatar']];

                }

            }

        }

        return $object;

    }

    public static function get_live_page_id(){

        $page_id = get_option('page_on_front');

        if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress-2.php')
            return $page_id;

        return false;

    }

}