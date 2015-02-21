<?php


class PATM_UnitTest_Factory extends WP_UnitTest_Factory {
    public $activity = null;
    function __construct() {
        parent::__construct();
        $this->tournament = new WP_UnitTest_Factory_For_Tournaments($this);
        $this->player = new WP_UnitTest_Factory_For_Players($this);
    }
}

class WP_UnitTest_Factory_For_Tournaments extends WP_UnitTest_Factory_For_Thing {

    function __construct( $factory = null ) {
        parent::__construct( $factory );
        $this->default_generation_definitions = array(
            'post_status' => 'publish',
            'post_title' => new WP_UnitTest_Generator_Sequence( 'Post title %s' ),
            'post_content' => new WP_UnitTest_Generator_Sequence( 'Post content %s' ),
            'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Post excerpt %s' ),
            'post_type' => tournamentCPT::$post_type
        );
    }

    function create_object( $args ) {
        return wp_insert_post( $args );
    }

    function update_object( $post_id, $fields ) {
        $fields['ID'] = $post_id;
        return wp_update_post( $fields );
    }

    function get_object_by_id( $post_id ) {
        return get_post( $post_id );
    }
}

class WP_UnitTest_Factory_For_Players extends WP_UnitTest_Factory_For_Thing {

    function __construct( $factory = null ) {
        parent::__construct( $factory );
        $this->default_generation_definitions = array(
            'post_status' => 'publish',
            'post_title' => new WP_UnitTest_Generator_Sequence( 'Post title %s' ),
            'post_content' => new WP_UnitTest_Generator_Sequence( 'Post content %s' ),
            'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Post excerpt %s' ),
            'post_type' => playerCPT::$post_type
        );
    }

    function create_object( $args ) {
        return wp_insert_post( $args );
    }

    function update_object( $post_id, $fields ) {
        $fields['ID'] = $post_id;
        return wp_update_post( $fields );
    }

    function get_object_by_id( $post_id ) {
        return get_post( $post_id );
    }
}