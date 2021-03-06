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

        $format_in = 'Ymd';


        $rundate = new DateTime( date("Y-m-d", strtotime(get_field('run_date'))), new DateTimeZone('UTC'));

        //check : is in time, because if not will mess datetime object up.
        if(strpos(get_field('run_time'), ':') !== false){
            $time = explode(':', get_field('run_time'));
            $rundate->setTime($time[0], $time[1]);
        } ?>

            <?php

            if(get_post_meta($post->ID, 'tournament_status', true) === '0' || get_post_meta($post->ID, 'tournament_status', true) === '4'){
                if(get_post_meta($post->ID, 'run_date', true) && get_post_meta($post->ID, 'run_time', true)){  ?>

                    <section class="text tournament-count-down">
                    <?php

                    $form_short_code = sprintf('[countdown date="%s %s" size="large"]', $rundate->format('Y-m-d'), get_post_meta($post->ID, 'run_time', true));
                    //echo $form_short_code;
                    echo do_shortcode($form_short_code); ?>
                    </section>
                    <?php
                }
            }

            ?>

            <?php if(($twitch = get_post_meta($post->ID, 'twitch', true)) && !in_array(get_post_meta($post->ID, 'tournament_status', true), [2, 3] ) ):

                $streams = explode(',', $twitch);

                foreach($streams as $key => $stream):

                    $title = 'Watch Here';

                    if(count($streams) > 1){
                        $title = 'Watch Stream '. ($key + 1);
                    } ?>

                    <section class="text __twitch tournament-stream" data-twitch-stream="<?php echo $stream; ?>">

                        <a href="http://www.twitch.tv/<?php echo $stream; ?>"><span><?php echo $title; ?></span></a>
                        <small>/<?php echo $stream; ?></small>

                    </section>

                <?php endforeach;

            endif; ?>

            <?php if(($twitch = get_post_meta($post->ID, 'hitbox', true)) && !in_array(get_post_meta($post->ID, 'tournament_status', true), [2, 3] ) ):

                $streams = explode(',', $twitch);

                foreach($streams as $key => $stream):

                    $title = 'Watch Here';

                    if(count($streams) > 1){
                        $title = 'Watch Stream '. ($key + 1);
                    } ?>

                    <section class="text __hitbox tournament-stream" data-twitch-stream="<?php echo $stream; ?>">

                        <a href="http://www.hitbox.tv/<?php echo $stream; ?>"><span><?php echo $title; ?></span></a>
                        <small>/<?php echo $stream; ?></small>

                    </section>

                <?php endforeach;

            endif; ?>


        <?php echo $before_widget; ?>


        <div class="tournament-widget-info">

            <section class="format tournament-meta-block text">
                <h3>Date</h3>
                <div class="row">
                    <div class="col-lg-12 text-center" style="margin-bottom: 15px;">
                        <?php tournamentCPT::get_tournament_date(); ?>
                    </div>
                </div>
            </section>

            <section class="format tournament-meta-block text">
                <h3>Format</h3>

                <div class="row">

                <dl class="col-lg-6">
                    <?php if(get_post_meta($post->ID, 'rounds', true)) : ?>
                        <dt>Rounds</dt>
                        <dd><?php echo get_post_meta($post->ID, 'rounds', true); ?></dd>
                    <?php endif; ?>

                    <?php if(get_post_meta($post->ID, 'tournament_type', true)) : ?>
                        <dt>Type</dt>
                        <dd><?php echo get_post_meta($post->ID, 'tournament_type', true); ?></dd>
                    <?php endif; ?>
                </dl>

                <dl class="col-lg-6">
                    <?php if(get_post_meta($post->ID, 'slots', true)) : ?>
                        <dt>Slots/Open/Reserves</dt>
                        <dd><?php echo get_post_meta($post->ID, 'slots', true); ?>/<?php echo ( get_post_meta($post->ID, 'slots', true) - tournamentCPT::get_tournament_player_count($post->ID, [tournamentCPT::$tournament_player_status[0]]) ) ?> / <?php echo tournamentCPT::get_tournament_player_count($post->ID, [tournamentCPT::$tournament_player_status[1]]) ?></dd>
                    <?php endif; ?>
                </dl>

                </div>
            </section>

            <?php if( get_field('prize_tiers') ): ?>

            <section class="prize-tiers tournament-meta-block text">

                <h3>Prizes</h3>


                        <?php

                        $price_count = count(get_field('prize_tiers'));

                        $html = '<ul class="prize-tiers-container">';

                        $column = 100;

                        $height = 100;

                        // loop through the rows of data
                        while ( have_rows('prize_tiers') ) : the_row();

                            $height -= (int) ( $column / $price_count );

                            $html .= sprintf('<li style="width:%s;"><div class="prize-container"><span class="the-prize" style="bottom:%s">%s</span><div class="prize" style="height:%s"></div></div><div class="place">%s</div></li>', ((int)( $column / $price_count)).'%', ($height).'%', get_sub_field('prize'), ($height).'%', get_sub_field('place'));

                            $column ++;

                        endwhile;

                        $html .= '</ul>';

                    echo $html;?>

            </section>

                <?php else : ?>


            <?php endif; ?>

            <section class="staff tournament-meta-block text ">
                <h3>Other Info</h3>

                <ul class="row">
                    <li class="col-lg-4">
                        <?php if(get_post_meta($post->ID, 'forum_link', true)) : ?>
                        <a href="<?php echo get_post_meta($post->ID, 'forum_link', true); ?>">Forum Link</a>
                        <?php endif; ?>
                    </li>

                    <li class="col-lg-4">
                        <?php if(get_post_meta($post->ID, 'brackets', true)) : ?>
                        <a href="<?php echo get_post_meta($post->ID, 'brackets', true); ?>">Bracket Link</a>
                        <?php endif; ?>
                    </li>

                    <li class="col-lg-4">
                        <?php if(get_post_meta($post->ID, 'irc', true)) : ?>
                        <a href="<?php echo get_post_meta($post->ID, 'irc', true); ?>">Player Meeting Point</a>
                        <?php endif; ?>
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
