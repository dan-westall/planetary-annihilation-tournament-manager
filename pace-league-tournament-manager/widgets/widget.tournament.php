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

        <?php

        $args = array(
            'connected_type' => 'tournament_players',
            'connected_items' => $post
        );

        $players = get_posts( $args );

        ?>
        <div class="tournament-widget-info">

            <section class="format tournament-meta-block text">
                <h3>Tournament Format</h3>
                <dl>
                    <dt>Rounds</dt>
                    <dd><?php the_field('rounds'); ?></dd>
                    <dt>Player Slots/Taken/Remaining</dt>
                    <dd><?php the_field('slots'); ?>/<?php echo count($players); ?>/<?php echo ( get_field('slots') - count($players) ) ?></dd>
                    <dt>Type</dt>
                    <dd><?php the_field('tournament_type'); ?></dd>
                </dl>
            </section>

            <section class="prize-tiers tournament-meta-block text">
                <h3>Tournament Prizes</h3>
                <dl>

                    <?php if( have_rows('prize_tiers') ):

                        // loop through the rows of data
                        while ( have_rows('prize_tiers') ) : the_row(); ?>

                            <dt><?php the_sub_field('place'); ?></dt>
                            <dd><?php the_sub_field('prize'); ?></dd>

                        <?php endwhile;

                    endif; ?>

                </dl>
            </section>


            <section class="staff tournament-meta-block text ">
                <h3>Other Information</h3>
                <dl>

                    <dt>Forum Link</dt>
                    <dd><a href="<?php the_field('forum_link'); ?>"><?php the_field('forum_link'); ?></a></dd>
                    <dt>Bracket Link</dt>
                    <dd><a href="<?php the_field('brackets'); ?>"><?php the_field('brackets'); ?></a></dd>
                    <dt>IRC</dt>
                    <dd><a href="<?php the_field('irc'); ?>"><?php the_field('irc'); ?></a></dd>
                </dl>
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