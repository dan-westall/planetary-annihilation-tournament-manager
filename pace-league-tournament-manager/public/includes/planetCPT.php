<?php

class planetCPT {

    public static $post_type = 'planets';

    function __construct() {

        add_action( 'init', array( $this, 'pace_register_cpt_planet') );

    }

    function pace_register_cpt_planet(){

        $planetLabel = array(
            'name'               => __('Planets'),
            'menu_name'          => __('Planets'),
            'all_items'          => __('All Planets'),
            'singular_name'      => __('Planet'),
            'add_new_item'       => __('Add New Planet'),
            'edit_item'          => __('Edit Planet'),
            'new_item'           => __('New Planet'),
            'view_item'          => __('View Planet'),
            'search_items'       => __('Search Planets'),
            'not_found'          => __('No Planets found'),
            'not_found_in_trash' => __('No Planets found in trash')
        );

        $planetArgs = array(
            'labels'              => $planetLabel,
            'description'         => 'Description',
            'public'              => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-admin-site',
            'supports'            => array('title', 'editor', 'thumbnail')
        );

        register_post_type( self::$post_type, $planetArgs );

    }
}