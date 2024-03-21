<?php

class Create_Block_Theme_Admin_Landing {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
	}

	function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$landing_page_slug     = 'create-block-theme-landing';
		$landing_page_title = _x( 'Create Block Theme', 'UI String', 'create-block-theme' );
		$landing_page_menu_title = $landing_page_title;
		add_theme_page( $landing_page_title, $landing_page_menu_title, 'edit_theme_options', $landing_page_slug, array( 'Create_Block_Theme_Admin_Landing', 'admin_menu_page' ) );

	}

	public static function admin_menu_page() {
		echo '<div id="create-block-theme-app">Landing Page to go Here</div>';
	}
}

