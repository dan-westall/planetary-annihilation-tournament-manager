<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Match_Generator{

    private $tournament_id;

    private $players;

    private $tournament;

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

    function __construct() {

    }

    public static function register(){

        $plugin = new self();

        add_action( 'add_meta_boxes', [ $plugin, 'register_meta_box' ] );

        add_action( 'wp_ajax_generate_tournament_matches', [ $plugin, 'ajax_generate_tournament_matches'], 10 );

        add_action( 'admin_enqueue_scripts',  [ $plugin, 'register_scripts'] );
    }

    public static function register_scripts(){

        wp_register_script(
            'WPTM-Match-Generator', plugins_url( 'admin/assets/js/admin.js', dirname(__FILE__)  ), array( 'jquery' ), WP_Tournament_Manager::VERSION
        );


    }

    public function generate_tournament_matches(){


        $tournament = new WPTM_Tournament_Helper($this->get_tournament_id());

        $test = $tournament->get_tournament_matches();

        //if has matches delete
        if( false !== ( $matches = $tournament->get_tournament_matches() ) ){

            //could be moved an array_map function
            foreach($matches as $match){
                wp_delete_post($match, true);

            }

        }

        //getPlayers, change to format needed
        $tournament->get_tourament_players();




        //get return schedule

        //loop results and build matches

        $match_name = sprintf(
            '%s$1c' );

        $new_match = [
            'post_type'    => matchCPT::$post_type,
            'post_title'   => $match_name,
            'post_status'  => 'publish',
            'post_content' => 'start'
        ];


        $match_id  = wp_insert_post($new_match);

        $p2p_result = p2p_type('tournament_matches')->connect($this->get_tournament_id(), $match_id, [
            'date'                    => current_time('mysql'),
        ]);




    }

    public function register_meta_box(){

        add_meta_box( 'generate_match_submit', __( 'Match Generation', 'wp-tournament-manager' ), [ $this, 'generate_match_submit' ], tournamentCPT::$post_type, 'side', 'core' );

    }

    public function generate_match_submit( $post ) {

        wp_enqueue_script('WPTM-Match-Generator');

        require_once WPTM_PLUGIN_DIR . '/admin/views/widget-match-generator.php';

    }

    public function ajax_generate_tournament_matches(){

        check_ajax_referer( 'generate-matches', 'security' );

        $tournament_id = intval($_POST['tournament_id']);

        $this->set_tournament_id($tournament_id);

        $tournament = new WPTM_Tournament_Helper($this->get_tournament_id());

        if( $tournament->get_tournament_type() === 'Round Robin' && $tournament->get_tournament_status() === tournamentCPT::$tournament_status[4] ){


            $this->generate_tournament_matches();


        }



        wp_send_json_success([ 'result' => 'done' ]);

    }

}