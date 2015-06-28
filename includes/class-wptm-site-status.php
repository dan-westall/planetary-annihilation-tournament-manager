<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPTM_Site_Status {

    private $site_state = [ 'tournament-in-progress', 'tournament-not-inprogress' ];

    private $tournament_active_status = 'tournament-in-progress';

    function _construct(){

        add_action( 'wp_ajax_modify_site_status', [ $this, 'modify_site_status'] );
        add_action( 'wp_ajax_nopriv_modify_site_status',  [ $this, 'modify_site_status' ] );

        add_action( 'wp_dashboard_setup', [ $this, 'register_status_dashboard_widget' ] );

        add_filter( 'template_include', [ $this, 'site_take_over_template' ] );

    }

    //todo this shouldnt be here
    public function is_tournament_in_progress(){


        if(WPTM_Site_Status::get_site_status() === $this->$tournament_active_status)
            return true;

        return false;
    }

    public static function get_site_status(){

        return get_option('wptm_get_site_status');

    }

    public function modify_site_status(){

        check_ajax_referer('security-modify-site-state', 'security');

        $site_state = ($_POST['site_state']);

        update_option('wptm_get_site_status', $site_state);

        do_action( 'site_status_chanege', $site_state );

    }


    public function register_status_dashboard_widget(){

        wp_add_dashboard_widget( 'site_status_widget', 'Site Status', [ $this, 'display_status_dashboard_widget' ] );

    }

    public function display_status_dashboard_widget(){

        wp_enqueue_script( 'site_status', WPTM_PLUGIN_URI . '/admin/assets/js/site_status.js' );

        require_once WPTM_PLUGIN_DIR . '/admin/views/widget_dashboard_site_status.php';

    }

    public static function site_take_over_template($template_path){




    }



}