<?php

class playerCPT {

    public static $post_type = 'player';

    function __construct() {

        add_action('init', array($this, 'register_cpt_player'));

        add_action( 'user_register', array( $this, 'action_new_player_profile' ) );

        add_action( 'p2p_init', array( $this, 'register_p2p_connections' ) );

    }

    function register_cpt_player() {

        $playerLabel = array(
            'name'               => __('Players'),
            'menu_name'          => __('Players'),
            'all_items'          => __('All Players'),
            'singular_name'      => __('All Players'),
            'add_new_item'       => __('Add New Player'),
            'edit_item'          => __('Edit Player'),
            'new_item'           => __('New Player'),
            'view_item'          => __('View Player'),
            'search_items'       => __('Search Players'),
            'not_found'          => __('No Players found'),
            'not_found_in_trash' => __('No Players found in trash')
        );

        $playerArgs = array(
            'labels'              => $playerLabel,
            'description'         => 'Tournament Players',
            'public'              => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'menu_position'       => 10,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array('title')
        );

        register_post_type( self::$post_type, $playerArgs );

    }

    public function register_p2p_connections() {



    }

    public function action_new_player_profile($user_id){




    }

    public static function get_player_by($id, $switch = 'pastats_player_id'){

        switch($switch){

            case "pastats_player_id":

                $player_id = DW_Helper::get_post_by_meta('pastats_player_id', $id);

                break;

        }

        return $player_id;

    }

    public static function get_player($attr) {

        extract(shortcode_atts(array(
            'player_id' => '',
            'by' => '',
            'output'        => 'html'
        ), $attr));

        $player_id = self::get_player_by($player_id, $by);

        $player = get_post($player_id);

        $data = self::player_return_format($player);

        switch($output){

            case "json":

                wp_send_json_success($data);

                break;

            case "html" :


                break;

            case "raw" :

                return $data;

                break;
        }

    }

    public static function player_return_format($player, $data = array()){

        $data['name']               = $player->post_title;
        $data['clan']               = get_post_meta($player->ID, 'clan', true);
        $data['pa_stats_player_id'] = get_post_meta($player->ID, 'pa_stats_player_id', true);




        return $data;

    }
}