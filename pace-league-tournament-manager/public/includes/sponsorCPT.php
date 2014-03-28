<?php

class sponsorCPT {


    function __construct() {

        add_action( 'init', array( $this, 'pace_register_cpt_sponsor') );

    }


    function pace_register_cpt_sponsor(){


        $planetLabel = array(
            'name'               => __('Sponsors'),
            'menu_name'          => __('Sponsor'),
            'all_items'          => __('All Sponsors'),
            'singular_name'      => __('Sponsor'),
            'add_new_item'       => __('Add New Sponsor'),
            'edit_item'          => __('Edit Sponsor'),
            'new_item'           => __('New Sponsor'),
            'view_item'          => __('View Sponsor'),
            'search_items'       => __('Search Sponsors'),
            'not_found'          => __('No Sponsors found'),
            'not_found_in_trash' => __('No Sponsors found in trash')
        );

        $planetArgs = array(
            'labels'              => $planetLabel,
            'description'         => 'Description',
            'public'              => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-awards',
            'supports'            => array('title', 'editor', 'thumbnail')
        );

        register_post_type( 'sponsors', $planetArgs );

    }
}