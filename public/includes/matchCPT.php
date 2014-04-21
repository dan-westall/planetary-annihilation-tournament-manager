<?php

class matchCPT {

    public static $post_type = 'match';

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_match') );
        //add_action( 'template_include', array( $this, 'get_match_results') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_shortcode('tournament-matches', array( $this, 'match_listing') );

        add_action('wp_ajax_pltm_get_match_results',  array( $this, 'get_match_results') );
        add_action('wp_ajax_nopriv_pltm_get_match_results',  array( $this, 'get_match_results') );

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

    public function get_match_results($attr) {

        extract(shortcode_atts(array(
            'tournament_id' => ''
        ), $attr));

        $args = array(
            'post_type'       => self::$post_type,
            'connected_type'  => 'tournament_matches',
            'connected_items' => $_GET['tournament_id'],
            'nopaging'        => true
        );

        $matches = get_posts($args);

        for ($row = 0; $row < count($matches); $row++) {

            $tournament_players = array();
            $players            = p2p_type('match_players')->get_connected($matches[$row]);

            foreach ($players->posts as $player) {

                $tournament_players[] = array(
                    'player_name'        => $player->post_title,
                    'pa_stats_player_id' => get_post_meta($player->ID, 'pastats_player_id', true),
                    'winner'             => p2p_get_meta($player->p2p_id, 'winner', true)
                );

            }

            $data[$row]['title']   = $matches[$row]->post_title;
            $data[$row]['players'] = $tournament_players;

            $data[$row]['pa_stats_match_id']       = get_post_meta($matches[$row]->ID, 'pa_stats_match_id', true);
            $data[$row]['challonge_tournament_id'] = get_post_meta($matches[$row]->ID, 'challonge_tournament_id', true);
            $data[$row]['status'] = ''; //todo to be played, in progress, completed??

        }

        wp_send_json_success($data);

    }

}