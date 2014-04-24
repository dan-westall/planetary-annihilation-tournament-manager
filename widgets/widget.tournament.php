<?php

class tournament_info extends WP_Widget {


    /** constructor -- name this the same as the class above */
    function tournament_info() {
        parent::WP_Widget(false, $name = 'Tournament Info Widget');
    }

    /** @see WP_Widget::widget -- do not rename this */
    function widget($args, $instance) {
        extract( $args );

        global $wp_query, $post;

        $title 		= apply_filters('widget_title', $instance['title']);
        $message 	= $instance['message'];
        ?>
        <?php echo $before_widget; ?>

        <div class="tournament-widget-info">

            <section class="format tournament-meta-block text">
                <h3>Tournament Format</h3>

                <div class="row">

                <dl class="col-lg-6">
                    <?php if(get_field('rounds')) : ?>
                        <dt>Rounds</dt>
                        <dd><?php the_field('rounds'); ?></dd>
                    <?php endif; ?>

                    <?php if(get_field('tournament_type')) : ?>
                        <dt>Type</dt>
                        <dd><?php the_field('tournament_type'); ?></dd>
                    <?php endif; ?>
                </dl>

                <dl class="col-lg-6">
                    <?php if(get_field('slots')) : ?>
                        <dt>Player Slots/Taken/Remaining</dt>
                        <dd><?php the_field('slots'); ?>/<?php echo count(get_tournament_players($post->ID)); ?>/<?php echo ( get_field('slots') - count(get_tournament_players($post->ID)) ) ?></dd>
                    <?php endif; ?>
                </dl>

                </div>
            </section>

            <section class="prize-tiers tournament-meta-block text">
                <h3>Tournament Prizes</h3>

                    <?php if( have_rows('prize_tiers') ):

                        $price_count = count(get_field('prize_tiers'));

                        $html = '<ul style="margin-top:40px;">';

                        $column = 100;

                        $height = 100;

                        // loop through the rows of data
                        while ( have_rows('prize_tiers') ) : the_row();

                            $height -= (int) ( $column / $price_count );

                            $html .= sprintf('<li style="width:%s;"><div class="prize-container"><span class="the-prize" style="bottom:%s">%s</span><div class="prize" style="height:%s"></div></div><div class="place">%s</div></li>', ((int)( $column / $price_count)).'%', ($height).'%', get_sub_field('prize'), ($height).'%', get_sub_field('place'));

                            $column ++;

                        endwhile;

                        $html .= '</ul>';

                    endif; echo $html;?>

            </section>

            <section class="staff tournament-meta-block text ">
                <h3>Other Information</h3>

                <ul class="row">
                    <li class="col-lg-4">
                        <a href="<?php the_field('forum_link'); ?>">Form Link</a>
                    </li>

                    <li class="col-lg-4">
                        <a href="<?php the_field('brackets'); ?>">Bracket Link</a>
                    </li>

                    <li class="col-lg-4">
                        <a href="<?php the_field('irc'); ?>">IRC</a>
                    </li>

                </ul>
            </section>



        </div>
        <?php echo $after_widget; ?>
    <?php
    }

    /** @see WP_Widget::update -- do not rename this */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['message'] = strip_tags($new_instance['message']);
        return $instance;
    }

    /** @see WP_Widget::form -- do not rename this */
    function form($instance) {

        $title 		= esc_attr($instance['title']);
        $message	= esc_attr($instance['message']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('message'); ?>"><?php _e('Simple Message'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('message'); ?>" name="<?php echo $this->get_field_name('message'); ?>" type="text" value="<?php echo $message; ?>" />
        </p>
    <?php
    }
}
add_action('widgets_init', create_function('', 'return register_widget("tournament_info");'));