<?php

class WP_Theme_Directory {

	const THEME_NAMES_ENDPOINT = 'https://themes.svn.wordpress.org/';

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_theme_names_endpoint' ) );
		add_action( 'admin_init', array( $this, 'assets_enqueue' ) );
	}

	public static function register_theme_names_endpoint() {
		register_rest_route(
			'create-block-theme/v1',
			'/wp-org-theme-names',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'WP_Theme_Directory', 'get_theme_names' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);

	}

	public static function get_theme_names() {
		$html = wp_remote_get( self::THEME_NAMES_ENDPOINT );

		// parse the html response extracting all the a inside li elements
		$pattern = '/<li><a href=".*?">(.*?)<\/a><\/li>/';
		preg_match_all( $pattern, $html['body'], $matches );

		// Revemo the / from the end of the theme name
		$cleaned_names = array_map(
			function ( $name ) {
				return str_replace( '/', '', $name );
			},
			$matches[1]
		);

		$names = array( 'names' => $cleaned_names );
		return rest_ensure_response( $names );
	}

	function assets_enqueue() {
		$asset_file = include( plugin_dir_path( dirname( __FILE__ ) ) . 'build/wp-org-theme-directory.asset.php' );

		wp_register_script(
			'wp-org-theme-directory',
			plugins_url( 'build/wp-org-theme-directory.js', dirname( __FILE__ ) ),
			$asset_file['dependencies'],
			$asset_file['version']
		);

		wp_enqueue_script(
			'wp-org-theme-directory',
		);

		// Initialize and empty array of theme names to be shared between different client side scripts
		wp_localize_script(
			'wp-org-theme-directory',
			'wpOrgThemeDirectory',
			array(
				'themeSlugs' => null,
			)
		);
	}

}


