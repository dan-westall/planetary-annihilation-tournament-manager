<?php

class matchCPT {

    public static $post_type = 'match';

    public static $match_status = array( 'Open', 'Pending', 'Complete');

    public static $match_format = array('Verses', 'FFA', 'Team');

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_match') );
        add_action( 'init', array( $this, 'register_cpt_taxonomies') );
        add_action( 'init', array( $this, 'populate_taxonomy_terms') );
        //add_action( 'template_include', array( $this, 'get_match_results') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_shortcode('tournament-matches', array( $this, 'get_match_results') );

        add_filter( 'wp_insert_post_data',  array( $this, 'default_comments_on' ) );

        add_filter( 'json_prepare_post',  array( $this, 'extend_json_api' ), 100, 3 );



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
                'show_in_json'        => true,
                'menu_position'       => 10,
                'menu_icon'           => 'dashicons-video-alt3',
                'supports'            => array('title', 'thumbnail', 'comments')
            );

        register_post_type( self::$post_type, $matchArgs );



    }

    function register_cpt_taxonomies(){

        $labels = array(
            'name'              => _x( 'Match Status', 'taxonomy general name' ),
            'singular_name'     => _x( 'Match Status', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Match Status' ),
            'all_items'         => __( 'All Match Status' ),
            'parent_item'       => __( 'Parent Match Status' ),
            'parent_item_colon' => __( 'Parent Match Status:' ),
            'edit_item'         => __( 'Edit Match Status' ),
            'update_item'       => __( 'Update Match Status' ),
            'add_new_item'      => __( 'Add New Match Status' ),
            'new_item_name'     => __( 'New Match Status Name' ),
            'menu_name'         => __( 'Match Status' ),
        );

        $args = array(
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'match-status' ),
        );

        register_taxonomy( 'match_status', self::$post_type, $args );        
        
        $labels = array(
            'name'              => _x( 'Match Format', 'taxonomy general name' ),
            'singular_name'     => _x( 'Match Format', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Match Formats' ),
            'all_items'         => __( 'All Match Format' ),
            'parent_item'       => __( 'Parent Match Format' ),
            'parent_item_colon' => __( 'Parent Match Format:' ),
            'edit_item'         => __( 'Edit Match Format' ),
            'update_item'       => __( 'Update Match Format' ),
            'add_new_item'      => __( 'Add New Match Format' ),
            'new_item_name'     => __( 'New Match Format Name' ),
            'menu_name'         => __( 'Match Format' ),
        );

        $args = array(
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'match-format' ),
        );

        register_taxonomy( 'match_format', self::$post_type, $args );

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
                ),
                'team' => array(
                    'title' => 'Team',
                    'type' => 'select',
                    'values' => range(0, 10)
                )
            )
        ) );

        p2p_register_connection_type( array(
            'name' => 'match_commentators',
            'from' => self::$post_type,
            'to' => 'user',
            'sortable' => 'from',
            'title' => array(
                'from' => __( 'Match Commentators', 'PLTM' )
            ),
            'admin_box' => array(
                'show' => 'from',
                'context' => 'side'
            )
        ) );

    }

    function populate_taxonomy_terms(){

        foreach(array('match_status', 'match_format') as $taxonomy){

            // Match Formats
            $terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );

            // if no terms then lets add our terms
            if( empty( $terms ) ){

                $terms = self::${$taxonomy};

                foreach( $terms as $term ){

                    if( !term_exists( $term, $taxonomy ) ){

                        wp_insert_term( $term, $taxonomy, array( 'slug' => strtolower(str_replace(' ', '-', $term)) ) );

                    }
                }
            }

        }

    }

    public static function get_match_results($attr) {

        extract(shortcode_atts(array(
            'tournament_id' => '',
            'output'        => 'html',
            'match_id'      => '',
            'autoreload'    => false
        ), $attr));

        $args = array(
            'post_type' => self::$post_type
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
                    'winner'             => p2p_get_meta($player->p2p_id, 'winner', true),
                    'team'               => p2p_get_meta($player->p2p_id, 'team', true)
                );

            }


            $data[$row]['wp_match_id'] = $matches[$row]->ID;
            $data[$row]['title']       = $matches[$row]->post_title;
            $data[$row]['players']     = $match_players;

            $data[$row]['challonge_tournament_id'] = get_post_meta($matches[$row]->ID, 'challonge_tournament_id', true);
            $data[$row]['challonge_match_id']      = get_post_meta($matches[$row]->ID, 'challonge_match_id', true);
            $data[$row]['pa_stats_match_id']       = get_post_meta($matches[$row]->ID, 'pa_stats_match_id', true);
            $data[$row]['pa_stats_start']          = get_post_meta($matches[$row]->ID, 'pa_stats_start', true);
            $data[$row]['pa_stats_stop']           = get_post_meta($matches[$row]->ID, 'pa_stats_stop', true);
            $data[$row]['twitch']                  = get_post_meta($matches[$row]->ID, 'twitch', true);
            $data[$row]['match_round']             = get_post_meta($matches[$row]->ID, 'match_round', true);
            $data[$row]['last_update']             = get_post_meta($matches[$row]->ID, 'last_update', true); //todo to be played, in progress, completed??
            $data[$row]['match_url']               = get_permalink($matches[$row]->ID);

            $data[$row]['videos']               = get_match_videos($matches[$row]->ID);


        }

        switch($output){

            case "json":

                wp_send_json($data);

                break;

            case "html" :

                self::match_listing_js_deps();

                $matchopts = array($tournament_id,$autoreload);

                return self::match_listing_template($matchopts);

                break;
        }
    }

    public static function match_return_format($match, $data = array(), $return = array('results' => true, 'prize' => true)){


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
        wp_register_script('match_listing', PLTM_PLUGIN_URI . 'public/assets/js/matchlisting.js' );
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

    public static function match_up($attr){

        extract(shortcode_atts(array(
            'date' => '',
            'size' => '',
            'match_id' => ''
        ), $attr));

        $players = p2p_type('match_players')->get_connected($match_id);

        foreach($players->posts as $player){

            $winner               = $title = '';
            $player_user_id       = get_post_meta($player->ID, 'user_id', true);
            $player_profile_image = playerCPT::get_player_avatar($player->ID);

            if(p2p_get_meta($player->p2p_id, 'winner', true)){
                $winner = 'winner';
            }

            if (get_user_meta($player_user_id, 'title', true)){
                $title = sprintf('<span>%s</span>', get_user_meta($player_user_id, 'title', true));
            }


            $match_card[] = sprintf(
                '<div class="col-lg-5">
                    <div class="player-match-card %5$s">
                        <div class="player-match-card-inner row text">
                            <div class="player-avatar col-lg-4">
                                <a href="%2$s">%1$s</a>
                            </div>
                            <div class="player-details col-lg-7">
                                <h4 class="player-name"><a href="%2$s">%3$s</a></h4>
                                %4$s
                            </div>
                            <div class="match-result col-lg-1">Winner</div>
                        </div>
                    </div>
                </div>',
                $player_profile_image,
                get_permalink($player->ID),
                $player->post_title,
                $title,
                $winner
            );

        }

        $vs = '<div class="vs col-lg-2"><h2>VS</h2></div>';

        $match_cards = implode($vs, $match_card);

        $html = sprintf(
            '<section class="player-matchup row">%s</section>',
            $match_cards
        );

        return $html;

    }

    public function default_comments_on( $data ) {
        if( $data['post_type'] == self::$post_type ) {
            $data['comment_status'] = 'open';
        }

        return $data;
    }

    public function extend_json_api($_post, $post, $context){

        if($post['post_type'] == 'match'){

            //dont need author
            unset($_post['author']);

            $comments   = wp_count_comments( $post['ID']);
            $tournament = p2p_type('tournament_matches')->set_direction('to')->get_connected($post['ID']);
            $players    = p2p_type('match_players')->get_connected($post['ID']);

            if(isset($tournament->posts[0]->ID)){

                $_post['meta']['tournament']['name'] = $tournament->posts[0]->post_title;
                $_post['meta']['tournament']['url']  = get_permalink($tournament->posts[0]->ID);

            }

            foreach ($players->posts as $player) {

                $match_players[] = array(
                    'wp_player_id'       => $player->ID,
                    'player_name'        => $player->post_title,
                    'pa_stats_player_id' => get_post_meta($player->ID, 'pastats_player_id', true),
                    'winner'             => p2p_get_meta($player->p2p_id, 'winner', true),
                    'team'               => p2p_get_meta($player->p2p_id, 'team', true),
                    'url'                => get_permalink($player->ID)
                );

            }

            $_post['meta']['players'] = $match_players;
            $_post['meta']['comment_count'] = $comments->approved;
            $_post['meta']['video'] =  get_match_videos($post['ID']);
            $_post['meta']['pa_stats_id'] = get_post_meta($post['ID'], 'pa_stats_match_id', true);

        }



        return $_post;

    }

}
