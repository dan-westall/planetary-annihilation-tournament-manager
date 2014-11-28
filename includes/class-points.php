<?php

class points {

    public static $points = [
        'comment' => 1,
        'match'   => 1,
        'vote'    => 1,
        'share'   => 1
    ];

    private $user_id;

    /**
     * @return mixed
     */
    public function getUserId() {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id) {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     *
     */
    public static function register() {

        $plugin = new self();

        //add_action( 'init',  [ $plugin, 'register_cpt'] );
        add_action( 'p2p_init', [ $plugin, 'register_p2p_connections']);

        add_action( 'wp_ajax_get_votes', [ $plugin, 'ajax_log_share']);
        add_action( 'wp_ajax_calculate_user_points', [ $plugin, 'ajax_calculate_user_points']);

        add_action( 'add_points', [ $plugin, 'add_points']);

    }

    /**
     *
     */
    function __construct() {


    }

    /**
     *
     */
    public static function register_p2p_connections() {


        $object_id = (isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : $_GET['post']);
        $post_type = get_post_type($object_id);

        p2p_register_connection_type([
            'name'      => 'social_media_share',
            'from'      => 'user',
            'to'        => [ articleCPT::$post_type, matchCPT::$post_type, 'guides', tournamentCPT::$post_type, 'news'  ],
            'admin_box' => [
                'show'    => 'to',
                'context' => 'advanced'
            ],
            'title'     => [
                'to' => __('Award', 'PLTM')
            ]

        ]);


    }

    public static function update_user_points($user_id, $action){

        global $wpdb;


    }

    public static function ajax_log_share(){

        check_ajax_referer('security-' . date('dmy'), 'security');

        global $current_user; get_currentuserinfo();

        if(false !== ($connection_id = self::has_shared($current_user->ID, $_POST['object_id']))){

            if(!p2p_get_meta($connection_id, $_POST['share_service'], true)){

                p2p_add_meta($connection_id, $_POST['share_service'], true);

                do_action('add_points', 'share', $current_user->ID, $_POST['object_id']);
            }

        } else {

            $meta = [
                'date'    => current_time('mysql'),
                $_POST['share_service'] => true
            ];

            $result = p2p_type('player_vote')->connect($current_user->ID, $_POST['object_id'], $meta);

            do_action('add_points', 'share', $current_user->ID, $_POST['object_id']);
        }

    }

    public static function ajax_calculate_user_points(){

        global $wpdb;

        $query = $wpdb->prepare(
            "
                SELECT
                    ID,
                    (SELECT meta_value FROM $wpdb->usermeta WHERE user_id = $wpdb->users.ID AND meta_key = 'player_id') AS player_id,
                    user_nicename,
                    (SELECT count(comment_ID) FROM $wpdb->comments WHERE user_id = $wpdb->users.ID and comment_approved = 1) AS comments,
                    (SELECT count(p2p_from) FROM $wpdb->p2p WHERE p2p_to = player_id AND p2p_type = 'match_players'  ) AS matches,
                    (SELECT count(p2p_from) FROM $wpdb->p2p WHERE p2p_to = player_id AND p2p_type = 'tournament_players' ) AS tournaments,
                    (SELECT count(p2p_from) FROM $wpdb->p2p WHERE p2p_from = $wpdb->users.ID AND p2p_type = 'player_vote' ) AS votes,
                    (SELECT count(p2p_from) FROM $wpdb->p2p WHERE p2p_from = $wpdb->users.ID AND p2p_type = 'social_media_share' ) AS shares
                        FROM $wpdb->users
                ",
            ''
        );

        $totals = $wpdb->get_results( $query );
        

    }

    public static function has_shared($user_id, $object_id, $service = ''){

        $p2p_id = p2p_type( 'social_media_share' )->get_p2p_id( $user_id, $object_id );

        if ( $p2p_id )
            return $p2p_id;

        return false;

    }

    public static function add_points($action, $user_id, $object_id){

        $current_points = get_user_meta($user_id, 'points', true);

        $new_points = $current_points + (self::$points[$action]);

        update_user_meta($user_id, 'points', $new_points);

    }

    public function get_user_points(){

        return  get_user_meta($this->user_id, 'points', true);

    }

}