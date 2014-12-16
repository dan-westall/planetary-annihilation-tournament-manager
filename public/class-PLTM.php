<?php
/**
 * Planetary_Annihilation_Tournament_Manager
 *
 * @package   Planetary_Annihilation_Tournament_Manager
 * @author    Dan Westall <dan.westall@googlemail.com>
 * @license   GPL-2.0+
 * @link      http://www.dan-westall.co.uk
 * @copyright 2014 Dan Westall
 */

/**
 * Planetary_Annihilation_Tournament_Manager class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-PLTM-admin.php`
 *
 * @package Planetary_Annihilation_Tournament_Manager
 * @author  Dan Westall <dan.westall@googlemail.com>
 */
class Planetary_Annihilation_Tournament_Manager {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '0.0.1';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'PLTM';


    //todo move tournament endpoint functionality down into the tournament cpt class
    //public static $endpoints = array('signup', 'matches', 'players', 'results', 'schedule', 'rules', 'planets');
    public static $tournament_endpoints = array('signup', 'rules', 'matches', 'players', 'countdown', 'brackets', 'brackets-full', 'results');

    public static $match_endpoints = array('roster');
    public static $planet_servers = 'https://raw.githubusercontent.com/pamods/mods-conundrum/master/cShareSystems_serverList/serverlist.json';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

        require_once (dirname (__FILE__) . '/../bootstrap.php');

        // Load plugin text domain
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Activate plugin when new blog is added
        //add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

        // Load public-facing style sheet and JavaScript.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );


//        new planetCPT();
        //new sponsorCPT();
        new playerCPT();
        new tournamentCPT();
        new matchCPT();
        new notificationCPT();

        new PLTM_shortcodes();
        new gform_filters();

        //new userWager();

        userPolling::register();
        tournament_in_progress::register();
        ruleCPT::register();
        awards::register();
//        acfCustomRules::register();

        remove_action( 'wp_head', 'wp_generator');
        remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
        remove_action( 'wp_head', 'rsd_link');
        remove_action( 'wp_head', 'wlwmanifest_link');

        add_action( 'init', array( $this, 'register_tournament_endpoints' ) );

        add_filter( 'query_vars', array( $this, 'register_query_vars'), 0 );
        add_filter( 'body_class', array( $this, 'filter_endpoint_body_classes'));

        add_filter( 'gform_submit_button', array( $this, 'filter_form_button' ), 10, 2);

        // Global filters
        add_filter( 'acf/load_field/name=signup_form', array( $this, 'filter_form_listing') );
        add_filter( 'acf/load_field/name=standard_tournament_signup_form', array( $this, 'filter_form_listing') );
        add_filter( 'acf/load_field/name=country', array( $this, 'filter_form_country') );
        add_filter( 'acf/load_field/name=planet', array( $this, 'filter_planet') );
        add_filter( 'acf/load_field/name=planet_server', array( $this, 'filter_planet_server') );


        add_filter( 'json_prepare_user',  array( $this, 'clean_json_api' ), 20, 3 );


	}

    public function register_tournament_endpoints(){

        foreach(array_merge(self::$tournament_endpoints, self::$match_endpoints) as $endpoint){
            add_rewrite_endpoint($endpoint, EP_PERMALINK);

        }

    }

    public function register_query_vars($vars){

        foreach(array_merge(self::$tournament_endpoints, self::$match_endpoints) as $endpoint){
            $vars[] = $endpoint;
        }

        return $vars;
    }

    public static function fetch_challonge_API(){

        $challongeAPI = get_field('challonge_api','option');

        return $challongeAPI;

    }

    public function filter_endpoint_body_classes($classes){

        global $wp, $post;

        foreach(array_merge(self::$tournament_endpoints, self::$match_endpoints) as $endpoint){

            if (isset( $wp->query_vars[$endpoint] )) {

                $classes[] = $endpoint;

            }
        }

        return $classes;

    }

    public function filter_form_listing($field){

        $forms = RGFormsModel::get_forms( null, 'title' );

        foreach($forms as $form){

            $form_listing[$form->id] = $form->title;

        }

        $field['choices'] = $form_listing;

        return $field;

    }

    public function filter_planet($field){

        global $post;

        //?minPlanets=1&maxPlanets=16&start=0&limit=100&request_time=1&sort_field=system_id&sort_direction=desc&name=&creator=

        foreach($forms as $form){

            $form_listing[$form->id] = $form->title;

        }

        $field['choices'] = $form_listing;

        return $field;

    }

    public function filter_planet_server($field){

        if ( false === ( $planet_servers = get_transient( 'planet_servers' ) ) ) {

            $servers = wp_remote_get(self::$planet_servers);

            $planet_servers = json_decode($servers['body']);

            set_transient( 'planet_servers', $planet_servers, 6 * HOUR_IN_SECONDS  );
        }


        foreach($planet_servers->servers as $server){

            $server_listing[$server->search_url] = $server->name;

        }

        $field['choices'] = $server_listing;

        return $field;

    }

    public function filter_form_country($field){

        foreach(DW_Helper::countryListing() As $key => $county){

            $countries[$key] = $county;

        }

        $field['choices'] = $countries;

        return $field;

    }

    public function filter_form_button($button, $form){

        return sprintf("<a href='javascript:void(0);' id='gform_submit_button_{$form["id"]}' class='custom-button' onclick=\"$('#gform_{$form["id"]}').submit()\"><span>{$form['button']['text']}</span></a>", $form['button']['text'], $form['id']);

    }


    public function clean_json_api($_post, $post, $context){

        return [];

    }


	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		//wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		//wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

    private static function single_activate() {


    }

    private static function single_deactivate() {

    }

}
