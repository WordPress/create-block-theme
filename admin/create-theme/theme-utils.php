<?php

class Theme_Utils {
	public static function is_absolute_url( $url ) {
		return ! empty( $url ) && isset( parse_url( $url )['host'] );
	}

	public static function get_theme_slug( $new_theme_name ) {
		$theme = wp_get_theme();

		// If the source theme has a single-word slug but the new theme has a multi-word slug
		// then function will look like: function apple-bumpkin_support() and that won't work.
		// There are no issues if it is multi-word>single-word or multi>multi or single>single.
		// Due to the complexity of this situation (compared to the simplicity of the others)
		// this will enforce the usage of a singleword slug for those themes.

		$old_slug = $theme->get( 'TextDomain' );
		$new_slug = sanitize_title( $new_theme_name );
		$new_slug = preg_replace( '/\s+/', '', $new_slug ); // Remove spaces

		if ( ! str_contains( $old_slug, '-' ) && str_contains( $new_slug, '-' ) ) {
			return str_replace( '-', '', $new_slug );
		}

		return $new_slug;
	}

	public static function get_file_extension_from_url( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		return $extension;
	}

	public static function replace_namespace( $content, $new_slug, $new_name ) {
		$theme               = wp_get_theme();
		$old_slug            = $theme->get( 'TextDomain' );
		$new_slug_underscore = str_replace( '-', '_', $new_slug );
		$old_slug_underscore = str_replace( '-', '_', $old_slug );
		$old_name            = $theme->get( 'Name' );

		// Generate placeholders
		$placeholder_slug            = md5( $old_slug );
		$placeholder_slug_underscore = md5( $old_slug_underscore );
		$placeholder_name            = md5( $old_name );

		// Replace old values with placeholders
		$content = str_replace( $old_slug, $placeholder_slug, $content );
		$content = str_replace( $old_slug_underscore, $placeholder_slug_underscore, $content );
		$content = str_replace( $old_name, $placeholder_name, $content );

		// Replace placeholders with new values
		$content = str_replace( $placeholder_slug, $new_slug, $content );
		$content = str_replace( $placeholder_slug_underscore, $new_slug_underscore, $content );
		$content = str_replace( $placeholder_name, $new_name, $content );

		return $content;
	}

	public static function clone_theme_to_folder( $location, $new_slug, $new_name ) {

		// Get real path for our folder
		$theme_path = get_stylesheet_directory();

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $theme_path, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		// Add all the files (except for templates)
		foreach ( $files as $name => $file ) {

			// Get real and relative path for current file
			$file_path     = wp_normalize_path( $file );
			$relative_path = substr( $file_path, strlen( $theme_path ) + 1 );

			// Create Directories
			if ( $file->isDir() ) {
				wp_mkdir_p( $location . DIRECTORY_SEPARATOR . $files->getSubPathname() );
			}

			// If the path is for templates/parts ignore it
			if (
				strpos( $file_path, 'block-template-parts/' ) ||
				strpos( $file_path, 'block-templates/' ) ||
				strpos( $file_path, 'templates/' ) ||
				strpos( $file_path, 'parts/' )
			) {
				continue;
			}

			// Replace only text files, skip png's and other stuff.
			$contents               = file_get_contents( $file_path );
			$valid_extensions       = array( 'php', 'css', 'scss', 'js', 'txt', 'html' );
			$valid_extensions_regex = implode( '|', $valid_extensions );

			if ( preg_match( "/\.({$valid_extensions_regex})$/", $relative_path ) ) {
				// Replace namespace values if provided
				if ( $new_slug ) {
					$contents = self::replace_namespace( $contents, $new_slug, $new_name );
				}
			}

			// Add current file to target
			file_put_contents( $location . DIRECTORY_SEPARATOR . $relative_path, $contents );
		}
	}

	public static function get_readme_data() {
		$readme_location = get_template_directory() . '/readme.txt';

		if ( ! file_exists( $readme_location ) ) {
			throw new Exception( 'No readme file found' );
		}

		$readme_file_contents = file_get_contents( $readme_location );

		$readme_file_details = array();

		// Handle Recommended Plugins.
		$pattern = '/== Recommended Plugins ==\s+(.*?)(\s+==|$)/s';
		preg_match_all( $pattern, $readme_file_contents, $matches );
		$readme_file_details['recommendedPlugins'] = $matches[1][0] ?? '';

		return $readme_file_details;
	}


	/**
	 * Relocate the theme to a new folder and activate the newly relocated theme.
	 */
	public static function relocate_theme( $new_theme_subfolder ) {

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

	public static function is_valid_screenshot( $file ) {

		$allowed_screenshot_types = array(
			'png' => 'image/png',
		);
		$filetype                 = wp_check_filetype( $file['name'], $allowed_screenshot_types );
		if ( is_uploaded_file( $file['tmp_name'] ) && in_array( $filetype['type'], $allowed_screenshot_types, true ) && $file['size'] < 2097152 ) {
			return 1;
		}
		return 0;
	}

}
