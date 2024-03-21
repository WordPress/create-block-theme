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
		$landing_page_title = _x( 'Manage Theme Fonts', 'UI String', 'create-block-theme' );
		$landing_page_menu_title = $landing_page_title;
		add_theme_page( $landing_page_title, $landing_page_menu_title, 'edit_theme_options', $landing_page_slug, array( 'Landing_Page', $landing_page_slug ) );

		// Check if the admin page title is set, and if not, set one.
		// This is needed to avoid a warning in the admin menu, due to the admin page title not being set in
		// the add_submenu_page() function for the Google Fonts and Local Fonts pages.
		global $title;

		// Check if current admin page is a create block theme page.
		// Context: https://github.com/WordPress/create-block-theme/issues/478
		$admin_page        = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$manage_font_pages = array(
			$manage_font_page_slug,
			$add_google_font_page_slug,
			$add_local_font_page_slug,
		);

		$is_manage_fonts_page = in_array( $admin_page, $manage_font_pages, true );

		if ( ! isset( $title ) && $is_manage_fonts_page ) {
			$title = $manage_fonts_page_title;
		}

		$local_fonts_page_title = _x( 'Embed local font in the active theme', 'UI String', 'create-block-theme' );
		$local_fonts_menu_title = $local_fonts_page_title;
		add_submenu_page( '', $local_fonts_page_title, $local_fonts_menu_title, 'edit_theme_options', $add_local_font_page_slug, array( 'Local_Fonts', 'local_fonts_admin_page' ) );
	}
}
