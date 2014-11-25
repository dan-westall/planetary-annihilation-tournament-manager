<?php

class ruleCPT {

    public static $post_type = 'rule';


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

        $ruleLabel = [
            'name'               => __('Rules'),
            'menu_name'          => __('Rules'),
            'all_items'          => __('All Rules'),
            'singular_name'      => __('Rule'),
            'add_new_item'       => __('Add New Rule'),
            'edit_item'          => __('Edit Rule'),
            'new_item'           => __('New Rule'),
            'view_item'          => __('View Rule'),
            'search_items'       => __('Search Rulees'),
            'not_found'          => __('No Rules found'),
            'not_found_in_trash' => __('No Rules found in trash')
        ];

        $ruleArgs = [
            'labels'              => $ruleLabel,
            'description'         => 'Description',
            'public'              => false,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'show_ui'             => 'edit.php?post_type='.tournamentCPT::$post_type,
            'show_in_json'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-book-alt',
            'capability_type'     => ['rule','rules'],
            'supports'            => ['title', 'thumbnail', 'editor', 'revisions']
        ];

        register_post_type( self::$post_type, $ruleArgs );

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

    /**
     *
     */
    public static function register_p2p_connections() {

        $object_id = (isset($_REQUEST['post_ID']) ? $_REQUEST['post_ID'] : $_GET['post']);
        $post_type = get_post_type($object_id);

    }
}