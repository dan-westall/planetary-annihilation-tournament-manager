<?php

class tournamentSignup {

    /**
     * @var
     */
    private $player_id;
    private $tournament_id;
    private $tournament_team_name;

    /**
     *
     */
    public static function register() {

        $plugin = new self();

        //add_action( 'p2p_init', [ $plugin, 'register_p2p_connections']);

        add_action( 'wp_ajax_player_signup', [ $plugin, 'player_signup']);
        add_action( 'wp_ajax_nopriv_player_signup',  [ $plugin, 'player_signup' ] );

        //moved from tournament class
        add_action( 'wp_ajax_tournament_withdraw',  [ $plugin, 'ajax_tournament_withdraw'] );
        add_action( 'wp_ajax_tournament_reenter',  [ $plugin, 'ajax_tournament_reenter'] );

    }


    /**
     *
     */
    function __construct() {


    }

    /**
     * @return mixed
     */
    public function getPlayerId() {
        return $this->player_id;
    }

    /**
     * @param mixed $player_id
     */
    public function setPlayerId($player_id) {
        $this->player_id = $player_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTournamentId() {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function setTournamentId($tournament_id) {
        $this->tournament_id = $tournament_id;

        return $this;
    }

    public function new_signup(){

    }

    public function withdraw(){


    }

    public function join_team(){



    }

    public function allow_signup(){




    }

    public function is_existing_player(){



    }

    public static function player_signup(){

        secutiry();


        $tournament_id = $_POST['tournament_id'];
        $player_id     = $_POST['player_id'];
        $signup        = $_POST['signup_data'];


        $signup = new tournamentSignup();

        $signup->setTournamentId($tournament_id);

        if(false === ( $player_id = $signup->is_existing_player($signup) )){

            //ok this is not an existing player we need to make an account!, call to playerCPT


            

        }



    }

    public static function ajax_tournament_withdraw(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = $_POST['tournament_id'];
        $player_id     = $_POST['player_id'];

        //todo make sure tournament signup are open

        $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

        if ( $p2p_id ) {

            p2p_update_meta($p2p_id, 'status', self::$tournament_player_status[5]);

            if (!empty($_POST['reason'])) {
                p2p_update_meta($p2p_id, 'note', $_POST['reason']);
            }

            do_action('tournament_player_withdrawn', $tournament_id, $player_id );

            echo json_encode(array('result' => true, 'message' => 'You have been removed from the tournament.'));

            die();

        } else {

            echo json_encode(array('result' => false, 'message' => 'Player not in tournament.'));

            die();

        }
    }

    public static function ajax_tournament_reenter(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = $_POST['tournament_id'];
        $player_id     = $_POST['player_id'];

        //todo make sure tournament signup are open

        $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

        if ( $p2p_id ) {

            p2p_update_meta($p2p_id, 'status', self::$tournament_player_status[0]);

            do_action('tournament_player_reentered', $tournament_id, $player_id );

            echo json_encode(array('result' => true, 'message' => 'You have been re-entered into the tournament.'));

            die();

        } else {

            echo json_encode(array('result' => false, 'message' => 'Player not in tournament.'));

            die();

        }

    }

}


/*
 *
 *
 * //$signup = new tournamentSignup();
 *
 *
 *
 *
 *
 *
 *
 *
 *
 */