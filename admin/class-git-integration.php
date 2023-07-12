<?php

require_once( __DIR__ . '/git-integration/git-themes.php' );

class Git_Integration_Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );

		if ( !defined('CREATE_BLOCK_THEME_GIT_DIR') ) {
			$plugin_dir = WP_PLUGIN_DIR.'/create-block-theme';
        	$theme_repos_dir = $plugin_dir.'/.git-repo';
			
			define('CREATE_BLOCK_THEME_GIT_DIR', $theme_repos_dir);
			wp_mkdir_p($theme_repos_dir);
		}
	}

	function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}
		$page_title = _x( 'Git Integration for Themes', 'UI String', 'create-block-theme' );
		$menu_title = _x( 'Git Integration for Themes', 'UI String', 'create-block-theme' );
		add_theme_page( $page_title, $menu_title, 'edit_theme_options', 'git-themes', array( 'Git_Themes', 'create_admin_page' ) );
	}
}
