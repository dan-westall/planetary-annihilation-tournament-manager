<?php


class WPTM_User_Meta_Caps {

	/**
	 * WPTM_User_Meta_Caps constructor.
	 */
	public function __construct() {

		add_filter( 'map_meta_cap', array( $this, 'tournament_meta_map' ), 10, 4 );

		add_filter( 'map_meta_cap', array( $this, 'match_meta_map' ), 10, 5 );

	}

	function tournament_meta_map( $caps, $cap, $user_id, $args ) {

		$post = get_post( $args[0] );

		if ( tournamentCPT::$post_type !== $post->post_type  ) {

			return $caps;

		}

		$user = get_user_by( 'id',  $user_id );

		if ( in_array( 'administrator', (array) $user->roles ) ) {

			return $caps;

		}

		$tournament_staff_p2p_id = p2p_type( 'tournament_staff' )->get_p2p_id( $post->ID, $user_id );

		$tournament = WPTM()->tournament->set_tournament_id( $post->ID );

		//if not a tournament stuff
		if( ! $tournament_staff_p2p_id ) {

			return $caps;

		}

		//if not assistant or Director
		if( ! in_array( p2p_get_meta( $tournament_staff_p2p_id, 'role', true ), [ 'Assistant', 'Director' ] ) ) {

			return $caps;

		}

		//staff should not be able to modify the tournament if cancelled or finished, only admins
		if ( in_array( $tournament->get_tournament_status(), array( 3, 4 ) ) ) {

			return $caps;

		}

		if( ! in_array( $cap, [ 'edit_tournament', 'read_tournament'] ) ) {

			return $caps;

		}

		$post_type = get_post_type_object( $post->post_type );

		$caps = array();

		if ( 'edit_tournament' === $cap ) {

			$caps[] = $post_type->cap->edit_posts;

		}

		if( 'read_tournament' === $cap ) {

			if ( 'private' != $post->post_status ) {

				$caps[] = 'read';

			} else {

				$caps[] = $post_type->cap->read_private_posts;

			}

		}

		return $caps;

	}

	function match_meta_map( $caps, $cap, $user_id, $args ) {

		return $caps;

	}

}

