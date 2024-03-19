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
		register_rest_route(
			'create-block-theme/v1',
			'/clone',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_clone_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
		register_rest_route(
			'create-block-theme/v1',
			'/create-blank',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_blank_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
		register_rest_route(
			'create-block-theme/v1',
			'/export-clone',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_export_cloned_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
		register_rest_route(
			'create-block-theme/v1',
			'/get-readme-data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_readme_data' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
	}

	function rest_get_readme_data( $request ) {
		try {
			$readme_data = Theme_Utils::get_readme_data();
			return new WP_REST_Response(
				array(
					'status'  => 'SUCCESS',
					'message' => __( 'Readme file data retrieved.', 'create-block-theme' ),
					'data'    => $readme_data,
				)
			);
		} catch ( Exception $error ) {
			return new WP_REST_Response(
				array(
					'status'  => 'FAILURE',
					'message' => $error->getMessage(),
				)
			);
		}
	}

	function rest_clone_theme( $request ) {

		$response = Theme_Create::clone_current_theme( $this->sanitize_theme_data( $request->get_params() ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response(
			array(
				'status'  => 'SUCCESS',
				'message' => __( 'Cloned Theme Created.', 'create-block-theme' ),
			)
		);
	}

	function rest_create_blank_theme( $request ) {

		$theme = $this->sanitize_theme_data( $request->get_params() );
		//TODO: Handle screenshots
		$screenshot = null;

		$response = Theme_Create::create_blank_theme( $theme, $screenshot );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response(
			array(
				'status'  => 'SUCCESS',
				'message' => __( 'Blank Theme Created.', 'create-block-theme' ),
			)
		);
	}

	function rest_export_cloned_theme( $request ) {

		//TODO: Handle Screenshots
		$screenshot = null;
		$theme      = $this->sanitize_theme_data( $request->get_params() );

		// Use previous theme's tags if custom tags are empty.
		if ( empty( $theme['tags_custom'] ) ) {
			$theme['tags_custom'] = implode( ', ', wp_get_theme()->get( 'Tags' ) );
		}

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::copy_theme_to_zip( $zip, $theme['slug'], $theme['name'] );
		$zip = Theme_Zip::add_templates_to_zip( $zip, 'all', $theme['slug'] );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'all' );

		// Add readme.txt.
		$zip->addFromStringToTheme(
			'readme.txt',
			Theme_Readme::build_readme_txt( $theme )
		);

		// Build style.css with new theme metadata
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, '*/' ) + 2 ) );
		$css_contents = Theme_Styles::build_child_style_css( $theme ) . $css_contents;
		$zip->addFromStringToTheme(
			'style.css',
			$css_contents
		);

		// Add / replace screenshot.
		if ( Theme_Utils::is_valid_screenshot( $screenshot ) ) {
			$zip->addFileToTheme(
				$screenshot['tmp_name'],
				'screenshot.png'
			);
		}

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
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

		if ( is_child_theme() ) {
			$zip = Theme_Zip::add_templates_to_zip( $zip, 'current', $theme_slug );
			$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'current' );
		} else {
			$zip = Theme_Zip::add_templates_to_zip( $zip, 'all', null );
			$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'all' );
		}

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
		$style_css = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$style_css = Theme_Styles::update_style_css( $style_css, $theme );
		file_put_contents( get_stylesheet_directory() . '/style.css', $style_css );
		file_put_contents(
			get_stylesheet_directory() . '/readme.txt',
			Theme_Readme::update_readme_txt( $theme )
		);

		// Relocate the theme to a new folder
		$response = Theme_Utils::relocate_theme( $theme['subfolder'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response(
			array(
				'status'  => 'SUCCESS',
				'message' => __( 'Theme Updated.', 'create-block-theme' ),
			)
		);
	}

	/**
	 * Save the user changes to the theme and clear user changes.
	 */
	function rest_save_theme( $request ) {

		Theme_Fonts::persist_font_settings();

		if ( is_child_theme() ) {
			Theme_Templates::add_templates_to_local( 'current' );
			Theme_Json::add_theme_json_to_local( 'current' );
		} else {
			Theme_Templates::add_templates_to_local( 'all' );
			Theme_Json::add_theme_json_to_local( 'all' );
		}
		Theme_Styles::clear_user_styles_customizations();
		Theme_Templates::clear_user_templates_customizations();

		return new WP_REST_Response(
			array(
				'status'  => 'SUCCESS',
				'message' => __( 'Theme Saved.', 'create-block-theme' ),
			)
		);
	}

	private function sanitize_theme_data( $theme ) {
		$sanitized_theme['name']                = sanitize_text_field( $theme['name'] );
		$sanitized_theme['description']         = sanitize_text_field( $theme['description'] );
		$sanitized_theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$sanitized_theme['author']              = sanitize_text_field( $theme['author'] );
		$sanitized_theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$sanitized_theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$sanitized_theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$sanitized_theme['template']            = '';
		$sanitized_theme['slug']                = Theme_Utils::get_theme_slug( $theme['name'] );
		$sanitized_theme['text_domain']         = $theme['slug'];
		return $sanitized_theme;
	}
}
