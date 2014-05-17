<?php

class notificationCPT {

    public static $post_type = 'notification';

    public static $notification_actions = array( 'tournament_signup_active' => 'Tournament Signup Not Reserve', 'tournament_signup_reserve' => 'Tournament Signup Reserve');

    function __construct() {

        add_action( 'init', array( $this, 'register_cpt_notification') );
        add_action( 'init', array( $this, 'register_notification_actions') );
        //add_action( 'template_include', array( $this, 'get_notification_results') );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

        add_action( 'player_added_to_tournament_active', array( $this, 'email_notification' ), 10, 3);
        add_action( 'player_added_to_tournament_reserve', array( $this, 'email_notification' ), 10, 3);

        add_filter( 'acf/load_field/name=notification_actions', array( $this, 'filter_notification_listing') );

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
            'supports'            => array('title', 'editor')
        );

        register_post_type( self::$post_type, $notificationArgs );

    }

    public function register_p2p_connections(){


    }

    public function register_notification_actions(){

        $this->exodus_register_notifications(self::$notification_actions);

    }

    public function email_notification($args) {

        $action = current_filter();

        switch ($action) {

            case 'plugins_loaded':

                break;

            default:

                foreach (self::$notification_actions as $key => $value) {

                    if ($action === $value) {


                        global $cart_status;

                        $subject = exodus_2014_get_notification(array('location' => $key, 'field' => 'post_title', 'filter' => 'the_title'));

                        //only continue if the message has a subject, because if not then no notification has been set
                        if(!empty($subject)){

                            $message = exodus_2014_get_notification(array('location' => $key));

                            $player_email = get_post_meta($args['player_id'], 'player_email', true);

                            $find = array(
                                '<TOURNAMENT NAME>',
                                '<TOURNAMENT URL>',
                                '<PLAYER IGN>'
                            );

                            $replace = array(
                                get_the_title($args['tournament_id']),
                                sprintf('<a href="%s">%s</a>', get_permalink($args['tournament_id']), get_the_title($args['player_id'])),
                                get_the_title($args['player_id'])
                            );

                            $html_message = apply_filters( 'message_html', html_entity_decode( $message ) );

                            $message = str_replace($find, $replace, $html_message );

                            add_filter( 'wp_mail_content_type', 'set_html_content_type' );

                            $mail = wp_mail( "dan.westall@googlemail.com, {$player_email}", $subject, ( $message ));

                            // Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
                            remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

                        }

                    }

                    break 2;
                }
        }


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

        if(taxonomy_exists($args['location']) && isset($args['taxonomy_term']) && !empty($args['taxonomy_term'])){

            $textarea = get_posts(array(
                'post_type' => 'textarea',
                'meta_query' => array(
                    array(
                        'key' => 'text_area_location',
                        'value' => $args['location']
                    ),
                    array(
                        'key' => 'taxonomy_term_location',
                        'value' => $args['taxonomy_term']
                    )
                )

            ));

            $testarea_id = $textarea[0]->ID;

        } else {

            $testarea_id = $wpdb->get_var($wpdb->prepare(
                "
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_value = %s AND meta_key = 'text_area_location' LIMIT 1
        ",
                $args['location']
            ));

        }

        if(empty($testarea_id))
            return false;

        $testarea = get_post($testarea_id);

        return apply_filters($filter, $testarea->{$return_field});

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


}
