<?php

class userWager {

    function __construct() {

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_action( 'wp_ajax_get_match_results', array( $this, 'place_wager') );

    }

    public function register_p2p_connections() {

        p2p_register_connection_type( array(
            'name' => 'player_wager',
            'from' => 'user',
            'to' => self::$post_type,
            'admin_box' => array(
                'show' => false
            )
        ) );

    }

    public function tournament_wager(){

        check_ajax_referer( 'security-' . date('dmy') , 'security' );

        $result = p2p_type('player_wager')->connect($_POST['current_user_id'], $_POST['player_id'], array(
            'date'          => current_time('mysql'),
            'tournament_id' => $_POST['tournament_id'],
            'wager'         => 'tournament_win'
        ));

        if($result){

            echo json_encode(array('result' => true, 'message' => 'Wager has been placed.'));

            die();

        }
    }

    public function match_wager(){

        check_ajax_referer( 'security-' . date('dmy') , 'security' );

        $result = p2p_type('player_wager')->connect($_POST['current_user_id'], $_POST['player_id'], array(
            'date'          => current_time('mysql'),
            'tournament_id' => $_POST['tournament_id'],
            'match_id' => $_POST['match_id'],
            'wager'         => 'match_win'
        ));

        if($result){

            echo json_encode(array('result' => true, 'message' => 'Wager has been placed.'));

            die();

        }
    }
}
