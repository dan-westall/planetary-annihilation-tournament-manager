<?php

class tournamentCPT {

    public static $post_type = 'tournament';

    function __construct() {

        add_action( 'init', array( $this, 'pace_register_cpt_tournament') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );
        add_action( 'gform_after_submission', array( $this, 'signup_tournament_player'), 10, 2);
        add_action( 'template_include', array( $this, 'load_endpoint_template')  );

        add_filter( 'tournament_rounds', array( $this, 'filter_tournament_rounds' ) );

    }

    function pace_register_cpt_tournament(){

        $tournamentLabel = array(
            'name'               => __('Tournaments'),
            'menu_name'          => __('Tournament'),
            'all_items'          => __('All Tournaments'),
            'singular_name'      => __('Tournament'),
            'add_new_item'       => __('Add New Tournament'),
            'edit_item'          => __('Edit Tournament'),
            'new_item'           => __('New Tournament'),
            'view_item'          => __('View Tournament'),
            'search_items'       => __('Search Tournaments'),
            'not_found'          => __('No Tournaments found'),
            'not_found_in_trash' => __('No Tournaments found in trash')
        );

        $tournamentArgs = array(
            'labels'                  => $tournamentLabel,
                'description'         => 'Description',
                'public'              => true,
                'has_archive'         => true,
                'exclude_from_search' => true,
                'show_ui'             => true,
                'menu_position'       => 10,
                'menu_icon'           => 'dashicons-networking',
                'supports'            => array('title', 'editor', 'thumbnail')
            );

        register_post_type( self::$post_type, $tournamentArgs );

    }

    public function register_p2p_connections() {

        p2p_register_connection_type( array(
            'name' => 'tournament_staff',
            'from' => self::$post_type,
            'to' => 'user',
            'sortable' => 'from',
            'admin_box' => array(
                'show' => 'from',
                'context' => 'advanced'
            ),
            'title' => array(
                'from' => __( 'Tournament Staff', 'my-textdomain' )
            ),
            'fields' => array(
                'role' => array(
                    'title' => 'Role',
                    'type' => 'select',
                    'values' => apply_filters('tournament_staff_roles', array( 'Caster - Lead', 'Caster', 'Official', 'Director' ) )
                ),
                'job' => array(
                    'title' => 'Job',
                    'type' => 'text',
                ),
            )
        ) );

        p2p_register_connection_type( array(
            'name' => 'tournament_planets',
            'from' => self::$post_type,
            'to' => planetCPT::$post_type,
            'sortable' => 'from',
            'admin_box' => array(
                'show' => 'from',
                'context' => 'advanced'
            ),
            'title' => array(
                'from' => __( 'Tournament Planets', 'my-textdomain' )
            ),
            'fields' => array(
                'role' => array(
                    'title' => 'Round',
                    'type' => 'select',
                    'values' => apply_filters('tournament_rounds', array( ) )
                )
            )
        ) );


        p2p_register_connection_type( array(
            'name' => 'tournament_players',
            'from' => self::$post_type,
            'to' => 'player',
            'admin_box' => array(
                'show' => 'from',
                'context' => 'advanced'
            )
        ) );

    }

    public function load_endpoint_template($template_path){

        global $wp_query, $post;

        foreach(Pace_League_Tournament_Manager::$endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

                $template_path = get_template_directory() . "/single-$endpoint-$post->post_type.php";

                if(file_exists($template_path)){
                    return $template_path;
                }

            }
        }

        return $template_path;
    }


    public function filter_tournament_rounds($rounds){

        global $post;

        $post = get_post($_GET['post']);

        if(is_object($post)){

            if($post->post_type == "tournament"){

                for($round = 1; $round <= get_post_meta($post->ID, 'rounds', true); $round ++){

                    $rounds[$round] = sprintf('Round %s', $round);

                }

            }

        }

        return $rounds;

    }

    public function signup_tournament_player($entry, $form) {

        global $wpdb;

        $signup_form_id = get_field('standard_tournament_signup_form', 'option');
        $tournament_id  = url_to_postid($entry['source_url']);

        //if tournament 0 bin
        if ($tournament_id === 0 || get_field('signup_closed', $tournament_id) !== true)
            return false;

        if ($signup_form_id && get_field('signup_form')) {
            $signup_form_id = get_field('signup_form');
        }

        //todo create mapping function so form fields are not hard coded by id.

        if ($signup_form_id == $entry['form_id']) {


            //todo email shouldnt be stored with the player profile CTP should be linked either by p2p or meta int
            $find_player = array(
                'post_type'      => self::POST_TYPE,
                'meta_query'     => array(
                    array(
                        'key'   => 'player_email',
                        'value' => $entry['3']
                    )
                ),
                'posts_per_page' => 1
            );

            $players = get_posts($find_player);

            //existing player check email
            if (!empty($players)) {

                $connection_meta = array(
                    'date' => current_time('mysql')
                );

                //if ladder information is entered then add that to the connection meta
                if (false) {
                    $connection_meta['ladder'] = 0;
                }

                //log clan at current time
                if (!empty($entry['2'])) {
                    $connection_meta['clan'] = 0;
                }

                //player found add player to tornament
                p2p_type('tournament_players')->connect($tournament_id, $players[0]->ID, $connection_meta);

                //add player to current challonge tournament


            } else {

                //new user accounts have been created to provide features going forward
                //TODO discuss with group merit for creating user accounts.
                $userdata = array(
                    'user_login' => $entry['3'],
                    'user_email' => $entry['3']
                );

                $user_id = wp_insert_user($userdata);

                //create new player post
                $new_player = array(
                    'post_title'  => $entry['5'],
                    'post_status' => 'publish',
                    'post_author' => $user_id,
                    'post_type'   => self::POST_TYPE
                );

                // Insert the post into the database
                $player_id = wp_insert_post($new_player);

                update_post_meta($player_id, 'player_email', $entry['3']);
                update_post_meta($player_id, 'user_id', $user_id);

                update_user_meta($user_id, 'player_id', $player_id);

                $connection_meta = array(
                    'date' => current_time('mysql')
                );

                //if ladder information is entered then add that to the connection meta
                if (false) {
                    $connection_meta['ladder'] = 0;
                }

                //log clan at current time
                if (!empty($entry['2'])) {
                    $connection_meta['clan'] = 0;
                }

                //player found add player to tornament
                p2p_type('tournament_players')->connect($tournament_id, $player_id, $connection_meta);

            }


            //new player


        }


    }
}