<?php if(get_field('existing_rules') || get_field('rules')): ?>

    <h2>Rules</h2>

    <?php the_field('rules'); ?>

<?php endif; ?>

<?php if(get_field('existing_rules')): ?>

    <?php echo apply_filters('the_content', get_field('existing_rules')->post_content);  ?>

<?php endif; ?>

<?php if(get_field('requirements') || get_field('existing_requirements')): ?>

    <h2>Requirements</h2>

    <?php the_field('requirements'); ?>

<?php endif; ?>

<?php if(get_field('existing_requirements')): ?>

    <?php echo apply_filters('the_content', get_field('existing_requirements')->post_content);  ?>

<?php endif; ?>