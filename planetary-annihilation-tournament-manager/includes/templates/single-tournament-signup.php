<?php $form_short_code = sprintf('[gravityform id="%s" name="Tournament Signup" title="false" description="false"]', get_field('signup_form'));

echo do_shortcode($form_short_code); ?>