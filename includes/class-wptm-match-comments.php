<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Match_Comments {

    public static $comment_type = [ 'match-summary' => 'Match Summary' ];

    function __construct(){

        add_filter( 'preprocess_comment', [ $this, 'set_comment_type'], 10, 1 );

        add_filter( 'comment_form_submit_field', [ $this, 'comment_type_form_input'], 10 ,2 );

        //admin comment management screen comment columns
        add_filter("manage_edit-comments_columns", array($this, 'comment_columns'));
        add_action('manage_comments_custom_column', array($this, 'comment_custom_columns'), 10, 2);

        //admin comment management screen comment filtering
        add_action( 'admin_comment_types_dropdown', array($this, 'admin_comment_types_dropdown') );

    }

    public function set_comment_type( $comment_data ) {

        $comment_type = array_keys( WPTM_Match_Comments::$comment_type );

        if (  !isset( $_POST['comment_type'] ) ) {

            return $comment_data;

        }

        if ( get_post_type( $comment_data['comment_post_ID'] ) != matchCPT::$post_type ){

            return $comment_data;

        }

        //no direct pass through for comment type see wp-comments-post -> wp_new_comment -> $commentdata -> wp_insert_comment
        if ( $_POST['comment_type'] === $comment_type[0] ){

            $comment_data['comment_type'] = $comment_type[0];

        }

        return (array) $comment_data;

    }

    public static function comment_type_form_input( $submit_field, $args ){

        global $post;

        $comment_type = array_keys( WPTM_Match_Comments::$comment_type );

        if ( get_post_type( $post->ID ) != matchCPT::$post_type ){

            return;

        }

        if ( array_key_exists( 'comment_type', $args ) ) {

            switch($args['comment_type']) {

                case $comment_type[0] :

                    echo sprintf( '<input type="hidden" name="comment_type" value="'. esc_attr( $comment_type[0] ) .'" />', '' );

                    break;

            }

        }

        return $submit_field;

    }

    public function comment_columns($columns) {

        global $post, $current_user;

        get_currentuserinfo();

        $columns['comment_type_heading'] = __( 'Comment Type' );

        return (array) $columns;

    }

    public function comment_custom_columns( $column, $comment_ID ){

        if ( $column == 'comment_type_heading') {

            if ( 'comment' === ( $comment_type = get_comment_type( $comment_ID ) ) ){

                echo 'Standard Comment';

            } else if ( array_key_exists( $comment_type, WPTM_Match_Comments::$comment_type ) ) {

                echo WPTM_Match_Comments::$comment_type[ get_comment_type( $comment_ID ) ];

            } else {

                echo $comment_type;

            }

        }

    }

    public function admin_comment_types_dropdown($types){

        return (array) array_merge($types, WPTM_Match_Comments::$comment_type);

    }

}