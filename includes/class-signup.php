<?php

class tournamentSignup {

    /**
     * @var
     */
    private $player_id;
    private $user_id;
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

    public function set_team($player){

        if(!is_user_logged_in() && isset($player->user_email)){

            $validation_result['is_valid'] = false;
            $validation_result['form']['cssClass'] = 'please-login-to-signup';

        }

    }

    public function allow_signup($signup_values){

        if(is_array(tournamentCPT::players_excluded_from_tournament($tournament_id))){

            $error = new WP_Error;

            if (in_array($signup_values['email'], tournamentCPT::players_excluded_from_tournament($tournament_id))){

                $error->add('excluded_player', 'Very Sorry but you are excluded from this tournament, if you think this is in error please contact us via the contact form.');

            }

        }


    }

    public function signup_validation(){




    }

    public function is_existing_player($value){

        global $wpdb;

        $player = $wpdb->get_row( $wpdb->prepare("SELECT user_email, ID AS user_id, (SELECT meta_value FROM wp_usermeta  WHERE user_id = user.ID AND meta_key = 'player_id') AS player_id  FROM $wpdb->users AS user WHERE user_email = %s", $values['email']['value']) );

        //do name check
        if(!isset($player->player_id)){

            $player = $wpdb->get_row( $wpdb->prepare("SELECT user_email, post.ID, (SELECT meta_value FROM wp_postmeta  WHERE post_id = post.ID AND meta_key = 'user_id') AS user_id  FROM wp_posts AS post LEFT JOIN wp_users AS user ON user.ID = (SELECT meta_value FROM wp_postmeta  WHERE post_id = post.ID AND meta_key = 'user_id') WHERE post_title = '%s' AND user_email != ''", $values['ign']['value']) );

        }

    }

    public static function player_signup(){

        secutiry();


        $tournament_id = $_POST['tournament_id'];
        $player_id     = $_POST['player_id'];
        $signup        = $_POST['signup_data'];


        $signup = new tournamentSignup();

        $signup->setTournamentId($tournament_id);


        if($signup->is){

        }

        if(false === ( $player_id = $signup->is_existing_player($signup) )){

            //ok this is not an existing player we need to make an account!, call to playerCPT

            if(false === (  $user = get_user_by( 'email', $signup['email'] ) )){
               $user =  $signup->new_user($signup);
            }

            $player_id = playerCPT::new_player_profile($user->ID,[]);

        }



    }


    public function new_user(){

        $password = wp_generate_password();

        $userdata = array(
            'user_login' => $values['ign']['value'],
            'user_email' => $values['email']['value'],
            'user_pass'  => $password
        );

        $user_id = wp_insert_user($userdata);

        wp_new_user_notification($user_id, $password);

        $user = get_user_by('id', $user_id);

        return $user;
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