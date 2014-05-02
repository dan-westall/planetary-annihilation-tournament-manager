<?php

class matchCPT {

    public static $post_type = 'match';

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_match') );
        //add_action( 'template_include', array( $this, 'get_match_results') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_shortcode('tournament-matches', array( $this, 'get_match_results') );

        //moved outside to our own api endpoint
//        add_action('wp_ajax_pltm_get_match_results',  array( $this, 'get_match_json') );
//        add_action('wp_ajax_nopriv_pltm_get_match_results',  array( $this, 'get_match_json') );

    }

    function register_cpt_match(){

        $matchLabel = array(
            'name'               => __('Matchs'),
            'menu_name'          => __('Match'),
            'all_items'          => __('All Matchs'),
            'singular_name'      => __('Match'),
            'add_new_item'       => __('Add New Match'),
            'edit_item'          => __('Edit Match'),
            'new_item'           => __('New Match'),
            'view_item'          => __('View Match'),
            'search_items'       => __('Search Matchs'),
            'not_found'          => __('No Matchs found'),
            'not_found_in_trash' => __('No Matchs found in trash')
        );

        $matchArgs = array(
            'labels'                  => $matchLabel,
                'description'         => 'Description',
                'public'              => true,
                'has_archive'         => true,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'menu_position'       => 10,
                'menu_icon'           => 'dashicons-video-alt3',
                'supports'            => array('title', 'thumbnail')
            );

        register_post_type( self::$post_type, $matchArgs );

    }

    public function register_p2p_connections(){

        p2p_register_connection_type( array(
            'name' => 'match_players',
            'from' => self::$post_type,
            'to' => playerCPT::$post_type,
            'admin_box' => array(
                'show' => 'any',
                'context' => 'side'
            ),
            'fields' => array(
                'winner' => array(
                    'title' => 'Winner',
                    'type' => 'checkbox'
                )
            )
        ) );

    }

    public static function get_match_results($attr) {

        extract(shortcode_atts(array(
            'tournament_id' => '',
            'output'        => 'html',
            'match_id'      => ''
        ), $attr));

        $args = array(
            'post_type'       => self::$post_type,
        );

        //if tournament id is set then do linking to matches
        if(!empty($tournament_id)){

            $args['connected_type']  = 'tournament_matches';
            $args['connected_items'] = $tournament_id;
            $args['nopaging']        = true;

        } else if(!empty($match_id)){

            //if match_id is set then get the wp id by default challnonge match id is used.

            $wp_match_id = self::get_match_by($match_id);

            $args['post__in'] = array($wp_match_id->ID);

        }

        $matches = get_posts($args);

        for ($row = 0; $row < count($matches); $row++) {

            $match_players = array();
            $players            = p2p_type('match_players')->get_connected($matches[$row]);

            foreach ($players->posts as $player) {

                $match_players[] = array(
                    'player_name'        => $player->post_title,
                    'pa_stats_player_id' => get_post_meta($player->ID, 'pastats_player_id', true),
                    'winner'             => p2p_get_meta($player->p2p_id, 'winner', true)
                );

            }

            $data[$row]['title']   = $matches[$row]->post_title;
            $data[$row]['players'] = $match_players;

            $data[$row]['challonge_tournament_id'] = get_post_meta($matches[$row]->ID, 'challonge_tournament_id', true);
            $data[$row]['challonge_match_id']      = get_post_meta($matches[$row]->ID, 'challonge_match_id', true);
            $data[$row]['pa_stats_match_id']       = get_post_meta($matches[$row]->ID, 'pa_stats_match_id', true);
            $data[$row]['pa_stats_start']          = get_post_meta($matches[$row]->ID, 'pa_stats_start', true);
            $data[$row]['pa_stats_stop']           = get_post_meta($matches[$row]->ID, 'pa_stats_stop', true);
            $data[$row]['last_update']             = get_post_meta($matches[$row]->ID, 'last_update', true); //todo to be played, in progress, completed??

        }

        switch($output){

            case "json":

                wp_send_json($data);

                break;

            case "html" :

                self::match_listing_js_deps();

                return self::match_listing_template($tournament_id);

                break;
        }
    }

    public static function match_listing_template($vars = array()) {
        ob_start();

        include( PLTM_PLUGIN_DIR . '/public/views/matchlisting_shortcode.php');

        return ob_get_clean();
    }

    public static function match_listing_js_deps() {
        //wp_register_script('knockout',plugins_url('/public/js/ko.min.js',__FILE__) );
        wp_enqueue_script('custom.knockout');
        //wp_register_script('socketio',":5000/socket.io/socket.io.js");
        //wp_enqueue_script('socketio');
        wp_register_script('match_listing',PLTM_PLUGIN_URI . 'public/assets/js/matchlisting.js', array('custom.knockout') );
        wp_enqueue_script('match_listing');
    }

    public static function get_match_by($id, $switch = 'challonge_match_id'){

        switch($switch) {

            case "challonge_match_id" :

                $match = DW_Helper::get_post_by_meta('challonge_match_id', $id);

                break;

        }

        return $match;

    }

    public static function get_match_tournament_id($match_id){

        $tournament_id = tournamentCPT::get_tournament_id_by(get_post_meta($match_id, 'challonge_tournament_id', true));

        //Cheap way if fail fail back to p2p
        if(!$tournament_id){

            $tournament = p2p_type( 'tournament_matches' )->set_direction( 'to' )->get_connected( $match_id );

            if(isset($tournament->posts[0]->ID)){

                $tournament_id = $tournament->posts[0]->ID;

            }

        }

        return $tournament_id;
    }
}