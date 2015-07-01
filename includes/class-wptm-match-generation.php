<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Match_Generator{

    private $tournament_id;

    private $players;

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
    }

    function __construct() {

    }

    public static function register(){

        $plugin = new self();

        add_action( 'add_meta_boxes', [ $plugin, 'register_meta_box' ] );

        add_action( 'wp_ajax_generate_tournament_matches', [ $plugin, 'generate_tournament_matches'] );

        add_action( 'wp_enqueue_scripts',  [ $plugin, 'register_scripts'] );
    }

    public static function register_scripts(){

        wp_register_script(
            'WPTM-Match-Generator',
            WPTM_PLUGIN_URI. 'admin/assets/js/admin.js',
            [],
            date('U'),
            true
        );


    }

    public function generate_tournament_matches(){

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

        wp_enqueue_script('signupForm');

        require_once WPTM_PLUGIN_DIR . '/admin/views/widget-match-generator.php';

    }

}