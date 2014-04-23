<?php

$players = p2p_type( 'tournament_players' )->get_connected( get_queried_object() );

if (count($players->posts) >= get_field('slots')) {

    _e('Sorry tournament is full');

} else {
    $form_short_code = sprintf('[gravityform id="%s" name="Tournament Signup" title="false" description="false"]', get_field('signup_form'));

    echo do_shortcode($form_short_code);
} ?>