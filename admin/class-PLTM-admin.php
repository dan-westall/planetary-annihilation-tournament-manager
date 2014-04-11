<?php
/**
 * Planetary_Annihilation_Tournament_Manager
 *
 * @package   Planetary_Annihilation_Tournament_Manager_Admin
 * @author    Dan Westall <dan.westall@googlemail.com>
 * @license   GPL-2.0+
 * @link      http://www.paceleague.com
 * @copyright 2014 Dan Westall
 */

/**
 * Planetary_Annihilation_Tournament_Manager_Admin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Planetary_Annihilation_Tournament_Manager_Admin
 * @author  Dan Westall <dan.westall@googlemail.com>
 */
class Planetary_Annihilation_Tournament_Manager_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = Planetary_Annihilation_Tournament_Manager::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

        add_action( 'gform_field_advanced_settings', array( $this, 'action_add_gravity_forms_map_field'), 10, 2);
        add_action( 'gform_editor_js', array( $this, 'action_add_gravity_forms_map_field_setting') );
        add_action( 'manage_posts_custom_column', array( $this, 'custom_columns'), 10, 2 );

        add_filter( 'manage_tournament_posts_columns', array( $this, 'admin_columns' ) );
        add_filter( 'gform_tooltips', array( $this, 'filter_gravity_forms_tooltips') );

        add_filter( 'manage_users_columns', array( $this, 'modify_user_table') );
        add_filter( 'manage_users_custom_column', array( $this, 'modify_user_table_row'), 10, 3 );

    }

    public function admin_columns( $columns ) {

        global $post, $current_user; get_currentuserinfo();

        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'title'  => __('Title'),
            'spaces'     => __('Open Slots / Taken slots'),
            'run_date' => __('Run date'),
            'admin' => __('Tournament Director'),
            'status'     => __('Status')
        );

        return $columns;

    }

    public function modify_user_table( $column ) {
        $column['clan'] = __('Clan');

        return $column;
    }

    public function modify_user_table_row( $val, $column_name, $user_id ) {
        $user = get_userdata( $user_id );

        switch ($column_name) {
            case 'clan' :

                return get_user_meta($user_id, 'clan', true);

                break;

        }

    }



    public function custom_columns( $column, $post_id ) {

        global $current_user; get_currentuserinfo();

        switch ( $column ) {

            case 'amount';

                echo '&pound; ';

                echo get_post_meta($post_id, 'total_product_price', true);

                break;

            case 'the_customer';

                $the_post = get_post($post_id);

                $user = get_userdata( $the_post->post_author );

                echo $user->user_email;

                break;

            case 'order_form';

                echo '<a href="'.get_permalink($post_id).'view" target="_blank">View PO Form</a>';

                break;

            case 'status';

                echo get_post_meta($post_id, 'tournament_status', true);

                break;

        }
    }

    function action_add_gravity_forms_map_field($position, $form_id){

        //create settings on position 50 (right after Admin Label)
        if($position == 50){
            ?>
            <li class="field_map_setting field_setting">
                <label for="field_admin_label">
                    <?php _e("Field Map", "gravityforms"); ?>
                    <?php gform_tooltip("form_field_field_map_value") ?>
                </label>
                <input type="text" id="field_field_map_value" onchange="SetFieldProperty('field_mapField', this.value);" />
            </li>
        <?php
        }
    }

    //Action to inject supporting script to the form editor page

    function action_add_gravity_forms_map_field_setting(){
        ?>
        <script type='text/javascript'>
            //adding setting to fields of type "text"
            fieldSettings["text"] += ", .field_map_setting";
            fieldSettings["email"] += ", .field_map_setting";
            fieldSettings["textarea"] += ", .field_map_setting";

            //binding to the load field settings event to initialize the checkbox
            jQuery(document).bind("gform_load_field_settings", function(event, field, form){

                jQuery("#field_field_map_value").val(field["field_mapField"]);
            });
        </script>
    <?php
    }

    //Filter to add a new tooltip

    function filter_gravity_forms_tooltips($tooltips){
        $tooltips["form_field_encrypt_value"] = "<h6>Field Map</h6>Check this box to encrypt this field's data";
        return $tooltips;
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Planetary_Annihilation_Tournament_Manager::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Planetary_Annihilation_Tournament_Manager::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Pace League Tournament Manager Settings', $this->plugin_slug ),
			__( 'Pace League Tournament Manager', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}


}
