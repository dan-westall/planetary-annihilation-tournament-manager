<?php

class matchCPT {

    public static $post_type = 'match';

    public static $match_status = array( 'Open', 'Pending', 'Complete');

    public static $match_format = array( 'Verses', 'FFA', 'Team');

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_match') );
        add_action( 'init', array( $this, 'register_cpt_taxonomies') );
        add_action( 'init', array( $this, 'populate_taxonomy_terms') );
        //add_action( 'template_include', array( $this, 'get_match_results') );

        add_action( 'after_setup_theme', array( $this, 'ctp_permission') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_shortcode('tournament-matches', array( $this, 'get_match_results') );

        add_filter( 'wp_insert_post_data',  array( $this, 'default_comments_on' ) );

        add_filter( 'json_prepare_post',  array( $this, 'extend_json_api' ), 100, 3 );


        add_filter('posts_fields', array( $this, 'edit_posts_fields'), 10, 100);
        add_filter('posts_orderby_request', array( $this, 'order_matches_by_tournament_date'), 10, 100);

        add_filter( 'the_title', array( $this, 'pre_title_tournament_name'), 10, 2);

        add_action( 'match_updated', array( $this, 'realtime_update_match_listing'), 10, 2);
        add_action( 'save_post',  array( $this, 'realtime_update_match_listing'), 10, 1 );

        //moved outside to our own api endpoint
//        add_action('wp_ajax_pltm_get_match_results',  array( $this, 'get_match_json') );
//        add_action('wp_ajax_nopriv_pltm_get_match_results',  array( $this, 'get_match_json') );

        add_filter( 'template_include',  array( $this, 'roster_management' ) );

        add_action( 'wp_ajax_update_team_roster',  array( $this, 'update_team_roster') );

        add_action( 'parse_query',   array( $this, 'match_api_filter'));

        add_filter( 'p2p_connection_type_args' ,   array( $this, 'test_args'), 10, 2);

    }

    public static function test_args($args, $sides){

        //

        if((get_post_type($_GET['post']) == matchCPT::$post_type || get_post_type($_REQUEST['post_ID']) == matchCPT::$post_type || get_post_type($_POST['from']) == matchCPT::$post_type) && $args['name'] == 'player_vote'){

            $args['fields']['team'] = [
                'title' => 'Team',
                'type' => 'select',
                'values' => range(0, 10)
            ];

        }


        return $args;

    }

    function register_cpt_match(){

        $matchLabel = array(
            'name'               => __('Matches'),
            'menu_name'          => __('Match'),
            'all_items'          => __('All Matches'),
            'singular_name'      => __('Match'),
            'add_new_item'       => __('Add New Match'),
            'edit_item'          => __('Edit Match'),
            'new_item'           => __('New Match'),
            'view_item'          => __('View Match'),
            'search_items'       => __('Search Matches'),
            'not_found'          => __('No Matches found'),
            'not_found_in_trash' => __('No Matches found in trash')
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
                'capability_type'     => array('match','matches'),
                'supports'            => array('title', 'thumbnail', 'comments')
            );

        register_post_type( self::$post_type, $matchArgs );



    }
    
    function ctp_permission(){

        $roles = array(
            get_role('administrator')
        );


        $caps  = array(
            'read',
            'read_'.self::$post_type.'',
            'read_private_'.self::$post_type.'es',
            'edit_'.self::$post_type,
            'edit_'.self::$post_type.'es',
            'edit_private_'.self::$post_type.'es',
            'edit_published_'.self::$post_type.'es',
            'edit_others_'.self::$post_type.'es',
            'publish_'.self::$post_type.'es',
            'delete_'.self::$post_type,
            'delete_'.self::$post_type.'es',
            'delete_private_'.self::$post_type.'es',
            'delete_published_'.self::$post_type.'es',
            'delete_others_'.self::$post_type.'es',
        );

        foreach ($roles as $role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
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
            'hierarchical'      => true,
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
                'context' => 'advanced',
                'priority' => 'high'
            ),
            'fields' => array(
                'winner' => array(
                    'title' => 'Winner',
                    'type' => 'checkbox'
                ),
                'clan' => array(
                    'title' => 'Clan',
                    'type' => 'custom',
                    'render' => 'matchCPT::p2p_display_clan'
                ),
                'team' => array(
                    'title' => 'Team',
                    'type' => 'select',
                    'values' => range(0, 10)
                )
            ),
            'sortable' => 'any'
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

            //check that we can show the match
            if(!in_array(get_post_meta($tournament_id, 'tournament_status', true), array(1,2,3,4))){

               if($output == 'json'){
                   wp_send_json(array('message' => 'Tournament not at correct status'));
               } else {
                   return 'No Matches to show';
               }

            }

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

    public static function get_match_by($id, $switch = 'challonge_match_id'){

        switch($switch) {

            case "challonge_match_id" :

                $match = DW_Helper::get_post_by_meta('challonge_match_id', $id);

                break;

        }

        return $match;

    }

    public static function get_match_tournament_id($match_id){

        global $wpdb;

        $tournament_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p.p2p_from AS tournament_id FROM wp_p2p AS p2p WHERE p2p_to = %s AND p2p_type = 'tournament_matches'", $match_id));

        return $tournament_id;

    }

    public static function match_up($attr){

        extract(shortcode_atts(array(
            'date' => '',
            'size' => '',
            'match_id' => ''
        ), $attr));

        $players = p2p_type('match_players')->get_connected($match_id);

        $match_cards = '';

        $match_format = matchCPT::match_format($match_id);

        $player_card = '
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
                    </div>';

        $vote_button = '';

        if(is_user_logged_in()){
            $vote_button = '<a href="javascript:void(0);" class="large-vote-button team-%s" data-team-id="%s">Vote For %s</a>';
        }


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

            $card = sprintf(
                $player_card,
                $player_profile_image,
                get_permalink($player->ID),
                $player->post_title,
                $title,
                $winner
            );

            $teams[p2p_get_meta($player->p2p_id, 'team', true)] .= $card;

            if($clan = get_post_meta($player->ID, 'clan', true)){

                $clans[$clan] .= $card;

            }

            if($match_format == "format-vs"){
                $match_card[] = '<div class="col-lg-5">'.$card.'</div>';
            } else {
                $match_card[] = $card;
            }

        }

        switch($match_format){

            case "format-vs" :

                $vs = '<div class="vs col-lg-2"></div>';

                $match_cards = implode($vs, $match_card);

                $html = sprintf(
                    '<h3 class="text-center">VS</h3><section class="player-matchup row">%s</section>',
                    $match_cards
                );

                break;

            case "format-ffs" :

                $vs = '<div class="vs col-lg-2"></div>';

                foreach(array_chunk($match_card, 2) as $pair) {

                    $match_cards .= '<div class="row">';

                    foreach ($pair as $item) {
                        if ($item === end($pair) && count($pair) > 1){
                            $match_cards .= $vs . '<div class="col-lg-5">'.$item.'</div>' ;
                        } else {
                            $match_cards .= '<div class="col-lg-5">'.$item.'</div>' ;
                        }
                    }

                    $match_cards .= '</div>';
                }

                $html = sprintf(
                    '<h3 class="text-center">%s Player FFA </h3><section class="player-matchup">%s</section>',
                    count($players->posts),
                    $match_cards
                );

                break;

            case "format-vs-team" :
            case "format-vs-team-clan" :

                $vs = '<div class="vs col-lg-2"></div>';

                $team_count = 1;

                foreach(array_chunk($teams, 2) as $pair) {

                    $match_cards .= '<div class="row">';

                    foreach ($pair as $key => $team) {

                        $team_label = $team_count;

                        if ($team === end($pair) && count($pair) > 1){

                            //todo remove nasty!!
                            if($match_format == "format-vs-team-clan"){
                                if(false !== $clan_label = array_search($team, $clans)){
                                    $team_label = $clan_label;
                                }

                            }

                            $vote_button_string = sprintf($vote_button, $key, $key, $team_label);

                            $match_cards .= $vs.  '<div class="col-lg-5"><h3 class="text-center">Team '.$team_label.'</h3>'.$team.$vote_button_string.'</div>' ;
                        } else {

                            //todo remove nasty!!
                            if($match_format == "format-vs-team-clan"){
                                if(false !== $clan_label = array_search($team, $clans)){
                                    $team_label = $clan_label;
                                }

                            }


                            $vote_button_string = sprintf($vote_button, $key, $key, $team_label);

                            $match_cards .=  '<div class="col-lg-5"><h3 class="text-center">Team '.$team_label.'</h3>'.$team.$vote_button_string.'</div>' ;
                        }

                        $team_count ++;
                    }

                    $match_cards .= '</div>';
                }

                $html = sprintf(
                    '<h3 class="text-center">%s Player - Team VS</h3><section class="player-matchup %s">%s</section>',
                    count($players->posts),
                    $match_format,
                    $match_cards
                );
                break;

        }

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

            $remove_fields = array('author', 'parent', 'format', 'slug', 'guid', 'excerpt', 'menu_order', 'ping_status', 'sticky', 'content', 'category', 'post_excerpt', 'tags_input');

            //dont need author
            foreach($remove_fields as $field){
                unset($_post[$field]);
            }

            $comments   = wp_count_comments( $post['ID']);
            $tournament = p2p_type('tournament_matches')->set_direction('to')->get_connected($post['ID']);
            $players    = p2p_type('match_players')->get_connected($post['ID']);

            if(isset($tournament->posts[0]->ID)){

                $_post['meta']['tournament']['wp_id'] = $tournament->posts[0]->ID;
                $_post['meta']['tournament']['name'] = $tournament->posts[0]->post_title;
                $_post['meta']['tournament']['slug'] = $tournament->posts[0]->post_name;
                $_post['meta']['tournament']['url']  = get_permalink($tournament->posts[0]->ID);
                $_post['meta']['tournament']['tournament_date']  = get_post_meta($tournament->posts[0]->ID,'run_date',true);

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

            $_post['meta']['players']        = $match_players;
            $_post['meta']['comment_count']  = $comments->approved;
            $_post['meta']['video']          = get_match_videos($post['ID']);
            $_post['meta']['pa_stats_id']    = get_post_meta($post['ID'], 'pa_stats_match_id', true);
            $_post['meta']['pa_stats_start'] = get_post_meta($post['ID'], 'pa_stats_start', true);
            $_post['meta']['pa_stats_stop']  = get_post_meta($post['ID'], 'pa_stats_stop', true);
            $_post['meta']['twitch']         = get_post_meta($post['ID'], 'twitch', true);
            $_post['meta']['match_round']    = get_post_meta($post['ID'], 'match_round', true);
            $_post['meta']['team_filter']    = get_post_meta($post['ID'], 'team_filter', true);

        }



        return $_post;

    }

    public function pre_title_tournament_name($title, $id){

        if(is_admin() && get_post_type($id) == matchCPT::$post_type && in_the_loop() == false){

            $tournament = p2p_type('tournament_matches')->set_direction('to')->get_connected($id);

            if(isset($tournament->posts[0]->ID)){

                $title = $title .' - ' . $tournament->posts[0]->post_title;

            }


        }

        return $title;
    }

    public function edit_posts_fields($statment_fields, $query){
        global $wpdb;

        if($query->query_vars['orderby'] == 'tournament_date'){



            switch($query->query_vars['post_type'][0]){

                case "match" :

                    $statment_query[]  = $wpdb->prepare("(SELECT meta_value FROM $wpdb->p2p LEFT JOIN wp_postmeta ON post_id = p2p_from WHERE p2p_to = wp_posts.ID AND meta_key = 'run_date') AS tournament_date", '', '');

                    break;

                //todo move this out
                case "tournament" :

                    $statment_query[]  = $wpdb->prepare("(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = wp_posts.ID AND meta_key = 'run_date') AS tournament_date", '', '');
                    $statment_query[]  = $wpdb->prepare("(SELECT meta_value FROM $wpdb->postmeta WHERE post_id = wp_posts.ID AND meta_key = 'run_time') AS tournament_time", '', '');

                    break;

            }


            $statment_fields .= ', '.implode(', ', $statment_query);
        }

        return $statment_fields;
    }

    public function order_matches_by_tournament_date($orderby_statement, $query) {

        if($query->query_vars['orderby'] == 'tournament_date'){

            if($query->query_vars['post_type'][0] == tournamentCPT::$post_type){

                $orderby_statement = "tournament_date DESC, tournament_time DESC, ".$orderby_statement;
            } else {


                $orderby_statement = "tournament_date DESC, ".$orderby_statement;
            }

        }




        return $orderby_statement;
    }

    public function realtime_update_match_listing($match_id){

        if(get_post_type($match_id) != self::$post_type)
            return false;

        $tournament_id = self::get_match_tournament_id($match_id);

        //fetch match object
        $_match = get_post($match_id, ARRAY_A);

        foreach($_match as $key => $match){

            $new_match[str_replace('post_', '', $key)] = $match;

        }

        //add detail
        $match = matchCPT::extend_json_api($new_match, $_match, 'realtime_match_listing');

        //setsub
        $match['subscription'] = 't'.$match['meta']['tournament']['wp_id'];

        //send to realtime
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://localhost:5555");

        $socket->send(json_encode($match));


    }

    public static function match_format($match_id = 0){

        $match = get_post($match_id);
        $teams = [];
        $clans = [];
        $players    = p2p_type('match_players')->get_connected($match->ID);

        foreach ($players->posts as $player) {

            $teams[p2p_get_meta($player->p2p_id, 'team', true)] ++;

            if($clan = get_post_meta($player->ID, 'clan', true)){
                $clans[$clan] = true;
            }
        }


        if(count($players->posts) == 2){
            return 'format-vs';
        } else if(count($teams) == count($players->posts)){
            return 'format-ffs';
        } else if(count($teams) < count($players->posts) && count($teams) == count($clans)) {
            return 'format-vs-team-clan';
        } else if(count($teams) < count($players->posts)){
            return 'format-vs-team';
        }

    }

    public static function roster_management($original_template){

        global $wp_query, $post;

        if($wp_query->get('post_type') == 'match' && !is_admin()){

            $tournament_id = self::get_match_tournament_id($post->ID);
            $currentClan   = get_current_user_clan();
            $canEdit       = can_edit_match_roster($tournament_id, $currentClan);
            $inclan        = in_array($currentClan, explode(',', get_post_meta($post->ID, 'team_filter', true)));

            if (isset( $wp_query->query_vars['roster'] ) && ($canEdit == true && $inclan == true )) {
                return get_template_directory().'/single-match-roster-management.php';
            } else {
                return $original_template;
            }

        }

        return $original_template;
    }

    public static function update_team_roster(){

        $total_teams   = range(0, 10);
        $tournament_id = self::get_match_tournament_id($_POST['match_id']);

        if(!can_edit_match_roster($tournament_id, get_current_user_clan()) && !in_array(get_current_user_clan(), explode(',',get_post_meta($_POST['match_id'], 'team_filter', true))) )
            die();

        //if winners have been declared then no changes to roster are allowed.
        if( count(self::get_match_winners($_POST['match_id'])) !== 0 ){

            echo  json_encode(['success' => false, 'message' => 'This match has winners declared, roster cannot be changed']);

            die();
        }

        if( get_post_meta($_POST['match_id'], 'pa_stats_start', true) != ''){

            echo json_encode(['success' => false, 'message' => 'This match has started, roster cannot be changed']);

            die();
        }

        if( get_post_meta($_POST['match_id'], 'pa_stats_match_id', true) != '' ){

            echo json_encode(['success' => false, 'message' => 'This match has a pa match ID, roster cannot be changed']);

            die();
        }

        $teams     = self::get_clan_team_from_match($_POST['match_id']);
        $user_clan = get_current_user_clan();


        //todo change to diff for better action notifications
        $players    = p2p_type('match_players')->get_connected( $_POST['match_id'] );

        foreach ($players->posts as $player) {

            if(get_post_meta($player->ID, 'clan', true) == $user_clan)
                p2p_type( 'match_players' )->disconnect( $_POST['match_id'], $player->ID );

        }

        foreach($_POST['players'] as $player){

            if (array_key_exists($_POST['clan'], $teams)) {

                $team = $teams[$_POST['clan']];

            } else {

                $team = array_shift(array_diff($total_teams, $teams));

            }

            $p2p_result = p2p_type('match_players')->connect($_POST['match_id'], $player, array(
                'date'                     => current_time('mysql'),
                'team'                     => $team
            ));

            do_action('clan_match_roster_change_add', $_POST['match_id'], $player);

        }

        echo json_encode(['success' => true, 'message' => 'This match roster has been updated']);

        die();

    }


    public static function match_api_filter($wp_query){

        if(isset($wp_query->query_vars['match_players']) && in_array('player', $wp_query->query_vars['post_type'])){

            $match_id = $wp_query->query_vars['match_players'];

            $wp_query->set('connected_type', 'match_players');
            $wp_query->set('connected_items', $match_id);
            $wp_query->set('nopaging', true);
            $wp_query->set('suppress_filters', false);

            if(isset($wp_query->query_vars['clan'])){
                $clan = $wp_query->query_vars['clan'];
                $wp_query->set('meta_query', [[ 'key' => 'clan', 'value' => $clan]]);
            }
        }

        if(isset($wp_query->query_vars['match_statuss']) && in_array(self::$post_type, $wp_query->query_vars['post_type'])){

            $match_status = $wp_query->query_vars['match_statuss'];

            switch($match_status) {
                case "played" :

                    $wp_query->set('meta_query', [
                        'relation' => 'OR',
                        [ 'key' => 'schedule_date', 'value' => date('Ymd') , 'type' => 'date', 'compare' => '<'],
                        [ 'key' => 'schedule_date', 'value' => ''],
                        [ 'key' => 'schedule_date', 'value' => '', 'compare' => 'NOT EXISTS']
                    ]);

                    break;
            }

        }


        return $wp_query;

    }


    public static function get_match_winners($match_id){

        global $wpdb;

        $query = $wpdb->prepare("SELECT p2p.p2p_to as player_id FROM $wpdb->p2p AS p2p WHERE p2p_from = %s AND ( SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'winner' AND p2p_id = p2p.p2p_id ) = 1  AND p2p_type = 'match_players';", $match_id);

        $winners = $wpdb->get_var($query);

        return $winners;

    }

    public static function p2p_display_clan($connection, $direction){

        global $wpdb;

        $query = $wpdb->prepare("SELECT (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'clan' AND post_id = p2p.p2p_to) As clan FROM $wpdb->p2p AS p2p WHERE p2p_id = %s AND p2p_type = 'match_players'", $direction->name[1]);

        $clan = $wpdb->get_var($query);

        return $clan;

    }

    public static function get_match_players($match_id){

        global $wpdb;

        $query = $wpdb->prepare(
            "
                SELECT
                    p2p_from as match_id,
                    p2p_to as player_id,
                    (SELECT meta_value FROM wp_p2pmeta WHERE p2p_id = p2p.p2p_id AND meta_key = 'team') AS team,
                    (SELECT meta_value FROM wp_postmeta WHERE post_id = p2p.p2p_to AND meta_key = 'clan') as clan
                      FROM wp_p2p as p2p WHERE p2p_type = 'match_players' AND p2p_from = %s
                ",
            $match_id
        );

        $match_teams = $wpdb->get_results( $query );

        return $match_teams;

    }

    public static function get_clan_team_from_match($match_id){

        global $wpdb;

        $clan_teams = [];

        $match_teams = self::get_match_players($match_id);

        foreach($match_teams as $team){
            $clan_teams[$team->clan] = $team->team;
        }

        return $clan_teams;

    }

    public static function get_match_players_by($by = 'team', $match_id, $args = []) {

        switch ($by) {

            case "team" :

                $team = array_filter(self::get_match_players($match_id), function ($player) {
                        if ($player->team == $args['team']) {
                            return $player;
                        };
                    });

                break;

        }

        return $team;

    }

    public static function get_player_match_team($match_id, $player_id) {

        $team = array_filter(self::get_match_players($match_id), function ($player) {
                if ($player->player_id == $player_id) {
                    return $player;
                };
            });

        return $team[0]->team;

    }

}
