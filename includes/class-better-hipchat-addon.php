<?php



class better_hipchat_intergration {

    /**
     *
     */
    function __construct() {

        add_filter( 'hipchat_get_events', [ $this, 'hipchat_tournaments_events' ], 10, 1 );
        add_filter( 'hipchat_event_wp_insert_comment_post_types', [ $this, 'comment_hipchat_post_types' ], 10, 1 );


    }

    public static function hipchat_tournaments_events( $events ){

        $events['tournament_signup_error'] = array(
                'action'      => 'tournament_signup_error',
                'description' => __( 'When theres a signup error', 'wp-tournament-manager' ),
                'default'     => true,
                'message'     => function(  $player_id, $tournament_id, $message, $signup_data ) {
                    return sprintf(
                        'Signup Error: User <strong>%1$s</strong> tried signing up to  <strong>%2$s</strong>
							<br>
							Error message given "%3$s"
							<br>
							<pre>%4$s</pre>
							',
                        esc_html( get_the_title( $player_id ) ),
                        esc_html( get_the_title( $tournament_id ) ),
                        $message,
                        $signup_data
                    );
                }
            );

        $events['tournament_signup'] = array(
                'action'      => 'tournament_signup',
                'description' => __( 'When theres a new tournament signup', 'wp-tournament-manager' ),
                'default'     => true,
                'message'     => function(  $player_id, $tournament_id, $message, $signup_data, $signup_status ) {
                    return sprintf(
                        'New Tournament Signup: User <strong>%1$s</strong> signed up to  <strong>%2$s</strong> as <strong>%3$s</strong>
							<br>
							<pre>%4$s</pre>
							',
                        esc_html( get_the_title( $player_id ) ),
                        esc_html( get_the_title( $tournament_id ) ),
                        $signup_status,
                        $signup_data
                    );
                }
            );


        return $events;

    }

    public static function hipchat_match_events(){




    }

    public static function comment_hipchat_post_types($post_types){

        return [ 'news', matchCPT::$post_type ];

    }

}