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
		$theme = $request->get_params();

		//TODO: Handle uploading a screenshot somehow...
		$screenshot = null;

		$theme_slug = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']                = sanitize_text_field( $theme['name'] );
		$theme['description']         = sanitize_text_field( $theme['description'] );
		$theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$theme['author']              = sanitize_text_field( $theme['author'] );
		$theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$theme['template']            = '';
		$theme['slug']                = $theme_slug;
		$theme['text_domain']         = $theme_slug;

		// Create theme directory.
		$source         = get_stylesheet_directory();
		$new_theme_path = get_theme_root() . DIRECTORY_SEPARATOR . $theme['slug'];

		if ( $theme['subfolder'] ) {
			$new_theme_path = get_theme_root() . DIRECTORY_SEPARATOR . $theme['subfolder'] . DIRECTORY_SEPARATOR . $theme['slug'];
		}

		if ( file_exists( $new_theme_path ) ) {
			return new \WP_Error( 'theme_exists', __( 'Theme already exists.', 'create-block-theme' ) );
		}

		wp_mkdir_p( $new_theme_path );

		// Copy theme files.
		Theme_Utils::clone_theme_to_folder( $new_theme_path, $theme['slug'], $theme['name'] );
		Theme_Utils::add_templates_to_folder( $new_theme_path, 'all', $theme['slug'] );

		file_put_contents( $new_theme_path . DIRECTORY_SEPARATOR . 'theme.json', MY_Theme_JSON_Resolver::export_theme_data( 'all' ) );

		if ( $theme['subfolder'] ) {
			switch_theme( $theme['subfolder'] . '/' . $theme_slug );
		} else {
			switch_theme( $theme_slug );
		}

		return new WP_REST_Response(
			array(
				'status'  => 'SUCCESS',
				'message' => __( 'Cloned Theme Created.', 'create-block-theme' ),
			)
		);
	}

	function rest_create_blank_theme( $request ) {

		$theme = $request->get_params();

		//TODO: Handle uploading a screenshot somehow...
		$screenshot = null;

		$theme_slug = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']        = sanitize_text_field( $theme['name'] );
		$theme['description'] = sanitize_text_field( $theme['description'] );
		$theme['uri']         = sanitize_text_field( $theme['uri'] );
		$theme['author']      = sanitize_text_field( $theme['author'] );
		$theme['author_uri']  = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom'] = sanitize_text_field( $theme['tags_custom'] );
		$theme['template']    = '';
		$theme['slug']        = $theme_slug;
		$theme['text_domain'] = $theme_slug;

		// Create theme directory.
		$source           = plugin_dir_path( __DIR__ ) . 'assets/boilerplate';
		$blank_theme_path = get_theme_root() . DIRECTORY_SEPARATOR . $theme['subfolder'] . DIRECTORY_SEPARATOR . $theme['slug'];

		if ( file_exists( $blank_theme_path ) ) {
			return new \WP_Error( 'theme_exists', __( 'Theme already exists.', 'create-block-theme' ) );
		}

		wp_mkdir_p( $blank_theme_path );

		// Add readme.txt.
		file_put_contents(
			$blank_theme_path . DIRECTORY_SEPARATOR . 'readme.txt',
			Theme_Readme::build_readme_txt( $theme )
		);

		// Add new metadata.
		$css_contents = Theme_Styles::build_child_style_css( $theme );

		// Add style.css.
		file_put_contents(
			$blank_theme_path . DIRECTORY_SEPARATOR . 'style.css',
			$css_contents
		);

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach (
			$iterator as $item
			) {
			if ( $item->isDir() ) {
				wp_mkdir_p( $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname() );
			} else {
				copy( $item, $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname() );
			}
		}

		// Overwrite default screenshot if one is provided.
		if ( $this->is_valid_screenshot( $screenshot ) ) {
			file_put_contents(
				$blank_theme_path . DIRECTORY_SEPARATOR . 'screenshot.png',
				file_get_contents( $screenshot['tmp_name'] )
			);
		}

		if ( ! defined( 'IS_GUTENBERG_PLUGIN' ) ) {
			global $wp_version;
			$theme_json_version = 'wp/' . substr( $wp_version, 0, 3 );
				$schema         = '"$schema": "https://schemas.wp.org/' . $theme_json_version . '/theme.json"';
			$theme_json_path    = $blank_theme_path . DIRECTORY_SEPARATOR . 'theme.json';
			$theme_json_string  = file_get_contents( $theme_json_path );
			$theme_json_string  = str_replace( '"$schema": "https://schemas.wp.org/trunk/theme.json"', $schema, $theme_json_string );
			file_put_contents( $theme_json_path, $theme_json_string );
		}

		if ( $theme['subfolder'] ) {
			switch_theme( $theme['subfolder'] . '/' . $theme_slug );
		} else {
			switch_theme( $theme_slug );
		}

		return new WP_REST_Response(
			array(
				'status'  => 'SUCCESS',
				'message' => __( 'Blank Theme Created.', 'create-block-theme' ),
			)
		);
	}

	function rest_export_cloned_theme( $request ) {
		$theme = $request->get_params();

		$theme_slug = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']                = sanitize_text_field( $theme['name'] );
		$theme['description']         = sanitize_text_field( $theme['description'] );
		$theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$theme['author']              = sanitize_text_field( $theme['author'] );
		$theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$theme['slug']                = $theme_slug;
		$theme['template']            = '';
		$theme['original_theme']      = wp_get_theme()->get( 'Name' );
		$theme['text_domain']         = $theme_slug;

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
		if ( $this->is_valid_screenshot( $screenshot ) ) {
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
		$response = $this->update_theme_metadata( $theme );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Relocate the theme to a new folder
		$response = $this->relocate_theme( $theme['subfolder'] );

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

	/**
	 * Update the theme metadata in the style.css and readme.txt files.
	 */
	function update_theme_metadata( $theme ) {
		$style_css = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$style_css = Theme_Styles::update_style_css( $style_css, $theme );
		file_put_contents( get_stylesheet_directory() . '/style.css', $style_css );
		file_put_contents(
			get_stylesheet_directory() . '/readme.txt',
			Theme_Readme::update_readme_txt( $theme )
		);
	}

	/**
	 * Relocate the theme to a new folder and activate the newly relocated theme.
	 */
	function relocate_theme( $new_theme_subfolder ) {

		$current_theme_subfolder = '';
		$theme_dir               = get_stylesheet();

		$source      = get_theme_root() . DIRECTORY_SEPARATOR . $theme_dir;
		$destination = get_theme_root() . DIRECTORY_SEPARATOR . $theme_dir;

		if ( str_contains( get_stylesheet(), '/' ) ) {
			$current_theme_subfolder = substr( get_stylesheet(), 0, strrpos( get_stylesheet(), '/' ) );
			$theme_dir               = substr( get_stylesheet(), strrpos( get_stylesheet(), '/' ) + 1 );
			$source                  = get_theme_root() . DIRECTORY_SEPARATOR . $current_theme_subfolder . DIRECTORY_SEPARATOR . $theme_dir;
			$destination             = get_theme_root() . DIRECTORY_SEPARATOR . $theme_dir;
		}

		if ( $new_theme_subfolder ) {
			$destination = get_theme_root() . DIRECTORY_SEPARATOR . $new_theme_subfolder . DIRECTORY_SEPARATOR . $theme_dir;
			wp_mkdir_p( get_theme_root() . DIRECTORY_SEPARATOR . $new_theme_subfolder );
		}

		if ( $source === $destination ) {
			return;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$success = move_dir( $source, $destination, false );

		if ( ! $success ) {
			return new \WP_Error( 'problem_moving', __( 'There was a problem moving the theme', 'create-block-theme' ) );
		}

		if ( $new_theme_subfolder ) {
			switch_theme( $new_theme_subfolder . '/' . $theme_dir );
		} else {
			switch_theme( $theme_dir );
		}
	}

	const ALLOWED_SCREENSHOT_TYPES = array(
		'png' => 'image/png',
	);

	function is_valid_screenshot( $file ) {
		$filetype = wp_check_filetype( $file['name'], self::ALLOWED_SCREENSHOT_TYPES );
		if ( is_uploaded_file( $file['tmp_name'] ) && in_array( $filetype['type'], self::ALLOWED_SCREENSHOT_TYPES, true ) && $file['size'] < 2097152 ) {
			return 1;
		}
		return 0;
	}

}
