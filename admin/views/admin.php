<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Planetary_Annihilation_Tournament_Manager
 * @author    Dan Westall <dan.westall@googlemail.com>
 * @license   GPL-2.0+
 * @link      http://www.dan-westall.co.uk
 * @copyright 2014 Dan Westall
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<!-- @TODO: Provide markup for your options page here. -->

    <?php


    global $wpdb;
//
//    $args = array(
//        'post_type' => playerCPT::$post_type,
//        'posts_per_page' => -1
//    );
//
//    $players = get_posts($args);
//
//    foreach($players as $player){
//
//        $user_id = (int) get_post_meta($player->ID, 'user_id', true);
//
//        if(is_int($user_id)){
//
//            $user_account    = get_user_by('id', $user_id);
//
//            if(get_user_meta($user_id, 'nickname', true) != $player->post_title){
//
//                $wp_user_id = wp_update_user( array( 'ID' => $user_id, 'nickname' => $player->post_title, 'display_name' => $player->post_title ) );
//
//                //if(is_wp_error($user_id)){
//
//                    echo $user_id;
//                    var_dump($wp_user_id);
//                //}
//
//                printf('Updated user %s, new nickname = "%s", set display name <br />', $user_id, $player->post_title);
//
//            }
//
//        }
//
//    }

    ?>

</div>
