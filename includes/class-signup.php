<?php

class tournamentSignup {

    /**
     * @var
     */
    private $player_id;
    private $user_id;
    private $tournament_id;
    private $join_id;
    private $tournament_team_name;
    private $tournament_join_status;

    /**
     *
     */
    function __construct() {


    }

    /**
     * @return mixed
     */
    public function getUserId() {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id) {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getJoinId() {
        return $this->join_id;
    }

    /**
     * @param mixed $join_id
     */
    public function setJoinId($join_id) {
        $this->join_id = $join_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTournamentTeamName() {
        return $this->tournament_team_name;
    }

    /**
     * @param mixed $tournament_team_name
     */
    public function setTournamentTeamName($tournament_team_name) {
        $this->tournament_team_name = $tournament_team_name;

        return $this;
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


    /**
     * @return mixed
     */
    public function getTournamentJoinStatus() {
        return $this->tournament_join_status;
    }

    /**
     * @param mixed $tournament_join_status
     */
    public function setTournamentJoinStatus($tournament_join_status) {
        $this->tournament_join_status = $tournament_join_status;

        return $this;
    }

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




    public function join_team($team){

        p2p_add_meta($this->getJoinId(), 'team_name', $team);

    }

    public function update_profile(){

    }

    public function set_clan($clan){

        update_post_meta($this->getPlayerId(),'clan', $clan);

        p2p_add_meta($this->getJoinId(), 'clan', $clan);

    }

    public function set_clan_contact(){

        p2p_add_meta($this->getJoinId(), 'clan_contact', 1);

    }

    public function is_existing_player($values){

        global $wpdb;

        $player = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                  user_email,
                  ID AS user_id,
                  (SELECT meta_value FROM wp_usermeta  WHERE user_id = user.ID AND meta_key = 'player_id') AS player_id
                    FROM $wpdb->users AS user
                      WHERE user_email = %s
                ",
                $values['email']
            )
        );

        //do name check
        if(!isset($player->player_id)){

            $player = $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        user_email,
                        post.ID,
                        (SELECT meta_value FROM wp_postmeta  WHERE post_id = post.ID AND meta_key = 'user_id') AS user_id
                          FROM wp_posts AS post
                            LEFT JOIN wp_users AS user ON user.ID = (SELECT meta_value FROM wp_postmeta  WHERE post_id = post.ID AND meta_key = 'user_id')
                              WHERE post_title = '%s'
                                AND user_email != ''
                    ",
                    $values['ign']
                )
            );


        }

        return false;

    }

    public function is_excluded_player($values){

        global $wpdb;

        $excluded_players_list = $wpdb->query(
            $wpdb->prepare(
                "
                SELECT
                user_email
                    FROM $wpdb->users AS user WHERE user.ID IN ( SELECT ( SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'user_id') FROM wp_p2p  WHERE p2p_type = 'tournament_excluded_players' AND p2p_from = %s)
                ",
                $this->tournament_id
            )
        );

        //if email is in excluded players bin, if there are any
        if(is_array($excluded_players_list)){
            if (in_array($values['email'], $excluded_players_list))
                return true;

        }

        return false;
    }

    public function is_tournament_signup_open($tournament_id){

        $tournament_closed        = get_post_meta($tournament_id, 'signup_closed', true);
        $tournament_slots         = get_post_meta($tournament_id, 'slots', true);
        $tournament_reserve_slots = get_post_meta($tournament_id, 'reserve_slots', true);
        $tournament_status        = get_post_meta($tournament_id, 'tournament_status', true);
        $total_tournament_slots   = ($tournament_slots + $tournament_reserve_slots);
        $tournament_player_status = tournamentCPT::$tournament_player_status;

        if($tournament_closed == true || $tournament_status >= 1){

            return false;

        }

        if(tournamentCPT::get_tournament_player_count($tournament_id, [$tournament_player_status[0], $tournament_player_status[1]]) >= $total_tournament_slots){

            return false;

        }

        return true;

    }

    public function join_tournament($player_id){

        $this->setPlayerId($player_id);

        $tournament_id = $this->tournament_id;

        $tournament_player_status = tournamentCPT::$tournament_player_status;
        $tournament_slots         = get_post_meta($tournament_id, 'slots', true);
        $tournament_reserve_slots = get_post_meta($tournament_id, 'reserve_slots', true);
        $total_tournament_slots   = ($tournament_slots + $tournament_reserve_slots);

        $current_player_count = tournamentCPT::get_tournament_player_count($tournament_id, [$tournament_player_status[0]]);
        $status               = ($current_player_count >= $tournament_slots ? $tournament_player_status[1] : $tournament_player_status[0]);

        $this->setTournamentJoinStatus($status);


        //player found add player to tornament
        $p2p_result = p2p_type('tournament_players')->connect($tournament_id, $this->player_id, [
            'date'   => current_time('mysql'),
            'status' => $this->getTournamentJoinStatus()
        ]);

        if(is_wp_error($p2p_result))
            throw new Exception('Sorry there was a error, we could not enter you into this tournament.');


        $this->setJoinId($p2p_result);

        do_action( "tournament_signup_$status", [ 'player_id' => $this->player_id, 'tournament_id' => $tournament_id ] );

        tournamentCPT::delete_tournament_caches($tournament_id);

    }

    public function validate_signup_data(){

        if(empty($_POST['email']))
            throw new Exception('Clan name is a required field.');

        if(empty($_POST['ign']) || strlen($_POST['ign'] < 2))
            throw new Exception('Clan name is a required field and must ne longer than 2');

        if(get_tournament_type($this->tournament_id) == 'teamarmies' && empty($_POST['team_name']))
            throw new Exception('Team name is a required field.');

        if(get_tournament_type($this->tournament_id) == 'clanwars' && empty($_POST['clan']))
            throw new Exception('Clan name is a required field.');

    }

    public function challonge_add_player_to_tournament($challonge_tournament_id, $email, $ign){


        $name = (get_tournament_type($tournament_id) == 'teamarmies' ? $values['team_name']['value'] : $values['ign']['value']);

        if(isset($connection_meta['challonge_result'])){
            update_post_meta($player_id, 'challonge_data', $connection_meta['challonge_result']);

            //easy search
            update_post_meta($player_id, 'challonge_participant_id', $meta['challonge_result']->id);

            $connection_meta = array_merge($connection_meta, [ 'challonge_tournament_id'  => $connection_meta['challonge_tournament_id'], 'challonge_participant_id' => $connection_meta['challonge_result']->id ] );

        }

        $c = new ChallongeAPI(Planetary_Annihilation_Tournament_Manager::fetch_challonge_API());

        $c->verify_ssl = false;

        $params = array(
            'participant[name]'               => $ign
        );

        $participant = $c->createParticipant($challonge_tournament_id, $params);

        $result = json_decode( json_encode( (array) $participant), false );

        return $result;

    }

    public function challonge_remove_player_from_tournament($challonge_tournament_id, $challonge_participant_id){

        $c = new ChallongeAPI(Planetary_Annihilation_Tournament_Manager::fetch_challonge_API());

        $c->verify_ssl = false;

        $participant = $c->deleteParticipant($challonge_tournament_id, $challonge_participant_id);

        $result = json_decode( json_encode( (array) $participant), false );

        return $result;
    }

    public function get_signup_message(){

        //active
        if($this->getTournamentJoinStatus() == tournamentCPT::$tournament_player_status[0])
            return sprintf('Congratulations you have signuped to this tournament');

        //active
        if($this->getTournamentJoinStatus() == tournamentCPT::$tournament_player_status[1])
            return sprintf('Unfortunately the tournament is full, but you\'ve been placed on the resevation list.');

    }

    public static function player_signup(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = $_POST['tournament_id'];
        $player_id     = $_POST['player_id'];
        $signup_data   = $_POST['signup_data'];


        $signup = new tournamentSignup();
        $signup->setTournamentId($tournament_id);

        try{

            $signup->validate_signup_data();

            if(!self::is_tournament_signup_open($tournament_id))
                throw new Exception('Tournament sign ups are closed.');

            if(!is_user_logged_in() && $signup->is_existing_player($signup_data))
                throw new Exception('Please login to sign up for this tournament');


            //ok this is not an existing player we need to make an account!, call to playerCPT
            if(false === ( $player_id = $signup->is_existing_player($signup_data) )){

                if(false === (  $user = get_user_by( 'email', $signup['email'] ) )){
                    $user =  $signup->new_user($signup_data);

                    if (!is_object($user))
                        throw new Exception('New user was not created.');

                }

                $player_id = playerCPT::new_player_profile($user->ID, $signup_data);

                if (!is_int($player_id)){
                    require_once(ABSPATH.'wp-admin/includes/user.php' );

                    //if new user was created but a new player profile could not be created then we need to delete the now orphaned user, for tidy
                    wp_delete_user( $user->ID );

                    throw new Exception('Player Profile was not created.');
                }

            }

            //if we are there, no exceptions so we have a new user with player profile, with a tournament that they can signup to
            if($signup->is_excluded_player($player_id))
                throw new Exception('Sorry but you are excluded from this tournament.');


            $signup->join_tournament($player_id);


            if(get_tournament_type($tournament_id) == 'teamarmies')
                $signup->join_team();

            if(get_tournament_type($tournament_id) == 'clanwars'){
                $signup->set_clan();

                if(isset($signup_data['clan_contact']) && !empty($signup_data['clan_contact']))
                    $signup->set_clan_contact();
            }

            $signup->update_profile();


        } catch (Exception $e) {

            wp_send_json_error(['message' => $e->getMessage()]);

            die();

        }

        do_action( "tournament_signup", [ 'player_id' => $signup->getPlayerId(), 'tournament_id' => $signup->getTournamentId() ] );

        wp_send_json_success(['message' => $signup->get_signup_message()]);



    }


    public function new_user($values){

        $password = wp_generate_password();

        $userdata = array(
            'user_login' => $values['ign'],
            'user_email' => $values['email'],
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