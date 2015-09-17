<?php

class notificationCPT {

    public static $post_type = 'notification';

    public static $notification_actions = array(
        'tournament_signup_active' => 'Tournament Signup Active',
        'tournament_signup_reserve' => 'Tournament Signup Reserve',
        'player_missing_pa_stats_id' => 'Player Missing PA Stats ID',
        'tournament_2_day_notice' => 'Tournament 2 day notice',
        'tournament_wrap_up' => 'Tournament Wrap Up');

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_notification') );
        add_action( 'init', array( $this, 'register_notification_actions') );
        //add_action( 'template_include', array( $this, 'get_notification_results') );

        add_action( 'after_setup_theme', array( $this, 'ctp_permission') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_action( 'tournament_signup_active', array( $this, 'email_notification' ), 10, 3);
        add_action( 'tournament_signup_reserve', array( $this, 'email_notification' ), 10, 3);

        add_action( 'player_missing_pa_stats_id', array( $this, 'email_notification' ), 10, 3);

        add_action( 'tournament_2_day_notice', array( $this, 'email_notification' ), 10, 3);
        add_action( 'tournament_wrap_up', array( $this, 'email_notification' ), 10, 3);

        add_filter( 'acf/load_field/name=notification_actions', array( $this, 'filter_notification_listing') );

        //mass notifications
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action('wp_ajax_tournament_2_day_notice', array($this,'tournament_2_day_notice'));
        add_action('wp_ajax_tournament_wrap_up', array($this,'tournament_wrap_up'));


        add_action( 'admin_enqueue_scripts',  [ $this, 'register_scripts'], 10 , 0 );

    }

    function register_cpt_notification(){

        $notificationLabel = array(
            'name'               => __('notifications'),
            'menu_name'          => __('notification'),
            'all_items'          => __('All notifications'),
            'singular_name'      => __('notification'),
            'add_new_item'       => __('Add New notification'),
            'edit_item'          => __('Edit notification'),
            'new_item'           => __('New notification'),
            'view_item'          => __('View notification'),
            'search_items'       => __('Search notifications'),
            'not_found'          => __('No notifications found'),
            'not_found_in_trash' => __('No notifications found in trash')
        );

        $notificationArgs = array(
            'labels'              => $notificationLabel,
            'description'         => 'Description',
            'public'              => false,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-email-alt',
            'capability_type'     => array('notification','notifications'),
            'supports'            => array('title', 'editor')
        );

        register_post_type( self::$post_type, $notificationArgs );

    }

    function ctp_permission(){

        $roles = array(
            get_role('administrator')
        );


        $caps  = array(
            'read',
            'read_'.self::$post_type.'',
            'read_private_'.self::$post_type.'s',
            'edit_'.self::$post_type,
            'edit_'.self::$post_type.'s',
            'edit_private_'.self::$post_type.'s',
            'edit_published_'.self::$post_type.'s',
            'edit_others_'.self::$post_type.'s',
            'publish_'.self::$post_type.'s',
            'delete_'.self::$post_type.'s',
            'delete_private_'.self::$post_type.'s',
            'delete_published_'.self::$post_type.'s',
            'delete_others_'.self::$post_type.'s',
        );

        foreach ($roles as $role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    public function register_p2p_connections(){

        p2p_register_connection_type( array(
            'name' => 'notification_players',
            'from' => self::$post_type,
            'to' => 'user' ,
            'duplicate_connections' => true,
            'admin_box' => array(
                'show' => false
            ),
            'fields' => array(
                'tournament' => array(
                    'title' => 'Tournament',
                    'type' => 'text',
                ),
                'date' => array(
                    'title' => 'Date Sent',
                    'type' => 'text',
                ),
            )
        ) );

    }

    public function register_notification_actions(){

        $this->exodus_register_notifications(self::$notification_actions);

    }

    public function email_notification( $tournament_id, $player_id ) {

        global $wp_exodus_functionality;

        $action = current_filter();

        remove_filter( 'the_content', [ $wp_exodus_functionality, 'exodus_name'] );

        switch ($action) {

            case 'tournament_2_day_notice':
            case 'tournament_wrap_up':

                //todo loads of code replication needs to be removed.

                $all_players = get_tournament_players($tournament_id, array(tournamentCPT::$tournament_player_status[0], tournamentCPT::$tournament_player_status[1]));

                $subject = $this->exodus_get_notification(array('location' => $action, 'field' => 'post_title', 'filter' => 'the_title'));

                //only continue if the message has a subject, because if not then no notification has been set
                if(!empty($subject)){

                    $notification = DW_Helper::get_post_by_meta('notification_actions', $action);
                    $message         = $this->exodus_get_notification(array('location' => $action));
                    $tournament_name = get_the_title($tournament_id);
                    $tournament_url  = sprintf('<a href="%s">%s</a>', get_permalink($tournament_id), get_the_title($tournament_id));

                    foreach($all_players as $player){

                        $find = array();
                        $replace = array();
                        $body_message = '';
                        $subject = '';

                        $user_id = get_post_meta($player->ID, 'user_id', true);
                        $user    = get_userdata($user_id);

                        if(!$user_id)
                            continue;

                        if (self::has_notification_been_sent($notification->ID, $tournament_id, $user_id))
                            continue;


                        $find = array(
                            '<TOURNAMENT NAME>',
                            '<TOURNAMENT URL>',
                            '<PLAYER IGN>'
                        );

                        $replace = array(
                            $tournament_name,
                            $tournament_url,
                            $player->post_title
                        );

                        $html_message = apply_filters( 'message_html', html_entity_decode( $message ) );

                        $body_message = str_replace($find, $replace, $html_message );
                        $subject = str_replace($find, $replace, $subject );

                        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: eXodus eSports <info@exodusesports.com>');

                        $mail = wp_mail( $user->user_email, html_entity_decode($subject), $body_message, $headers );

                        if($mail){
                            $p2p_result = p2p_type('notification_players')->connect($notification->ID, $user_id, array( 'date' =>  date("Y-m-d H:i:s"), 'tournament' => get_the_title($tournament_id), 'tournament_id' => $tournament_id ));
                        }

                    }

                }

                break;

            default:

                foreach (self::$notification_actions as $key => $value) {

                    if ($action === $key) {


                        $subject = $this->exodus_get_notification(array('location' => $key, 'field' => 'post_title', 'filter' => 'the_title'));

                        //only continue if the message has a subject, because if not then no notification has been set
                        if(!empty($subject)){

                            $notification = DW_Helper::get_post_by_meta('notification_actions', $key);

                            $message = $this->exodus_get_notification(array('location' => $key));

                            $user_id = get_post_meta($player_id, 'user_id', true);
                            $user    = get_userdata($user_id);
                            $user_email = $user->user_email;

                            if(!$user_id)
                                continue;

                            if (self::has_notification_been_sent($notification->ID, $tournament_id, $user_id))
                                continue;

                            $find = array(
                                '<TOURNAMENT NAME>',
                                '<TOURNAMENT URL>',
                                '<PLAYER IGN>',
                                '<TOURNAMENT RULES>'
                            );

                            $replace = array(
                                get_the_title($tournament_id),
                                sprintf('<a href="%s">%s</a>', get_permalink($tournament_id), get_the_title($player_id)),
                                get_the_title($player_id),
                                sprintf('<a href="%srules">%s</a>', get_permalink($tournament_id), 'rules'),
                            );

                            $html_message = apply_filters( 'message_html', html_entity_decode( $message ) );

                            $message = str_replace($find, $replace, $html_message );
                            $subject = str_replace($find, $replace, $subject );

                            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: eXodus eSports <info@exodusesports.com>');

                            $mail = wp_mail( $user_email, html_entity_decode($subject), $message, $headers );

                            if($mail){
                                $p2p_result = p2p_type('tournament_players')->connect($notification->ID, $user_id, array( 'date' =>  date("Y-m-d H:i:s"), 'tournament' => get_the_title($tournament_id) ));
                            }

                            return $mail;

                        }

                        break 2;

                    }

                }
        }


        add_filter( 'the_content', [ $wp_exodus_functionality, 'exodus_name'] );


    }

    public function filter_notification_listing($field){

        $field['choices'] = self::$notification_actions;

        return $field;

    }

    function exodus_register_notifications( $locations = array() ) {
        global $register_text_areas;

        $register_text_areas = array_merge( (array) $register_text_areas, $locations );
    }

    function exodus_get_notification($args = array()){

        global $wpdb;

        $return_field = 'post_content';
        $filter       = 'the_content';

        if(isset($args['field'])){
            $return_field = $args['field'];
        }

        if(isset($args['filter'])){
            $filter = $args['filter'];
        }


            $testarea_id = $wpdb->get_var($wpdb->prepare(
                "
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_value = %s AND meta_key = 'notification_actions' LIMIT 1
        ",
                $args['location']
            ));



        if(empty($testarea_id))
            return false;

        $testarea = get_post($testarea_id);

        return $testarea->{$return_field};

    }

    function exodus_get_notification_with_title($args = array('title_string' => '<h2>%s</h2>')){

        global $wpdb;

        $testarea_id = $wpdb->get_var($wpdb->prepare(
            "
		SELECT post_id
		FROM $wpdb->postmeta
		WHERE meta_value = %s AND meta_key = 'text_area_location' LIMIT 1
	",
            $args['location']
        ));

        if(empty($testarea_id))
            return false;

        $testarea = get_post($testarea_id);

        echo sprintf($args['title_string'], get_the_title($testarea->ID));

        echo  apply_filters('the_content', $testarea->post_content);

    }


    //todo general notification function merge
    public function tournament_2_day_notice(){

        check_ajax_referer( 'send-players-2-day-notification', 'security' );

        do_action('tournament_2_day_notice', $_POST['tournament_id'], null );

        die();

    }

    public function tournament_wrap_up(){

        check_ajax_referer( 'send-players-tournament-wrap-up', 'security' );

        do_action('tournament_wrap_up', $_POST['tournament_id'], null );

        //todo link notifications to tournament for tournament wide emails.
        update_post_meta($_POST['tournament_id'], 'wrap_up_email_sent', date('Y-m-d H:i:s'));

        die();

    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box( $post_type ) {
        $post_types = array('tournament');     //limit meta box to certain post types
        if ( in_array( $post_type, $post_types )) {
            add_meta_box(
                'tournament_players_mass_notification',
                __('Tournament mass notification', 'PLTM'),
                array($this, 'render_meta_box_tournament_notification'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_tournament_notification( $post ) {

        wp_enqueue_script('WPTM-Notification');

        // Display the form, using the current value.
        echo '<label for="myplugin_new_field">';
        _e( 'Send notifications', 'PLTM' );
        echo '</label> ';

        echo '<br /><br /><a href="javascript:void(0);" class="button" data-tournament-id="'.$post->ID.'" data-security="'.wp_create_nonce( "send-players-2-day-notification" ).'" id="send-players-2-day-notification">Send 2 day notification</a>';

       if(get_post_meta($post->ID, 'wrap_up_email_sent', date('Y-m-d H:i:s'))){
           echo '<br /><div>wrap-up email sent! ' . get_post_meta($post->ID, 'wrap_up_email_sent', true).'</div>';
       } else {
           echo '<br /><br /><a href="javascript:void(0);" class="button" data-tournament-id="'.$post->ID.'" data-security="'.wp_create_nonce( "send-players-tournament-wrap-up" ).'" id="send-players-tournament-wrap-up">Tournament Wrapup</a>';
       }
    }

    public function has_notification_been_sent($notification_id, $tournament_id, $user_id ){

        global $wpdb;

        $query = $wpdb->prepare(
            "
                SELECT p2p_id,
                (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'tournament' AND p2p_id = wp_p2p.p2p_id) AS tournament_name,
                (SELECT meta_value FROM $wpdb->p2pmeta WHERE meta_key = 'tournament_id' AND p2p_id = wp_p2p.p2p_id) AS tournament_id
                  FROM $wpdb->p2p WHERE p2p_from = %d and p2p_to = %d
                ",
            $notification_id,
            $user_id
        );

        $notifications = $wpdb->get_results( $query );

        foreach($notifications as $notification){

            if($notification->tournament_name == get_the_title($tournament_id) || $notification->tournament_id == $tournament_id){

                return true;
            }

        }


        return false;

    }

    public function register_scripts(){
        wp_register_script(
            'WPTM-Notification', WPTM_PLUGIN_URI . 'admin/assets/js/default.admin.js' , array( 'jquery' ), WP_Tournament_Manager::VERSION
        );

    }
}
