<?php

if(get_tournament_matches($post->ID)) {

    $form_short_code = sprintf('[tournament-matches tournament_id=%s]', $post->ID);

    echo do_shortcode($form_short_code);

}

?>
