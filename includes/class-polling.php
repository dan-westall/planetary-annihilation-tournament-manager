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

        add_action('p2p_init', [ $plugin, 'register_p2p_connections']);

        add_action('wp_ajax_tournament_vote', [ $plugin, 'tournament_vote']);
        add_action('wp_ajax_match_vote', [ $plugin, 'match_vote']);

        add_action( 'p2p_created_connection', [ $plugin, 'action_p2p_new_connection' ] );

    }

    /**
     *
     */
    function __construct() {


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
    public static function tournament_vote() {

        check_ajax_referer('security-' . date('dmy'), 'security');


        $result = p2p_type('player_vote')->connect($_POST['current_user_id'], $_POST['player_id'], array(
            'date'          => current_time('mysql'),
            'tournament_id' => $_POST['tournament_id'],
            'vote'          => 'tournament_win'
        ));

        if ($result) {

            echo json_encode(array('result' => true, 'message' => 'Vote has been placed.'));

            die();

        }
    }

    /**
     *
     */
    public static function match_vote() {

        check_ajax_referer('security-' . date('dmy'), 'security');

        $match_id = $_POST['match_id'];

        switch (get_match_format($_POST['match_id'])) {

            case "format-vs" :
            case "format-ffs" :

                //matchCPT::get_player get_match_players_by_card

                $result = p2p_type('player_vote')->connect($_POST['current_user_id'], $_POST['player_id'], array(
                    'date'          => current_time('mysql'),
                    'tournament_id' => $_POST['tournament_id'],
                    'match_id'      => $match_id,
                    'vote'          => 'match_win'
                ));

                do_action('match_vote_made', $_POST['player_id'], $match_id);

                break;

            case "format-vs-team" :
            case "format-vs-team-clan" :

                $team = $_POST['team'];

                $result = p2p_type('player_vote')->connect($_POST['current_user_id'], $player, array(
                    'date'          => current_time('mysql'),
                    'tournament_id' => $_POST['tournament_id'],
                    'match_id'      => $match_id,
                    'vote'          => 'match_win'
                ));

                do_action('match_team_vote_made', $team, $match_id);

                break;

        }

        if ($result) {

            echo json_encode(array('result' => true, 'message' => 'Vote has been placed.'));

            die();

        }
    }

    /**
     * @return array
     */
    function get_match_votes() {

        global $wpdb;

        $connected = get_posts( array(
            'connected_type' => 'player_vote',
            'connected_meta' => array(
                array(
                    'key' => 'match_id',
                    'value' => $this->match_id
                )
            ),
            'nopaging' => true,
            'suppress_filters' => false
        ) );


        return [];

    }

    /**
     * @param $match_id
     * @return array
     */
    public function get_tournament_votes() {

        global $wpdb;

        $connected = get_posts( array(
            'connected_type' => 'player_vote',
            'connected_meta' => array(
                array(
                    'key' => 'tournament_id',
                    'value' => $this->tournament_id
                )
            ),
            'nopaging' => true,
            'suppress_filters' => false
        ) );

        return [];

    }

    public function get_vote(){

        $connected = get_posts( array(
            'connected_type' => 'player_vote',
            'connected_meta' => array(
                array(
                    'key' => 'tournament_id',
                    'value' => $this->tournament_id
                )
            ),
            'nopaging' => true,
            'suppress_filters' => false
        ) );

    }

    public function has_voted(){




    }

    public static function is_polling(){

        global $post;

        if(get_post_meta($post->ID, 'polling_enabled', true))
            return true;

        return false;

    }

    public function action_p2p_new_connection( $p2p_id ){

        if(!is_admin())
            return;

        $connection = p2p_get_connection( $p2p_id );

        switch($connection->p2p_type){

            case "player_vote" :

                $tournament_id = matchCPT::get_match_tournament_id($connection->p2p_to);

                p2p_add_meta( $p2p_id, 'tournament_id', $tournament_id );

                break;


        }

    }

    public static function p2p_display_tournament($connection, $direction){

        return get_the_title(p2p_get_meta($direction->name[1], 'tournament_id', true));

    }

}

//aim

//$userVote = new userVote();

///$userVote->get_player($player_id)->match_votes($match_id);
///$userVote->get_player($player_id)->tournament_votes($tournament_id);
///$userVote->get_match($match_id)->get_team();

$votes = new userPolling();

$votes->setMatchId(0)->get_match_votes();
//$votes->setMatchId(0)->get_match_votes();


//$votes->setTournamentId()->setPlayerId()->get_vote();
