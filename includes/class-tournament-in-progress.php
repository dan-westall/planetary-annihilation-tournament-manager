<?php

class tournament_in_progress {

    public static function register() {

        $plugin = new self();

        add_action( 'save_post',  [ $plugin, 'realtime_update_live_page'], 10, 1 );

        add_filter( 'realtime_subscription', [ $plugin, 'get_live_state' ], 10, 2 );


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

    public function get_live_state($subscription_id){





    }

}