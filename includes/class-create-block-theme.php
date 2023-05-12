<?php

/**
 * The core Create Block Theme plugin class.
 *
 * @since      0.0.2
 * @package    Create_Block_Theme
 * @subpackage Create_Block_Theme/includes
 * @author     WordPress.org
 */
#[AllowDynamicProperties]
class Create_Block_Theme {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    0.0.2
	 */
	public function __construct() {

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    0.0.2
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-create-block-theme-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-create-theme.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-manage-fonts.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wp-org-theme-directory.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-create-block-theme-api.php';

		$this->loader = new Create_Block_Theme_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.0.2
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin       = new Create_Block_Theme_Admin();
		$manage_fonts_admin = new Manage_Fonts_Admin();
		$wp_theme_directory = new WP_Theme_Directory();
		$plugin_api         = new Create_Block_Theme_API();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.0.2
	 */
	public function run() {
		$this->loader->run();
	}
}
