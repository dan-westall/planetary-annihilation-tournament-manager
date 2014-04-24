<?php


if (count(get_tournament_players($post->ID)) >= get_field('slots')) {

    _e('Sorry tournament is full');

} else {
    $form_short_code = sprintf('[gravityform id="%s" name="Tournament Signup" title="false" description="false"]', get_field('signup_form'));

    echo do_shortcode($form_short_code);
} ?>