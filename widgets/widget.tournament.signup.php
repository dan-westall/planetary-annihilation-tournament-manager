<?php

class tournament_signup extends WP_Widget {


    /** constructor -- name this the same as the class above */
    function tournament_signup() {
        parent::WP_Widget(false, $name = 'Tournament Action - Signup');
    }

    /** @see WP_Widget::widget -- do not rename this */
    function widget($args, $instance) {
        extract( $args );

        global $wp_query, $post, $current_user;

        get_currentuserinfo();

        $title 		= apply_filters('widget_title', $instance['title']);
        $message 	= $instance['message'];

        ?>
        <?php if(!isset($wp_query->query_vars['sign-up']) && is_tournament_signup_open($post->ID) && is_player_in_tournament($post->ID, $current_user->player_id) == false): ?>

            <a href="<?php the_permalink(); ?>sign-up" class='tournament-btn __signup tournament-signup-button'><span>Sign-up to tournament</span></a>

        <?php elseif(is_player_in_tournament($post->ID, $current_user->player_id, [ tournamentCPT::$tournament_player_status[0], tournamentCPT::$tournament_player_status[1] ]) == true && tournamentCPT::allow_withdraw($post->ID) == true):

            wp_enqueue_script('angularparts.tournament.withdraw');

            ?>

            <div ng-controller="tournamentWithdraw as Withdraw">

                <button id="tournament-withdraw" class='tournament-btn __withdraw' ng-click="Withdraw.promptConfirm();" ng-hide="Withdraw.hasWithdraw"><span>Withdraw</span></button>

            </div>

        <?php elseif(is_player_in_tournament($post->ID, $current_user->player_id, [ tournamentCPT::$tournament_player_status[5] ] ) && is_tournament_signup_open($post->ID)):

            wp_enqueue_script('angularparts.tournament.withdraw');

            ?>

            <div ng-controller="tournamentWithdraw as Withdraw">

                <button id="tournament-withdraw" class='tournament-btn __signup' ng-click="Withdraw.reverse();" ng-hide="Withdraw.hasWithdraw"><span>Re-Enter</span></button>

            </div>

        <?php endif; ?>

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

        //todo add default to stop notice errors

        $title 		= esc_attr($instance['title']);
        $message	= esc_attr($instance['message']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
    <?php
    }


}
add_action('widgets_init', create_function('', 'return register_widget("tournament_signup");'));