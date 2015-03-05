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

        add_action( 'wp_ajax_player_signup', [ $plugin, 'player_signup'] );
        add_action( 'wp_ajax_nopriv_player_signup',  [ $plugin, 'player_signup' ] );

        //moved from tournament class
        add_action( 'wp_ajax_tournament_withdraw',  [ $plugin, 'ajax_tournament_withdraw'] );
        add_action( 'wp_ajax_tournament_reenter',  [ $plugin, 'ajax_tournament_reenter'] );

        add_action( 'wp_enqueue_scripts',  [ $plugin, 'register_scripts'] );

        add_action( 'tournament_signup',  [ $plugin, 'challonge_add_player_to_tournament'] );

        add_action( 'tournament_player_withdrawn',  [ $plugin, 'challonge_remove_player_from_tournament'] );
        add_action( 'tournament_player_reentered',  [ $plugin, 'challonge_add_player_to_tournament'] );



        add_shortcode( 'tournament_signup_form', [ $plugin,  'tournament_signup_form'] );

    }

    public static function register_scripts(){

        wp_register_script(
            'signupForm',
            PLTM_PLUGIN_URI. 'public/assets/js/patm.signup.min.js', //PLTM_PLUGIN_URI
            ['defaults.main.min'],
            date('U'),
            true
        );


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

    public function is_existing_tournament_player($player_id){
        $p2p_id = p2p_type('tournament_players')->get_p2p_id($this->tournament_id, $player_id);

        //is linked to tournament
        if ($p2p_id) {

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

    public function validate_signup_fields(){

        $error = new WP_Error();

        $er = empty($_POST['signup_data']['email']);
        $er1 = array_key_exists('email', $_POST['signup_data']);

        if(empty($_POST['signup_data']['email']) && !array_key_exists('email', $_POST['signup_data']) && !is_email($_POST['signup_data']['email']))
            return new WP_Error( 'validation', __( "Plesse make sure all fields have been filled in.", "wp_tournament_manager" ) );

        if(empty($_POST['signup_data']['inGameName']) && !array_key_exists('inGameName', $_POST['signup_data']) &&  strlen($_POST['inGameName'] < 2))
            return new WP_Error( 'validation', __( "Plesse make sure all fields have been filled in.", "wp_tournament_manager" ) );

        if(get_tournament_type($this->tournament_id) == 'teamarmies' && !empty($_POST['signup_data']['teamName']) && !array_key_exists('teamName', $_POST['signup_data']))
            return new WP_Error( 'validation', __( "Plesse make sure all fields have been filled in.", "wp_tournament_manager" ) );

        if(get_tournament_type($this->tournament_id) == 'clanwars' && !empty($_POST['signup_data']['clan']) && !array_key_exists('clan', $_POST['signup_data']))
            return new WP_Error( 'validation', __( "Plesse make sure all fields have been filled in.", "wp_tournament_manager" ) );

        return true;

    }

    public static function challonge_add_player_to_tournament($player_id, $tournament_id){

        if(false === ($challonge_tournament_id  = tournamentCPT::get_the_challonge_tournament_id($tournament_id)))
            return false;

        $player        = get_post($player_id);
        $connection_id = p2p_type('tournament_players')->get_p2p_id($tournament_id, $player_id);
        $challonge_api_key = Planetary_Annihilation_Tournament_Manager::fetch_challonge_API();

        $name = (get_tournament_type($tournament_id) == 'teamarmies' ? p2p_get_meta($connection_id, 'team_name', true) : $player->post_title);

        $c = new ChallongeAPI($challonge_api_key);

        $c->verify_ssl = false;

        $params = array(
            'participant[name]' => $player->post_title
        );

        $participant = (array) $c->createParticipant($challonge_tournament_id, $params);

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
        $challonge_api_key        = Planetary_Annihilation_Tournament_Manager::fetch_challonge_API();

        $c = new ChallongeAPI($challonge_api_key);

        $c->verify_ssl = false;

        $participant = (array) $c->deleteParticipant($challonge_tournament_id, $challonge_participant_id);

        p2p_delete_meta($connection_id, 'challonge_participant_id', $challonge_participant_id);

        return $participant;
    }

    public function get_signup_message(){

        //active
        if($this->getTournamentJoinStatus() == tournamentCPT::$tournament_player_status[0])
            return sprintf('Congratulations you have signuped to this tournament');

        //active
        if($this->getTournamentJoinStatus() == tournamentCPT::$tournament_player_status[1])
            return sprintf('Unfortunately the tournament is full, but you\'ve been placed on the reservation list.');

    }

    public static function player_signup(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        $tournament_id = intval($_POST['tournament_id']);
        $player_id     = intval($_POST['player_id']);
        $signup_data   = array_map( 'esc_attr', $_POST['signup_data'] );


        $signup = new tournamentSignup();

        if( is_wp_error( $signup->validate_signup_fields() ) )
            wp_send_json_error(['message' => $signup->validate_signup_fields()->get_error_message() , 'type' => 'validation']);


        $signup->setTournamentId($tournament_id);

        try{

            if(!$signup->is_tournament_signup_open($tournament_id))
                throw new Exception('Tournament sign ups are closed.');

            if(!is_user_logged_in() && $signup->is_existing_player($signup_data))
                throw new Exception(sprintf('Please login to sign up for this tournament'));


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

            if($signup->is_existing_tournament_player($player_id))
                throw new Exception('Great news, Your already signed up to this tournament.');

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

            wp_send_json_error(['message' => $e->getMessage(), 'type' => 'error']);

        }

        do_action( "tournament_signup", [ 'player_id' => $signup->getPlayerId(), 'tournament_id' => $signup->getTournamentId() ] );

        wp_send_json_success(['message' => $signup->get_signup_message(), 'type' => 'success']);

    }


    public function new_user($values){

        $password = wp_generate_password();

        $userdata = array(
            'user_login' => $values['inGameName'],
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

            do_action('tournament_player_withdrawn', $player_id , $tournament_id );

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

            do_action('tournament_player_reentered', $player_id, $tournament_id );

            echo json_encode(array('result' => true, 'message' => 'You have been re-entered into the tournament.'));

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



        ?>
        <script type="text/ng-template" id="error-messages">
            <div ng-message="required">You left the field blank</div>
            <div ng-message="minlength">Your field is too short</div>
            <div ng-message="maxlength">Your field is too long</div>
            <div ng-message="email">Your email address invalid</div>
        </script>

        <script type="text/ng-template" id="signupform.html">

            <div ng-controller="signupFormController">

                <div ng-show="result.message" class="form-message" ng-class="{ '__error': result.type == 'error', '__validation': result.type == 'validation', '__success': result.type == 'success' }">{{result.message}}</div>

                <form class="tournament-signup-form" name="playerSignupForm" ng-class="{ 'submission-in-progress': submission }" ng-submit="submitted = true; submitSignup( signupData, playerSignupForm.$valid )" novalidate>

                    <div id="in-game-name" class="form-group" ng-class="{ 'has-error' : inGameName }">
                        <label>In game Name</label>
                        <input type="text" name="inGameName" ng-model="signupData.inGameName" class="form-control" placeholder="In game name" value="<?php echo $ign; ?>" ng-minlength="2" required>
                        <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.inGameName.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.inGameName.$touched">
                            <div ng-message="required">You left your in game name blank.</div>
                        </div>
                        <div class="description">Please ensure this matches exactly, including the type of brackets used. You will be able to modify this later if it changes.</div>
                    </div>

                    <div id="email" class="form-group" ng-class="{ 'has-error' : email }">
                        <label>Email Address</label>
                        <input type="email" name="email" ng-model="signupData.email" class="form-control" placeholder="Email Address" value="<?php echo $email; ?>" required>
                        <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.email.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.email.$touched">
                            <div ng-message="required">You left your email blank.</div>
                        </div>
                        <div class="description">This e-mail address will be used solely by eXodus, it will not be passed to any third parties. Please ensure this is a monitored e-mail address as we will use it to communicate with you.</div>
                    </div>

                    <?php if(get_tournament_type($tournament_id) == 'teamarmies') : ?>

                        <div id="team-name" class="form-group" ng-class="{ 'has-error' : teamName }">
                            <label>Team Name</label>
                            <input type="text" name="teamName" ng-model="signupData.teamName" class="form-control" placeholder="Team name" required>
                            <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.teamName.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.teamName.$touched"></div>
                        </div>

                    <?php endif; ?>

                    <?php if(get_tournament_type($tournament_id) == 'clanwars') : ?>

                        <div id="clan-name" class="form-group" ng-class="{ 'has-error' : clanName }">
                            <label>Clan Name</label>
                            <input type="text" name="teamName" ng-model="signupData.clanName" class="form-control" placeholder="Clan name" value="<?php echo $clan; ?>" required>
                            <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.clanName.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.clanName.$touched"></div>
                        </div>

                        <div id="clan-contact" class="form-group" ng-class="{ 'has-error' : clanContact }">
                            <label>I am clan contact</label><br />
                            <div class="custom-checkbox-style">
                                <input type="checkbox" value="None" id="clan-contact" name="clanContact"  ng-model="signupData.clanContact"/>
                                <label for="clan-contact"></label>
                            </div>
                            <label for="communication" class="description">When dealing with clans its easier for everyone, if there is just one point of contact</label>
                        </div>

                    <?php endif; ?>

                    <div id="team-name" class="form-group">
                        <label>Is there anything else we need to know?</label>
                        <textarea ng-model="signupData.otherDetails"></textarea>
                    </div>

                    <div id="team-name" class="form-group">
                        <label>Future Communication</label><br />
                        <div class="custom-checkbox-style">
                            <input type="checkbox" value="None" id="communication" name="check"  ng-model="signupData.communication"/>
                            <label for="communication"></label>
                        </div>
                        <label for="communication" class="description" ng-class="{ 'happy': signupData.communication }">I agree to receive emails from eXodus eSports regarding new products, services or upcoming events. Collected information will not be shared with any third party.<span></span></label>
                    </div>

                    <input type="submit" value="Join this tournament" class="tournament-btn __signup"/>
    <br />

                    <pre>{{signupData | json}}</pre>
                </form>

            </div>

        </script>

        <?php

        return '<div ng-include=" \'signupform.html\' "></div>';
        
    }

}