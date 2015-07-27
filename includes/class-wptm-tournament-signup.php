<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Tournament_Signup {

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

        add_action( 'wp_ajax_player_signup', [ $plugin, 'player_signup'] );
        add_action( 'wp_ajax_nopriv_player_signup',  [ $plugin, 'player_signup' ] );

        //moved from tournament class
        add_action( 'wp_ajax_tournament_withdraw',  [ $plugin, 'ajax_tournament_withdraw'] );
        add_action( 'wp_ajax_tournament_reenter',  [ $plugin, 'ajax_tournament_reenter'] );

        add_action( 'wp_enqueue_scripts',  [ $plugin, 'register_scripts'] );

        add_action( 'tournament_signup',  [ $plugin, 'challonge_add_player_to_tournament'] );

        add_action( 'tournament_player_withdrawn',  [ $plugin, 'challonge_remove_player_from_tournament'], 10, 2 );
        add_action( 'tournament_player_active',  [ $plugin, 'challonge_add_player_to_tournament'], 10, 2 );

        add_action( 'updated_p2p_meta',  [ $plugin, 'challonge_add_player_to_tournament'], 10, 4  );



        add_shortcode( 'tournament_signup_form', [ $plugin,  'tournament_signup_form'] );

    }

    public static function register_scripts(){

        wp_register_script(
            'signupForm',
            WPTM_PLUGIN_URI. 'public/assets/js/patm.signup.min.js', //PLTM_PLUGIN_URI
            ['defaults.main.min'],
            date('U'),
            true
        );


    }


    public function join_team($team){

        p2p_add_meta($this->getJoinId(), 'team_name', strtolower($team));

        do_action( 'player_team_join', $this->getPlayerId(), $this->getTournamentId(), $this->getJoinId(), strtolower($team));

    }

    public function update_profile($player_id, $meta){

        foreach($meta as $key => $value){
            update_post_meta($player_id, $key, $value);
        }

    }

    public function update_user($user_id, $meta){

        foreach($meta as $key => $value){
            update_user_meta($user_id, $key, $value);
        }

    }

    public function set_clan($clan){

        update_post_meta($this->getPlayerId(),'clan', $clan);

        p2p_add_meta($this->getJoinId(), 'clan', $clan);

    }

    public function set_clan_tag($clan_tag){

        update_post_meta($this->getPlayerId(),'clan_tag', $clan_tag);

        p2p_add_meta($this->getJoinId(), 'clan_tag', $clan_tag);

    }

    public function set_clan_contact(){

        p2p_add_meta($this->getJoinId(), 'clan_contact', 1);

    }

    public function player_signup_status_change($p2p_id, $meta_key, $meta_value, $prev_value){

        if($meta_key == 'status' && $meta_value == tournamentCPT::$tournament_player_status[5] && $prev_value == tournamentCPT::$tournament_player_status[0]){




        }

    }

    public function is_existing_player($values){

        global $wpdb;

        $player = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                  user_email,
                  ID AS user_id,
                  (SELECT meta_value FROM $wpdb->usermeta  WHERE user_id = user.ID AND meta_key = 'player_id') AS player_id
                    FROM $wpdb->users AS user
                      WHERE user_email = %s
                ",
                $values['email']
            )
        );

        if(isset($player->player_id))
            return $player->player_id;
        //do name check


        $player = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    user_email,
                    post.ID,
                    (SELECT meta_value FROM $wpdb->postmeta  WHERE post_id = post.ID AND meta_key = 'user_id') AS user_id
                      FROM $wpdb->posts AS post
                        LEFT JOIN $wpdb->users AS user ON user.ID = (SELECT meta_value FROM $wpdb->postmeta  WHERE post_id = post.ID AND meta_key = 'user_id')
                          WHERE post_title = '%s'
                            AND user_email != ''
                ",
                $values['ign']
            )
        );

        if(isset($player->player_id))
            return $player->player_id;




        return false;

    }

    public function is_excluded_player($values){

        global $wpdb;

        $excluded_players_list = $wpdb->query(
            $wpdb->prepare(
                "
                SELECT
                user_email
                    FROM $wpdb->users AS user WHERE user.ID IN ( SELECT ( SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p2p_to AND meta_key = 'user_id') FROM {$wpdb->prefix}p2p  WHERE p2p_type = 'tournament_excluded_players' AND p2p_from = %s)
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

    public static function is_existing_tournament_player($player_id, $tournament_id, $player_status = []){

        $p2p_id        = p2p_type('tournament_players')->get_p2p_id($tournament_id, $player_id);
        $player_status = empty($player_status) ? tournamentCPT::$tournament_player_status : $player_status;

        //is linked to tournament
        if ($p2p_id && in_array(p2p_get_meta($p2p_id, 'status', true), $player_status)) {
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


        //split out, need better function to take care of can join tournament
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

        do_action( "tournament_signup_{$status}", $this->player_id, $tournament_id );

        tournamentCPT::delete_tournament_caches($tournament_id);

    }

    public function validate_signup_fields(){

        //todo take another pass on this

        $error = new WP_Error();

        $er = empty($_POST['signup_data']['email']);
        $er1 = array_key_exists('email', $_POST['signup_data']);

        if(empty($_POST['signup_data']['email']) || !array_key_exists('email', $_POST['signup_data']) || !is_email($_POST['signup_data']['email']))
            return new WP_Error( 'validation', __( "Plesse make sure all email has been filled in.", "wp_tournament_manager" ) );

        if(empty($_POST['signup_data']['inGameName']) || !array_key_exists('inGameName', $_POST['signup_data']) ||  strlen($_POST['signup_data']['inGameName']) < 2)
            return new WP_Error( 'validation', __( "Plesse make sure all in game name has been filled in.", "wp_tournament_manager" ) );

        if(get_tournament_type($this->tournament_id) == 'teamarmies' && (!empty($_POST['signup_data']['teamName']) || !array_key_exists('teamName', $_POST['signup_data'])))
            return new WP_Error( 'validation', __( "Plesse make sure all teamname have been filled in.", "wp_tournament_manager" ) );

        if(get_tournament_type($this->tournament_id) == 'clanwars' && (!empty($_POST['signup_data']['clan']) && !array_key_exists('clan', $_POST['signup_data'])))
            return new WP_Error( 'validation', __( "Plesse make sure all clan name has been filled in.", "wp_tournament_manager" ) );

        return true;

    }

    public static function challonge_add_player_to_tournament($player_id, $tournament_id){

        if(false === ($challonge_tournament_id  = tournamentCPT::get_the_challonge_tournament_id($tournament_id)))
            return false;

        $player        = get_post($player_id);
        $connection_id = p2p_type('tournament_players')->get_p2p_id($tournament_id, $player_id);
        $challonge_api_key = WP_Tournament_Manager::fetch_challonge_API();

        $name = (get_tournament_type($tournament_id) == 'teamarmies' ? p2p_get_meta($connection_id, 'team_name', true) : $player->post_title);

        try {

            $c = new ChallongeAPI($challonge_api_key);

            $c->verify_ssl = false;

            $params = array(
                'participant[name]' => $player->post_title
            );

            $participant = (array) $c->createParticipant($challonge_tournament_id, $params);

        } catch (Exception $e){

            do_action('challonge_add_player_error', $challonge_tournament_id, $player_id, $tournament_id);

        }

        if($participant['active']){

            p2p_add_meta($connection_id, 'challonge_participant_id', $participant['id']);

            return $participant;
        }

        return false;

    }

    public static function challonge_remove_player_from_tournament($player_id, $tournament_id){

        if(false === ($challonge_tournament_id  = tournamentCPT::get_the_challonge_tournament_id($tournament_id)))
            return false;

        $connection_id            = p2p_type('tournament_players')->get_p2p_id($tournament_id, $player_id);
        $challonge_participant_id = p2p_get_meta($connection_id, 'challonge_participant_id', true);
        $challonge_api_key        = WP_Tournament_Manager::fetch_challonge_API();

        try {

            $c = new ChallongeAPI($challonge_api_key);

            $c->verify_ssl = false;

            $participant = (array)$c->deleteParticipant($challonge_tournament_id, $challonge_participant_id);

        } catch (Exception $e){

            do_action('challonge_remove_player_error', $challonge_tournament_id, $challonge_participant_id, $player_id, $tournament_id);

        }

        p2p_delete_meta($connection_id, 'challonge_participant_id', $challonge_participant_id);

        return $participant;
    }

    public function get_signup_message(){

        //active
        if($this->getTournamentJoinStatus() == tournamentCPT::$tournament_player_status[0])
            return sprintf('Congratulations you have entered %s, you will receive your welcome email shortly.<br /><br />Don\'t forget to follow us on <a href="https://twitter.com/exodusesport">Twitter</a> or <a href="https://www.facebook.com/exodus.es">Facebook</a>', get_the_title($this->getTournamentId()));

        //reservation
        if($this->getTournamentJoinStatus() == tournamentCPT::$tournament_player_status[1])
            return sprintf('Unfortunately the tournament is full, but you\'ve been placed on the reservation list.');

    }

    public static function player_signup(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = intval($_POST['tournament_id']);
        $player_id     = intval($_POST['player_id']);
        $signup_data   = array_map( 'esc_attr', $_POST['signup_data'] );


        $signup = new WPTM_Tournament_Signup();

        if( is_wp_error( $signup->validate_signup_fields() ) ) {

            wp_send_json_error(['message' => $signup->validate_signup_fields()->get_error_message() , 'type' => 'validation']);

        }

        $signup->setTournamentId($tournament_id);

        try{

            if(!$signup->is_tournament_signup_open($tournament_id)){

                throw new Exception('Tournament sign ups are closed.');

            }

            if($signup->is_existing_player($signup_data)){

                if( true !== ( $can_join_tournament = apply_filters( 'can_join_tournament', true, $tournament_id, $signup->is_existing_player($signup_data) ) ) ){

                    if( is_string( $can_join_tournament ) ){

                        throw new Exception( $can_join_tournament );

                    }

                    throw new Exception('You cannot join this tournament.');

                }

                if( !is_user_logged_in() ) {

                    throw new Exception(sprintf('Please <a href="%s">login</a> to sign up for this tournament', wp_login_url( get_permalink($tournament_id).'sign-up' ) ));

                }

            }

            //ok this is not an existing player we need to make an account!, call to playerCPT
            if(false === ( $player_id = $signup->is_existing_player($signup_data) )){

                if(false === (  $user = get_user_by( 'email', $signup_data['email'] ) )){
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

            if($signup->is_existing_tournament_player($player_id, $tournament_id))
                throw new Exception('Great news, you\'re already signed up to this tournament.');

            $signup->join_tournament($player_id);

            if(get_tournament_type($tournament_id) == 'teamarmies')
                $signup->join_team($signup_data['teamName']);

            if(get_tournament_type($tournament_id) == 'clanwars'){
                $signup->set_clan($signup_data['clanName']);

                $signup->set_clan_tag($signup_data['clanTag']);

                if(isset($signup_data['clan_contact']) && !empty($signup_data['clan_contact']))
                    $signup->set_clan_contact();
            }

            if(!empty($signup_data['otherDetails']))
                //$signup->set_clan_contact();


            //$signup->update_profile(['site_notifications' => 1]);
            $signup->update_user( get_post_meta($player_id, 'user_id', true ), ['site_notifications' => ( $signup_data['communication'] ? $signup_data['communication'] : "0" ) ]);

            $player_helper = new WPTM_Player_Helper( $player_id );

            if( is_int( $player_helper->has_pa_stats_id() ) ){

                $signup_data['PA Stats ID'] = $player_helper->has_pa_stats_id();

            } else {

                do_action( "tournament_signup_no_pastats", $player_id, $tournament_id, $_POST['signup_data'] );

            }


        } catch (Exception $e) {

            do_action( "tournament_signup_error", $player_id, $tournament_id, $e->getMessage(), $_POST['signup_data'] );

            wp_send_json_error(['message' => $e->getMessage(), 'type' => 'error']);

        }

        do_action( "tournament_signup", $player_id, $tournament_id, $signup->get_signup_message(), $_POST['signup_data'] , $signup->getTournamentJoinStatus() );

        do_action( "tournament_state_change", $tournament_id );

        wp_send_json_success(['message' => $signup->get_signup_message(), 'type' => 'success']);

    }


    public function new_user($values){

        $password = wp_generate_password();

        $userdata = [
            'user_login' => $values['inGameName'],
            'user_email' => $values['email'],
            'user_pass'  => $password
        ];

        $user_id = wp_insert_user($userdata);

        wp_new_user_notification($user_id, $password);

        $user = get_user_by('id', $user_id);

        return $user;
    }

    public static function ajax_tournament_withdraw(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = intval($_POST['tournament_id']);
        $player_id     = intval($_POST['player_id']);

        //todo make sure tournament signup are open

        $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

        if ( $p2p_id ) {

            p2p_update_meta($p2p_id, 'status', tournamentCPT::$tournament_player_status[5]);

            if (!empty($_POST['reason'])) {
                p2p_update_meta($p2p_id, 'note', $_POST['reason']);
            }

            do_action('tournament_player_withdrawn', $player_id , $tournament_id );

            do_action( "tournament_state_change", $tournament_id );

            echo json_encode(array('result' => true, 'message' => 'You have been removed from the tournament.'));

            die();

        } else {

            echo json_encode(array('result' => false, 'message' => 'Player not in tournament.'));

            die();

        }
    }

    public static function ajax_tournament_reenter(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = intval($_POST['tournament_id']);
        $player_id     = intval($_POST['player_id']);

        $tournament_player_status = tournamentCPT::$tournament_player_status;

        $current_player_count = tournamentCPT::get_tournament_player_count($tournament_id, [ $tournament_player_status[0] ] );
        $status = ( $current_player_count >= $tournament_slots ? $tournament_player_status[1] : $tournament_player_status[0] );



        $p2p_id = p2p_type( 'tournament_players' )->get_p2p_id( $tournament_id, $player_id );

        if ( $p2p_id ) {

            p2p_update_meta($p2p_id, 'status', $status);

            do_action( "tournament_signup_{$status}", $player_id, $tournament_id );

            do_action( "tournament_state_change", $tournament_id );

            echo json_encode(array('result' => true, 'message' => "You have been set to $status in this tournament."));

            die();

        } else {

            echo json_encode(array('result' => false, 'message' => 'Player not in tournament.'));

            die();

        }

    }

    public static function tournament_signup_form($attr){

        global $post, $current_user;

        extract(shortcode_atts(array(
            'odd' => '',
            'tournament_id' => $post->ID
        ), $attr));

        wp_enqueue_script('signupForm');

        //prefills
        $ign = $clan ='';

        if(is_user_logged_in()){

            get_currentuserinfo();

            $user_player_profile = get_post( $current_user->player_id );

            $ign   = $user_player_profile->post_title;
            $email = $current_user->user_email;
            $clan  = get_post_meta($user_player_profile->ID, 'clan', true);

        }

        require_once WPTM_PLUGIN_DIR . '/public/views/tournament-signup.php';

        return '<div ng-include=" \'signupform.html\' "></div>';
        
    }

}