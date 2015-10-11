<?php


class WPTM_User_Meta_Caps {


	/**
	 * WPTM_User_Meta_Caps constructor.
	 */
	public function __construct() {

		add_filter( 'map_meta_cap', array( $this, 'my_map_meta_cap' ), 10, 4 );

	}

	function tournament_meta_map( $caps, $cap, $user_id, $args ) {

		$post = get_post( $args[0] );

		if ( tournamentCPT::$post_type !== $post->post_type  ) {

			return $caps;

		}

		$tournament_staff = p2p_type( 'tournament_staff' )->get_p2p_id( $post->ID, $user_id );

		if ( ! $tournament_staff) {

			return $caps;

		}

		$post_type = get_post_type_object( $post->post_type );

		if( in_array( $cap, [ ' '] ) )

	}

}

