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
<section class="text tournament-count-down">
        <?php

        if(get_field('tournament_status') === '0' || get_field('tournament_status') === '4'){
            if(get_field('run_date') && get_field('run_time')){
                $format_in = 'Ymd'; // the format your value is saved in (set in the field options)
                $rundate = DateTime::createFromFormat($format_in, get_field('run_date'));
                $form_short_code = sprintf('[countdown date="%s %s" size="large"]', $rundate->format('Y-m-d'), get_field('run_time'));
                //echo $form_short_code;
                echo do_shortcode($form_short_code);
            }
        }

        ?>

</section>
        <?php echo $before_widget; ?>



        <div class="tournament-widget-info">

            <section class="format tournament-meta-block text">
                <h3>Date</h3>
                <div class="row">
                    <?php

                        if(get_field('tournament_status') === '0' || get_field('tournament_status') === '4'){
                            if(get_field('run_date') && get_field('run_time')){

                                echo '<div class="col-lg-12">';

                                echo $rundate->format('l jS F Y') . ' @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a><br/><br/>';

                                echo '</div>';
                            }
                            else{
                                echo "To be announced";
                            }
                        }
                        else
                        {
                            if(get_field('tournament_status') === '1'){
                                    if(get_field('run_date') && get_field('run_time')){
                                        $format_in = 'Ymd'; // the format your value is saved in (set in the field options)
                                        $rundate = DateTime::createFromFormat($format_in, get_field('run_date'));
                                        echo $rundate->format('l jS F Y') . ' @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a><br/>';

                                    }
                                echo "<br/>LIVE NOW";
                            }
                            else{
                                if(get_field('tournament_status') === '2'){
                                    if(get_field('run_date') && get_field('run_time')){
                                        $format_in = 'Ymd'; // the format your value is saved in (set in the field options)
                                        $rundate = DateTime::createFromFormat($format_in, get_field('run_date'));
                                        echo $rundate->format('l jS F Y') . ' @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a><br/>';

                                    }
                                    echo "<br/>CANCELLED";
                                }
                                else{

                                    if(get_field('run_date') && get_field('run_time')){
                                        $format_in = 'Ymd'; // the format your value is saved in (set in the field options)
                                        $rundate = DateTime::createFromFormat($format_in, get_field('run_date'));
                                        echo $rundate->format('l jS F Y') . ' @ <a href="http://www.timeanddate.com/worldclock/fixedtime.html?msg=Tournament&iso=' . get_field('run_date') . 'T' . str_replace(':','',get_field('run_time')) . '" target="_blank">' . get_field('run_time') . ' UTC</a><br/>';

                                    }
                                    echo "<br/>Finished";
                                }
                            }
                        }
                    ?>

                    <br/>
                </div>
            </section>

            <section class="format tournament-meta-block text">
                <h3>Format</h3>

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
                        <dt>Slots/Open/Reserves</dt>
                        <dd><?php the_field('slots'); ?>/<?php echo ( get_field('slots') - count(get_tournament_players($post->ID, array(tournamentCPT::$tournament_player_status[0]))) ) ?>/ <?php echo count(get_tournament_players($post->ID, array(tournamentCPT::$tournament_player_status[1]))) ?></dd>
                    <?php endif; ?>
                </dl>

                </div>
            </section>

            <section class="prize-tiers tournament-meta-block text">

                <h3>Prizes</h3>

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
                <h3>Other Info</h3>

                <ul class="row">
                    <li class="col-lg-4">
                        <?php if(get_field('forum_link')) : ?>
                        <a href="<?php the_field('forum_link'); ?>">Forum Link</a>
                        <?php endif; ?>
                    </li>

                    <li class="col-lg-4">
                        <?php if(get_field('brackets')) : ?>
                        <a href="<?php the_field('brackets'); ?>">Bracket Link</a>
                        <?php endif; ?>
                    </li>

                    <li class="col-lg-4">
                        <?php if(get_field('irc')) : ?>
                        <a href="<?php the_field('irc'); ?>">Player Meeting Point</a>
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
