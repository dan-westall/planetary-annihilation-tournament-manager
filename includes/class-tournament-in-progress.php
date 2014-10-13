<?php

/*

require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-includes/wp-db.php' );
if (!$wpdb) {
    $wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
} else {
    global $wpdb;
}
 */

class tournament_in_progress {

    public static function register() {

        $plugin = new self();

        add_action( 'save_post',  [ $plugin, 'realtime_update_live_page'], 10, 1 );

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

        if(get_post_meta($post_id, '_wp_page_template', true) != 'template-tournament-in-progress-2.php')
            return false;

        $tournament_id = get_post_meta($post_id, 'tournament', true );

        //setsub
        $result['subscription'] =  sprintf('t%s-live', $tournament_id);



        //send to realtime
        $context = new ZMQContext();
        $socket  = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://localhost:5555");

        $socket->send(json_encode($result));



    }

    public function get_live_state($object, $subscription_id){

        $object = [];

        $live_page_id = self::get_live_page_id();



        $votes = new userPolling();

        $object_votes = $votes->setObjectId($current_match_id)->get_votes();

        $object['polling'] = $object_votes;
        $object['subscription'] = $subscription_id;

        return $object;



    }

    public static function get_live_page_id(){

        $page_id = get_option('page_on_front');

        if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress-2.php')
            return $page_id;

        return false;

    }

}