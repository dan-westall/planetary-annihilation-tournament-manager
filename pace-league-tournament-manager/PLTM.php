<?php
/**
 * Pace League Tournament Manager
 *
 * Custom plugin for PACE league, that allows management of tournaments
 *
 * @package   Pace_League_Tournament_Manager
 * @author    Dan Westall <dan.westall@googlemail.com>
 * @license   GPL-2.0+
 * @link      http://www.paceleague.com
 * @copyright 2014 Dan Westall
 *
 * @wordpress-plugin
 * Plugin Name:       Pace League Tournament Manager
 * Plugin URI:        http://www.paceleague.com
 * Description:       Custom plugin for PACE league, that allows management of tournaments
 * Version:           0.0.1
 * Author:            Dan Westall
 * Author URI:        http://www.dan-westall.co.uk
 * Text Domain:       PLTM
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-PLTM.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Pace_League_Tournament_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pace_League_Tournament_Manager', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Pace_League_Tournament_Manager', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-PLTM-admin.php' );
	add_action( 'plugins_loaded', array( 'Pace_League_Tournament_Manager_Admin', 'get_instance' ) );

}
