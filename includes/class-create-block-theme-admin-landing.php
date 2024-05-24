<?php

/**
 * The wp-admin landing page for the Create Block Theme plugin.
 * @since      2.2.0
 * @package    Create_Block_Theme
 * @subpackage Create_Block_Theme/includes
 * @author     WordPress.org
 */
class CBT_Admin_Landing {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
	}

	function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$landing_page_slug       = 'create-block-theme-landing';
		$landing_page_title      = _x( 'Create Block Theme', 'UI String', 'create-block-theme' );
		$landing_page_menu_title = $landing_page_title;
		add_theme_page( $landing_page_title, $landing_page_menu_title, 'edit_theme_options', $landing_page_slug, array( 'CBT_Admin_Landing', 'admin_menu_page' ) );

	}

	public static function admin_menu_page() {

		$asset_file = include plugin_dir_path( __DIR__ ) . 'build/admin-landing-page.asset.php';

		// Enqueue CSS of the app
		wp_enqueue_style( 'create-block-theme-app', plugins_url( 'build/admin-landing-page.css', __DIR__ ), array( 'wp-components' ), $asset_file['version'] );

		// Load our app.js.
		wp_enqueue_script( 'create-block-theme-app', plugins_url( 'build/admin-landing-page.js', __DIR__ ), $asset_file['dependencies'], $asset_file['version'] );

		wp_localize_script(
			'create-block-theme-app',
			'cbt_landingpage_variables',
			array(
				'assets_url' => plugins_url( 'create-block-theme/assets/' ),
				'editor_url' => admin_url( 'site-editor.php?canvas=edit' ),
			)
		);

		// Enable localization in the app.
		wp_set_script_translations( 'create-block-theme-app', 'create-block-theme' );

		echo '<div id="create-block-theme-app"></div>';
	}
}
