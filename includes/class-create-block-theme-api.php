<?php

/**
 * The api functionality of the plugin leveraged by the site editor UI.
 *
 * @package    Create_Block_Theme
 * @subpackage Create_Block_Theme/admin
 * @author     WordPress.org
 */
class Create_Block_Theme_API {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the REST routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'create-block-theme/v1',
			'/export',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_export_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
		register_rest_route(
			'create-block-theme/v1',
			'/update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_update_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
		register_rest_route(
			'create-block-theme/v1',
			'/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_save_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
	}

	/**
	 * Export the theme as a ZIP file.
	 */
	function rest_export_theme( $request ) {
		$theme_slug = wp_get_theme()->get( 'TextDomain' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme_slug );
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::copy_theme_to_zip( $zip, null, null );
		$zip = Theme_Zip::add_templates_to_zip( $zip, 'all', null );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'all' );

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme_slug . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
	}

	/**
	 * Update the theme metadata and relocate the theme.
	 */
	function rest_update_theme( $request ) {
		$theme = $request->get_params();

		// Update the metadata of the theme in the style.css file
		$this->update_theme_metadata( $theme );

		// Relocate the theme to a new folder
		$this->relocate_theme( $theme['subfolder'] );

	}

	/**
	 * Save the user changes to the theme and clear user changes.
	 */
	function rest_save_theme( $request ) {
		if ( is_child_theme() ) {
			Theme_Templates::add_templates_to_local( 'current' );
			Theme_Json::add_theme_json_to_local( 'current' );
		} else {
			Theme_Templates::add_templates_to_local( 'current' );
			Theme_Json::add_theme_json_to_local( 'current' );
		}
		Theme_Styles::clear_user_styles_customizations();
		Theme_Templates::clear_user_templates_customizations();
	}

	/**
	 * Update the theme metadata in the style.css file.
	 */
	function update_theme_metadata( $theme ) {
		$theme['slug'] = Theme_Utils::get_theme_slug( $theme['name'] );
		$style_css     = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$css_contents  = trim( substr( $style_css, strpos( $style_css, '*/' ) + 2 ) );
		$style_css     = Theme_Styles::build_child_style_css( $theme ) . $css_contents;
		file_put_contents( get_stylesheet_directory() . '/style.css', $style_css );
	}

	/**
	 * Relocate the theme to a new folder and activate the newly relocated theme.
	 */
	function relocate_theme( $new_theme_subfolder ) {

		$current_theme_subfolder = '';
		$theme_dir               = get_stylesheet();

		if ( str_contains( get_stylesheet(), '/' ) ) {
			$current_theme_subfolder = substr( get_stylesheet(), 0, strrpos( get_stylesheet(), '/' ) );
			$theme_dir               = substr( get_stylesheet(), strrpos( get_stylesheet(), '/' ) + 1 );
		}

		if ( $current_theme_subfolder === $new_theme_subfolder ) {
			return;
		}

		$source      = get_theme_root() . '/' . $current_theme_subfolder . '/' . $theme_dir;
		$destination = get_theme_root() . '/' . $new_theme_subfolder . '/' . $theme_dir;

		wp_mkdir_p( get_theme_root() . '/' . $new_theme_subfolder );
		rename( $source, $destination );

		switch_theme( $new_theme_subfolder . '/' . $theme_dir );
	}

}
