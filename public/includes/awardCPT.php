<?php

class award {

    public static $post_type = 'award';

    public static $award_types = [ 'video_highlight' => 'Video Highlight' ];

    /**
     *
     */
    public static function register() {

        $plugin = new self();

        add_action( 'init',  [ $plugin, 'register_cpt'] );
        add_action( 'p2p_init', [ $plugin, 'register_p2p_connections']);

        add_action( 'after_setup_theme', [ $plugin, 'role_permission'] );

    }

    /**
     *
     */
    function __construct() {


    }

    /**
     *
     */
    function register_cpt(){

        $awardLabel = [
            'name'               => __('Awards'),
            'menu_name'          => __('Awards'),
            'all_items'          => __('All Awards'),
            'singular_name'      => __('Award'),
            'add_new_item'       => __('Add New Award'),
            'edit_item'          => __('Edit Award'),
            'new_item'           => __('New Award'),
            'view_item'          => __('View Award'),
            'search_items'       => __('Search Awardes'),
            'not_found'          => __('No Awards found'),
            'not_found_in_trash' => __('No Awards found in trash')
        ];

        $awardArgs = [
            'labels'              => $awardLabel,
            'description'         => 'Description',
            'public'              => false,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_json'        => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-awards',
            'capability_type'     => ['award','awards'],
            'supports'            => ['title', 'thumbnail', 'editor']
        ];

        register_post_type( self::$post_type, $awardArgs );

    }

    function role_permission(){

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
            'delete_'.self::$post_type,
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

    private static function link_fields($post_type, $award_type){

        $fields['video']['highlight'] = [
            'time_slot' => [
                    'title'  => 'Tournament',
                    'type'   => 'text'
                ],

        ];


        return $fields[$post_type][$award_type];
    }

    /**
     *
     */
    public static function register_p2p_connections() {


        $object_id = (isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : $_GET['post']);
        $post_type = get_post_type($object_id);
//
//        p2p_register_connection_type([
//            'name'      => 'award',
//            'from'      => self::$post_type,
//            'to'        => [ matchCPT::$post_type, playerCPT::$post_type ],
//            'admin_box' => [
//                'show'    => 'to',
//                'context' => 'advanced'
//            ],
//            'title'     => [
//                'to' => __('Award - Vote', 'PLTM')
//            ],
//            'fields'    => self::link_fields($post_type, self::get_award_type($object_id))
//        ]);


    }

    public static function get_award_type($award_id){

        return get_post_type($award_id, 'award_type', true);

    }

}