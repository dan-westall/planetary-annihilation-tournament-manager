<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Match_Comments {

    public static $comment_type = [ 'match-summary' => 'Match Summary' ];

    function __construct(){

        add_filter( 'preprocess_comment', [ $this, 'set_comment_type'], 10, 1 );

        add_action( 'comment_form', [ $this, 'comment_type_form_input'] );

    }

    public function set_comment_type( $comment_data ) {

        $comment_type = array_keys( WPTM_Match_Comments::$comment_type );

        if (  !isset( $_POST['comment_type'] ) ) {

            return $comment_data;

        }

        if ( get_post_type( $comment_data['comment_post_ID'] ) != matchCPT::$post_type ){

            return $comment_data;

        }

        if ( $_POST['comment_type'] === $comment_type[0] ){

            $comment_data['comment_type'] = $comment_type[0];

        }

        return $comment_data;

    }

    public static function comment_type_form_input( $post_id ){

        $comment_type = array_keys( WPTM_Match_Comments::$comment_type );

        if ( get_post_type( $post_id ) != matchCPT::$post_type ){

            return;

        }

        echo sprintf( '<input type="hidden" name="comment_type" value="'. esc_attr( $comment_type[0] ) .'" />', '' );

    }

}