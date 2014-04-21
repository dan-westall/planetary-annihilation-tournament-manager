<?php

class matchCPT {

    public static $post_type = 'match';

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_match') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_shortcode('tournament-matches', array( $this, 'match_listing') );

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
                'special' => array(
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

        $matches = array();

        $row = 0;

        $args = array(
            'post_type'       => self::$post_type,
            'connected_type'  => 'posts_to_pages',
            'connected_items' => $tournament_id,
            'nopaging'        => true
        );

        p2p_update_meta();

        wp_send_json_success($data);

    }

}