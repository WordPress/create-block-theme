<?php

/**
 * The Editor integration for the Create Block Theme plugin.
 * @since      2.2.0
 * @package    Create_Block_Theme
 * @subpackage Create_Block_Theme/includes
 * @author     WordPress.org
 */
class CBT_Editor_Tools {

	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'create_block_theme_sidebar_enqueue' ) );
	}

	function create_block_theme_sidebar_enqueue() {
		global $pagenow;

		if ( 'site-editor.php' !== $pagenow || ! wp_is_block_theme() ) {
			return;
		}

		$asset_file = include plugin_dir_path( dirname( __FILE__ ) ) . 'build/plugin-sidebar.asset.php';

		wp_register_script(
			'create-block-theme-slot-fill',
			plugins_url( 'build/plugin-sidebar.js', dirname( __FILE__ ) ),
			$asset_file['dependencies'],
			$asset_file['version']
		);
		wp_enqueue_style(
			'create-block-theme-styles',
			plugins_url( 'build/plugin-sidebar.css', dirname( __FILE__ ) ),
			array(),
			$asset_file['version']
		);
		wp_enqueue_script(
			'create-block-theme-slot-fill',
		);

		global $wp_version;
		wp_add_inline_script(
			'create-block-theme-slot-fill',
			'const WP_VERSION = "' . $wp_version . '";',
			'before'
		);

		// Enable localization in the plugin sidebar.
		wp_set_script_translations( 'create-block-theme-slot-fill', 'create-block-theme' );
	}
}
