<?php

class tournamentCPT {

    public static $post_type = 'tournament';

    function __construct() {

        add_action( 'init', array( $this, 'pace_register_cpt_tournament') );

        add_action( 'widgets_init', array( $this, 'register_tournament_sidebar') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );
        add_action( 'p2p_created_connection', array( $this, 'action_p2p_add_player_from_tournament' ) );
        add_action( 'p2p_delete_connections', array( $this, 'action_p2p_remove_player_from_tournament' ) );
        add_filter( 'p2p_connectable_args', array( $this, 'filter_p2p_tournament_player_requirements' ) );

        add_action( 'gform_after_submission', array( $this, 'signup_tournament_player'), 10, 2);
        add_action( 'template_include', array( $this, 'load_endpoint_template')  );

        //todo sync players from challonge to wordpress, fair bit of work, need to refactor signup_tournament_player to make it happen
        //add_action( 'save_post', array( $this, 'action_challonge_sync_check') );


        add_filter( 'tournament_rounds', array( $this, 'filter_tournament_rounds' ) );
        add_filter( 'the_title', array( $this, 'filter_endpoint_titles') );
        add_filter( 'single_template', array( $this, 'single_tournament_template') );
        add_filter( 'post_updated_messages', array( $this, 'filter_post_type_feedback_messages') );


        add_filter( 'acf/load_field/name=challonge_tournament_link', array( $this, 'filter_challonge_tournament_listing') );

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

    function single_tournament_template($single) {
        global $wp_query, $post;

        /* Checks for single template by post type */
        if ($post->post_type == self::$post_type) {

           return PACE_PLUGIN_DIR.'/includes/templates/single-'.self::$post_type.'.php';

        }

    }

    public function load_endpoint_template($template_path){

        global $wp_query, $post;

        foreach(Pace_League_Tournament_Manager::$endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

                $template_path = PACE_PLUGIN_DIR . "/includes/templates/single-$post->post_type-$endpoint.php";

                if(file_exists($template_path)){
                    return $template_path;
                }

            }
        }

        return $template_path;
    }


    public function filter_tournament_rounds($rounds){

        global $post;

        if(isset($_GET['post'])){

            $post = get_post($_GET['post']);

            if(is_object($post)){

                if($post->post_type == "tournament"){

                    for($round = 1; $round <= get_post_meta($post->ID, 'rounds', true); $round ++){

                        $rounds[$round] = sprintf('Round %s', $round);

                    }

                }

            }

        }

        return $rounds;

    }

    public function filter_endpoint_titles($title){

        global $post, $wp_query;

        if(!is_object($post))
            return $title;

        foreach(Pace_League_Tournament_Manager::$endpoints as $endpoint){

            if ($post->post_type == 'tournament' && isset( $wp_query->query_vars[$endpoint] )) {

                $title .= sprintf(' - %s', ucwords($endpoint));

            }

        }

        return $title;

    }

    function register_tournament_sidebar(){

        register_sidebar( array(
            'name'          => __( 'Single Tournament Widgets', 'pace' ),
            'description'   => __( 'Used for single tournament widgets', 'pace' ),
            'before_widget' => '<section id="%1$s" class="widget container-box %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h1 class="widget-title">',
            'after_title'   => '</h1>',
        ) );

        register_sidebar( array(
            'name'          => __( 'Archive Tournament Widgets', 'pace' ),
            'id'            => 'sidebar-5',
            'description'   => __( 'Used for archive tournament widgets', 'pace' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h1 class="widget-title">',
            'after_title'   => '</h1>',
        ) );


    }

    public function signup_tournament_player($entry, $form) {

        global $wpdb;

        $signup_form_id          = get_field('standard_tournament_signup_form', 'option');
        $tournament_id           = url_to_postid($entry['source_url']);
        $challonge_tournament_id = $this->get_the_challonge_tournament_id($tournament_id);

        $tournament_closed = get_field('signup_closed', $tournament_id);

        //if tournament 0 bin
        if ($tournament_id === 0 || $tournament_closed !== false)
            return false;

        if ($signup_form_id && get_field('signup_form')) {
            $signup_form_id = get_field('signup_form');
        }

        if ($signup_form_id != $entry['form_id'])
            return false;

        //todo move out to general function file as this is a useful snippit
        foreach( $form['fields'] as $field ) {

            $values[$field['field_mapField']] = array(
                'id'    => $field['id'],
                'label' => $field['label'],
                'value' => $entry[ $field['id'] ],
            );
        }

        //todo email shouldnt be stored with the player profile CTP should be linked either by p2p or meta int
        $find_player = array(
            'post_type'      => playerCPT::$post_type,
            'meta_query'     => array(
                array(
                    'key'   => 'player_email',
                    'value' => $entry['3']
                )
            ),
            'posts_per_page' => 1
        );

        $players = get_posts($find_player);

        //existing player
        if (!empty($players)) {

            $player_id = $players[0]->ID;

            //add player to current challonge tournament
            $challonge_result = $this->challonge_add_player_to_tournament($challonge_tournament_id, $values['email']['value'], $values['ign']['value']);

            //error check
            if(true){

                //player found add player to tornament
                $p2p_result = $this->action_add_player_to_tournament($player_id, $tournament_id, $challonge_tournament_id, $challonge_result);

                if(!$p2p_result){
                    //email admins let them know something went wrong.
                }

            } else {
                //error here
            }

        } else {

            //new user accounts have been created to provide features going forward
            $userdata = array(
                'user_login' => $values['email']['value'],
                'user_email' =>$values['email']['value'],
                'user_pass' => wp_generate_password()
            );

            $user_id = wp_insert_user($userdata);

            //create new player post
            $new_player = array(
                'post_title'  => $values['ign']['value'],
                'post_status' => 'publish',
                'post_author' => $user_id,
                'post_type'   => playerCPT::$post_type
            );

            // Insert the post into the database
            $player_id = wp_insert_post($new_player);

            update_post_meta( $player_id, 'player_email', $values['email']['value']);
            update_post_meta( $player_id, 'user_id', $user_id);

            update_user_meta( $user_id, 'player_id', $player_id);

            //add player to current challonge tournament
            $challonge_result = $this->challonge_add_player_to_tournament($challonge_tournament_id, $values['email']['value'], $values['ign']['value']);

            //error check, if challonge was correct lets do p2p
            if(true){

                $p2p_result = $this->action_add_player_to_tournament($player_id, $tournament_id, $challonge_tournament_id, $challonge_result);

                if(!$p2p_result){
                    //email admins let them know something went wrong.
                }

            } else {
                //error here

                //change confirmation message

                //email admins let them know something went wrong.

            }

        }

    }

    public function filter_challonge_tournament_listing($field){

        $c = new ChallongeAPI(Pace_League_Tournament_Manager::fetch_challonge_API());

        $form_listing[] = 'Select Tournament';
        $form_listing[] = 'Custom Tournament ID';

        $args = array('subdomain' => 'api-test');

        $tournaments = $c->getTournaments($args);

        //converts the simplexmlobject to a standard object with arrays, much easier to work with.
        $tournaments = json_decode( json_encode( (array) $tournaments), false );

        if(is_array($tournaments->tournament)){

            foreach($tournaments->tournament as $t){

                $form_listing[$t->id] = $t->name;

            }

        } else {
            $form_listing[$tournaments->tournament->id] = $tournaments->tournament->name;
        }

        $field['choices'] = $form_listing;

        return $field;

    }

    public function filter_p2p_tournament_player_requirements($args){

        switch($_POST['p2p_type']){

            case "tournament_players" :

                //player profiles must have emails for challonge intergration
                $args['meta_query'] = array(
                    array(
                        'key' => 'player_email',
                        'compare' => 'EXISTS'
                    )
                );

                break;

        }

        return $args;

    }

    public function challonge_add_player_to_tournament($challonge_tournament_id, $email, $ign){

        $c = new ChallongeAPI(Pace_League_Tournament_Manager::fetch_challonge_API());

        $params = array(
            "participant[email]"              => $email,
            'participant[name]'               => $ign,
            'participant[challonge_username]' => $ign,
        );

        $participant = $c->createParticipant($challonge_tournament_id, $params);

        $result = json_decode( json_encode( (array) $participant), false );

        return $result;

    }

    public function challonge_remove_player_from_tournament($challonge_tournament_id, $challonge_participant_id){

        $c = new ChallongeAPI(Pace_League_Tournament_Manager::fetch_challonge_API());

        $c->verify_ssl = false;

        $participant = $c->deleteParticipant($challonge_tournament_id, $challonge_participant_id);

        $result = json_decode( json_encode( (array) $participant), false );

        return $result;
    }

    public function action_p2p_add_player_from_tournament($p2p_id){

        $connection = p2p_get_connection( $p2p_id );

        if ( 'tournament_players' == $connection->p2p_type ) {

            $challonge_tournament_id = $this->get_the_challonge_tournament_id($connection->p2p_from);

            $email = get_post_meta($connection->p2p_to, 'player_email', true);
            $ign   = get_the_title($connection->p2p_to);

            //add player to current challonge tournament
            $challonge_result = $this->challonge_add_player_to_tournament($challonge_tournament_id, $email, $ign);

            p2p_add_meta( $p2p_id, 'challonge_tournament_id', $challonge_tournament_id);
            p2p_add_meta( $p2p_id, 'challonge_participant_id', $challonge_result->id);
            p2p_add_meta( $p2p_id, 'date', current_time('mysql') );

            //save the return to db as this has useful info in it
            update_post_meta($connection->p2p_to, 'challonge_data', $challonge_result);

            //easy search
            update_post_meta($connection->p2p_to, 'challonge_participant_id', $challonge_result->id);

        }

    }

    public function action_p2p_remove_player_from_tournament($p2p_id){

        $connection = p2p_get_connection( $p2p_id );

        if ( 'tournament_players' == $connection->p2p_type ) {

            //todo tournament remove reason and history will be to be done.

            $challonge_tournament_id = $this->get_the_challonge_tournament_id($connection->p2p_from);
            $challonge_participant_id = p2p_get_meta( $connection->p2p_id, 'challonge_participant_id', true );

            $challonge_result = $this->challonge_remove_player_from_tournament($challonge_tournament_id, $challonge_participant_id);

            //save the return to db as this has useful info in it
            delete_post_meta($connection->p2p_to, 'challonge_data' );

            //easy search
            delete_post_meta($connection->p2p_to, 'challonge_participant_id');


        }
    }

    public function action_add_player_to_tournament($player_id, $tournament_id, $challonge_tournament_id, $challonge_result){

        //save the return to db as this has useful info in it
        update_post_meta($player_id, 'challonge_data', $challonge_result);

        //easy search
        update_post_meta($player_id, 'challonge_participant_id', $challonge_result->id);

        $connection_meta = array(
            'date'                     => current_time('mysql'),
            'challonge_tournament_id'  => $challonge_tournament_id,
            'challonge_participant_id' => $challonge_result->id
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
        $p2p_result = p2p_type('tournament_players')->connect($tournament_id, $player_id, $connection_meta);

        return $p2p_result;

    }

    public function get_the_challonge_tournament_id($post_id){

        if(get_post_meta($post_id, 'challonge_tournament_link',true) == "Custom Tournament ID"){
            $challonge_tournament_id = get_post_meta($post_id, 'custom_tournament_id',true);
        } else {
            $challonge_tournament_id = get_post_meta($post_id, 'challonge_tournament_link',true);
        }

        return $challonge_tournament_id;

    }

    function filter_post_type_feedback_messages( $messages ) {

        $post             = get_post();
        $post_type        = get_post_type( $post );
        $post_type_object = get_post_type_object( $post_type );

        $messages[self::$post_type] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => __( 'Tournament updated.', 'pace-tournament-cpt' ),
            2  => __( 'Custom field updated.', 'pace-tournament-cpt' ),
            3  => __( 'Custom field deleted.', 'pace-tournament-cpt' ),
            4  => __( 'Tournament updated.', 'pace-tournament-cpt' ),
            /* translators: %s: date and time of the revision */
            5  => isset( $_GET['revision'] ) ? sprintf( __( 'Tournament restored to revision from %s', 'pace-tournament-cpt' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6  => __( 'Tournament published.', 'pace-tournament-cpt' ),
            7  => __( 'Tournament saved.', 'pace-tournament-cpt' ),
            8  => __( 'Tournament submitted.', 'pace-tournament-cpt' ),
            9  => sprintf(
                __( 'Tournament scheduled for: <strong>%1$s</strong>.', 'pace-tournament-cpt' ),
                // translators: Publish box date format, see http://php.net/date
                date_i18n( __( 'M j, Y @ G:i', 'pace-tournament-cpt' ), strtotime( $post->post_date ) )
            ),
            10 => __( 'Tournament draft updated.', 'pace-tournament-cpt' ),
        );

        if ( $post_type_object->publicly_queryable ) {
            $permalink = get_permalink( $post->ID );

            $view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View tournament', 'pace-tournament-cpt' ) );
            $messages[ $post_type ][1] .= $view_link;
            $messages[ $post_type ][6] .= $view_link;
            $messages[ $post_type ][9] .= $view_link;

            $preview_permalink = add_query_arg( 'preview', 'true', $permalink );
            $preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview tournament', 'pace-tournament-cpt' ) );
            $messages[ $post_type ][8]  .= $preview_link;
            $messages[ $post_type ][10] .= $preview_link;
        }

        return $messages;

    }

    //not in use
    public function action_challonge_sync_check($post_id){

        if ( wp_is_post_revision( $post_id ) )
            return;

        $post_meta = get_post_custom($post_id);

        //check to make sure this tournament has been linked to a challonge one
        if( array_key_exists('custom_tournament_id', $post_meta) == true || array_key_exists('custom_tournament_id', $post_meta) == true  ){

            if(get_post_meta($post_id, 'challonge_tournament_link',true) == "Custom Tournament ID"){
                $challonge_tournament_id = get_post_meta($post_id, 'custom_tournament_id',true);
            } else {
                $challonge_tournament_id = get_post_meta($post_id, 'challonge_tournament_link',true);
            }

            $c = new ChallongeAPI(Pace_League_Tournament_Manager::fetch_challonge_API());

            $args = array(
                'connected_type' => 'tournament_players',
                'connected_items' => $post_id
            );

            $players = get_posts( $args );

            $wp_total_players = count($players);

            $challonge_tournament_players = json_decode( json_encode( (array) $c->getParticipants($challonge_tournament_id) ), false );

            //if wp players doesnt equal challonge players when sync!
            if($wp_total_players != count($challonge_tournament_players->participant)){



            }

        }

    }
}