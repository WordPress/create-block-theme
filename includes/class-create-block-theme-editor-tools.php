<?php

class Create_Block_Theme_Editor_Tools {

	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'create_block_theme_sidebar_enqueue' ) );
	}

	function create_block_theme_sidebar_enqueue() {
		global $pagenow;

		if ( 'site-editor.php' !== $pagenow ) {
			return;
		}

		$asset_file = include plugin_dir_path( dirname( __FILE__ ) ) . 'build/plugin-sidebar.asset.php';

		wp_register_script(
			'create-block-theme-slot-fill',
			plugins_url( 'build/plugin-sidebar.js', dirname( __FILE__ ) ),
			$asset_file['dependencies'],
			$asset_file['version']
		);
		wp_enqueue_script(
			'create-block-theme-slot-fill',
		);

		// Enable localization in the plugin sidebar.
		wp_set_script_translations( 'create-block-theme-slot-fill', 'create-block-theme' );
	}
}
