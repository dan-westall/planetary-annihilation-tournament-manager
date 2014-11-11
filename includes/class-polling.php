<?php

/**
 * Class userPolling
 */
class userPolling {

    /**
     * @var
     */
    private $player_id;
    /**
     * @var
     */
    private $match_id;
    /**
     * @var
     */
    private $tournament_id;

    private $object_id;

    /**
     * @var
     */
    private $team_id;

    private $user_id;

    public static $vote_type = array('tournament_win' => 'Tournament Win', 'match_win' => 'Match Win');

    /**
     *
     */
    public static function register() {

        $plugin = new self();

        add_action( 'p2p_init', [ $plugin, 'register_p2p_connections']);

        add_action( 'wp_ajax_vote', [ $plugin, 'vote']);

        add_action( 'wp_ajax_get_votes', [ $plugin, 'ajax_get_votes']);
        add_action( 'wp_ajax_nopriv_get_votes',  [ $plugin, 'ajax_get_votes' ] );

        add_action( 'p2p_created_connection', [ $plugin, 'action_p2p_new_connection']);

        add_action( 'match_vote_made', [ $plugin, 'realtime_polling_result'], 10, 3);

        add_action( 'save_post', [ $plugin, 'update_polling_result'], 10, 1 );


    }

    /**
     *
     */
    function __construct() {


    }

    /**
     * @return mixed
     */
    public function getObjectId() {
        return $this->object_id;
    }

    /**
     * @param mixed $object_id
     */
    public function setObjectId($object_id) {
        $this->object_id = $object_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMatchId() {
        return $this->match_id;
    }

    /**
     * @param mixed $match_id
     */
    public function setMatchId($match_id) {
        $this->match_id = $match_id;

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
    public function getTeamId() {
        return $this->team_id;
    }

    /**
     * @param mixed $team_id
     */
    public function setTeamId($team_id) {
        $this->team_id = $team_id;

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

    public function realtime_polling_result( $current_user_id, $object_id, $vote_type ){

        $tournament_id = 0;

        switch(get_post_type($object_id)){

            case tournamentCPT::$post_type :

                $tournament_id = $object_id;

                break;

            case matchCPT::$post_type :

                $tournament_id = matchCPT::get_match_tournament_id($object_id);

                $votes = new userPolling();

                $object_votes = $votes->setObjectId($object_id)->get_votes();

                $result['polling'][$object_id] = $object_votes;

                break;
        }

        //setsub
        $result['subscription'] =  'live';

        if (class_exists('ZMQContext')) {
            //send to realtime
            $context = new ZMQContext();
            $socket  = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
            $socket->connect("tcp://localhost:5555");

            $socket->send(json_encode($result));
        }


    }

    /**
     *
     */
    public static function register_p2p_connections() {

        p2p_register_connection_type(array(
            'name'      => 'player_vote',
            'from'      => 'user',
            'to'        => [matchCPT::$post_type, playerCPT::$post_type],
            'admin_box' => array(
                'show'    => 'to',
                'context' => 'advanced'
            ),
            'title'     => array(
                'to' => __('Polling - Vote', 'PLTM')
            ),
            'fields'    => array(
                'tournament_id' => array(
                    'title'  => 'Tournament',
                    'type'   => 'custom',
                    'render' => 'userPolling::p2p_display_tournament'
                ),
                'vote'          => array(
                    'title'  => 'Vote Type',
                    'type'   => 'select',
                    'values' => apply_filters('tournament_player_status', self::$vote_type)
                )
            )
        ));


    }


    /**
     *
     */
    public static function vote() {

        check_ajax_referer('security-' . date('dmy'), 'security');

        global $current_user;
        get_currentuserinfo();

        $vote_on       = $_POST['vote_on'];
        $team_id       = $_POST['team_id'];

        $vote_type = self::get_vote_type($vote_on);

        $meta = [
            'date'          => current_time('mysql'),
            'tournament_id' => ( get_post_type($vote_on) == matchCPT::$post_type ? matchCPT::get_match_tournament_id($vote_on) : $vote_on ),
            'vote'          => $vote_type
        ];

        if(isset($team_id)){
            $meta['team'] = $team_id;
        }

        if(get_post_type($vote_on) === matchCPT::$post_type && !isset($team_id)){
            
            echo json_encode(array('result' => false, 'message' => 'ERROR: To vote on match a team ID is needed'));

            die();
        }


        $result = p2p_type('player_vote')->connect($current_user->ID, $vote_on, $meta);

        if (is_wp_error($result)) {

            echo json_encode(array('result' => false, 'message' => $result->get_error_message()));

            die();

        } else {

            do_action('match_vote_made', $current_user->ID, $_POST['vote_on'], $vote_type);

            echo json_encode(array('result' => true, 'message' => 'Vote has been placed.'));

            die();

        }
    }

    public static function get_vote_type($object_id) {

        $keys = array_keys(self::$vote_type);

        switch (get_post_type($object_id)) {

            case matchCPT::$post_type :

                return $keys[1];

                break;

            case playerCPT::$post_type :

                return $keys[0];

                break;

        }

    }


    /**
     * @param $match_id
     * @return array
     */
    public function get_votes() {

        global $wpdb;

        $query = $wpdb->prepare(
            "
                SELECT
                    (SELECT meta_value FROM $wpdb->p2pmeta WHERE p2p_id = p2p.p2p_id AND meta_key = 'team') AS team,
                    count(p2p_to) AS votes
                      FROM $wpdb->p2p as p2p WHERE p2p_type = 'player_vote' AND p2p_to = %s GROUP BY team
                ",
            $this->object_id
        );

        $votes = $wpdb->get_results( $query );

        return $votes;


    }

    public function ajax_get_votes() {

        $votes = new userPolling();

        $object_votes = $votes->setObjectId($_POST['match_id'])->get_votes();

        echo json_encode($object_votes);

        die();

    }

    public function has_voted() {

        global $wpdb;

        $query = $wpdb->prepare(
            "
                SELECT *
                      FROM $wpdb->p2p as p2p WHERE p2p_type = 'player_vote' AND p2p_from = %s AND p2p_to = %s
                ",
            $this->user_id,
            $this->object_id
        );

        $votes = $wpdb->get_results( $query );

        if(empty($votes))
            return false;

        return true;


    }

    public static function is_polling() {

        global $post;

        if (get_post_meta($post->ID, 'polling_enabled', true))
            return true;

        return false;

    }

    public function action_p2p_new_connection($p2p_id) {

        if (!is_admin())
            return;

        $connection = p2p_get_connection($p2p_id);

        switch ($connection->p2p_type) {

            case "player_vote" :

                $tournament_id = matchCPT::get_match_tournament_id($connection->p2p_to);

                p2p_add_meta($p2p_id, 'tournament_id', $tournament_id);

                break;


        }

    }

    public static function p2p_display_tournament($connection, $direction) {

        return get_the_title(p2p_get_meta($direction->name[1], 'tournament_id', true));

    }


    public static function update_polling_result($post_id){

        $live_page_id = tournament_in_progress::get_live_page_id();

        if(get_post_meta($live_page_id, 'current_match', true) == $post_id){

            self::realtime_polling_result(false, $post_id, false);

        }

    }
}
