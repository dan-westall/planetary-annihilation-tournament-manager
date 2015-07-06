<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Match_Generator{

    private $tournament_id;

    private $players;

    private $tournament;

    private $match_list = [];

    /**
     * @return mixed
     */
    public function get_tournament_id() {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function set_tournament_id($tournament_id) {

        $this->tournament_id = $tournament_id;


        return $this;
    }

    public function get_match_list(){

        return $this->match_list;

    }

    public function set_match_list($match_id){

        $this->match_list[] = $match_id;

        return $this;
    }

    function __construct() {

    }

    public static function register(){

        $plugin = new self();

        add_action( 'add_meta_boxes', [ $plugin, 'register_meta_box' ] );

        add_action( 'wp_ajax_ajax_generate_tournament_matches', [ $plugin, 'ajax_generate_tournament_matches'], 10, 0 );

        add_action( 'admin_enqueue_scripts',  [ $plugin, 'register_scripts'], 10 , 0 );
    }

    public static function register_scripts(){

        wp_register_script(
            'WPTM-Match-Generator', plugins_url( 'admin/assets/js/admin.js', dirname(__FILE__)  ), array( 'jquery' ), WP_Tournament_Manager::VERSION
        );

    }

    public function generate_tournament_matches($groups = false){


        $tournament = new WPTM_Tournament_Helper($this->get_tournament_id());

        $test = $tournament->get_tournament_matches();

        //if has matches delete
        if( false !== ( $matches = $tournament->get_tournament_matches()->posts ) ){

            array_map(function($match){

                wp_delete_post($match->ID, true);

            }, $matches);

        }

        //getPlayers, change to format needed
        $players = $tournament->get_tourament_players();

        if($groups){

            $tournament_player_group = [];

            foreach($players as $player){

                $group = p2p_get_meta($player->p2p_id, 'group', true);

                if(empty($group))
                    continue;

                $tournament_player_group[$group][] = $player;

            }

            foreach($tournament_player_group as $group => $players){

                $matches = WPTM_Tournament_Formats::schedule_format( (array) $players );

                $this->create_matches($matches, $group);

            }

            return true;

        }

        $matches = WPTM_Tournament_Formats::schedule_format( (array) $players );

        $this->create_matches($matches);

    }

    public function create_matches($matches, $group = null){

        foreach($matches AS $round => $games){

            foreach($games AS $play){

                $group_identifier = is_null($group) ? ($round + 1) : $group;
                $round            = ($round + 1);

                $match_name = sprintf(
                    'Group %1$s - %2$s vs %3$s',
                    $group_identifier,
                    $play["Home"]->post_name,
                    $play["Away"]->post_name);

                $new_match = [
                    'post_type'    => matchCPT::$post_type,
                    'post_title'   => $match_name,
                    'post_status'  => 'publish',
                    'post_content' => 'start'
                ];

                $match_id = wp_insert_post($new_match, true);

                if(is_wp_error($match_id)){

                    throw new Exception('Sorry there was a error, we could not create the matches for this tournament.');

                }

                if(!is_null($group)){

                    update_post_meta($match_id, 'group', $group );

                }

                update_post_meta($match_id, 'round', $round );

                $p2p_result_tm = p2p_type('tournament_matches')->connect($this->get_tournament_id(), $match_id, [
                    'date'        => current_time('mysql')
                ]);

                $p2p_result_mp_1 = p2p_type('match_players')->connect($match_id, $play["Home"]->ID, [
                    'date' => current_time('mysql'),
                    'team' => 0
                ]);

                $p2p_result_mp_2 = p2p_type('match_players')->connect($match_id, $play["Away"]->ID, [
                    'date' => current_time('mysql'),
                    'team' => 1
                ]);

                $this->set_match_list( [ 'match_title' => $match_name, 'match_id' => $match_id ] );

            }

        }

    }

    public function error_match_cleanup(){

        array_map( function( $match ) {

            wp_delete_post( $match['match_id'], true );

        }, $this->get_match_list() );

    }

    public function register_meta_box(){

        global $post;

        $tournament = new WPTM_Tournament_Helper( $post->ID );

        if( !in_array( $tournament->get_tournament_status(), [ tournamentCPT::$tournament_status[0], tournamentCPT::$tournament_status[4] ] ) ) {
            return;
        }

        add_meta_box( 'generate_match_submit', __( 'Match Generation', 'wp-tournament-manager' ), [ $this, 'generate_match_submit' ], tournamentCPT::$post_type, 'side', 'core' );

    }

    public function generate_match_submit( $post ) {

        wp_enqueue_script('WPTM-Match-Generator');

        require_once WPTM_PLUGIN_DIR . '/admin/views/widget-match-generator.php';

    }

    public function ajax_generate_tournament_matches(){

        //check_ajax_referer( 'generate-matches', 'security' );

        $tournament_id = intval($_POST['tournament_id']);
        $group_rounds  = true;

        $this->set_tournament_id($tournament_id);

        $tournament = new WPTM_Tournament_Helper($this->get_tournament_id());

        $t = $tournament->get_tournament_status();

        //if( $tournament->get_tournament_type() === 'Round Robin' && $tournament->get_tournament_status() === tournamentCPT::$tournament_status[4] ){
        if( in_array( $tournament->get_tournament_status(), [ tournamentCPT::$tournament_status[0], tournamentCPT::$tournament_status[4] ] ) ){

            try {

                $this->generate_tournament_matches($group_rounds);

            } catch( Exception $e ){

                $this->error_match_cleanup();

                do_action( "match_generation_error", $tournament_id, $e->getMessage(), $group_rounds );

                wp_send_json_error( ['message' => $e->getMessage(), 'type' => 'error'] );

            }

            do_action( "match_generation", $tournament_id, $this->get_match_list() );

            wp_send_json_success( [ 'message' => sprintf( '%s matches have been created.', count( $this->get_match_list() ) ), 'type' => 'success', 'match_list' => $this->get_match_list() ] );

        }

    }

}