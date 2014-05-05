<?php

class tournament_staff extends WP_Widget {


    /** constructor -- name this the same as the class above */
    function tournament_staff() {
        parent::WP_Widget(false, $name = 'Tournament Info - Staff');
    }

    /** @see WP_Widget::widget -- do not rename this */
    function widget($args, $instance) {
        extract( $args );

        global $wp_query, $post;

        $title 		= apply_filters('widget_title', $instance['title']);
        $message 	= $instance['message'];
        ?>
        <?php


        $args = array(
            'connected_type' => 'tournament_staff',
            'connected_items' => $post
        );

        $staff = get_users( $args );


        $args = array(
            'connected_type' => 'tournament_players',
            'connected_items' => $post
        );

        $players = get_posts( $args );


        ?>

        <?php  if(!empty($staff)) :?>
        <?php echo $before_widget; ?>

        <div class="tournament-widget-info">

            <section class="staff tournament-meta-block">
                <h3>Staff Members</h3>



                <table>

                    <?php

                    foreach($staff as $staff_member):

                        echo '<tr>';

                        if(get_user_meta($staff_member->ID, 'twitch_url', true)) : ?>

                            <td class="has-twitch staff-member"><?php echo $staff_member->display_name; ?> <a class="twitch-link" href="<?php echo get_user_meta($staff_member->ID, 'twitch_url', true); ?>"></a><br />



                            </td>

                            <td>
                                <strong>(<?php echo p2p_get_meta( $staff_member->data->p2p_id, 'role', true ); ?>)</strong>
                            </td>

                        <?php else : ?>

                            <td class=" staff-member"><?php echo $staff_member->display_name; ?>

                                </td>

                            <td>
                                <strong>(<?php echo p2p_get_meta( $staff_member->data->p2p_id, 'role', true ); ?>)</strong>
                            </td>

                        <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </section>

        </div>
        <?php echo $after_widget; ?>
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
add_action('widgets_init', create_function('', 'return register_widget("tournament_staff");'));