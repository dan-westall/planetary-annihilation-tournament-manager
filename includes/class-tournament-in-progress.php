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

    public function get_live_state($object, $subscription_id){

        $object = [];

        $live_page_id = self::get_live_page_id();

        while( has_sub_field('stream', $live_page_id) ){

            if( have_rows('large_content_type', $live_page_id) ) {

                while (have_rows('large_content_type', $live_page_id)) {

                    the_row();

                    if (get_row_layout() == 'match'):

                        $current_match_id   = get_sub_field( 'current_match' );
                        $show_match_polling = get_sub_field( 'show_winner_poll' );

                    elseif (get_row_layout() == 'poll'):


                    elseif (get_row_layout() == 'advert'):


                    endif;

                }

            }  else {


            }

        }



        $votes = new userPolling();

        $object_votes = $votes->setObjectId($current_match_id)->get_votes();

        $object['polling'] = $object_votes;
        $object['subscription'] = $subscription_id;
        $object['subscription'] = 'ddd';

        return $object;



    }

    public static function get_live_page_id(){

        $page_id = get_option('page_on_front');

        if(get_post_meta($page_id, '_wp_page_template', true) == 'template-tournament-in-progress-2.php')
            return $page_id;

        return false;

    }

}