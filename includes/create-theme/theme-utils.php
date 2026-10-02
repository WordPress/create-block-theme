<?php

class CBT_Theme_Utils {
	/**
	 * Escape a value embedded in a generated PHP single-quoted string.
	 *
	 * @param string $value The value to escape.
	 * @return string The escaped value.
	 */
	public static function escape_php_single_quoted_string( $value ) {
		return addcslashes( (string) $value, "\\'" );
	}

	public static function is_absolute_url( $url ) {
		return ! empty( $url ) && isset( parse_url( $url )['host'] );
	}

	public static function get_theme_slug( $new_theme_name ) {
		return sanitize_title( $new_theme_name );
	}

	public static function get_file_extension_from_url( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		return $extension;
	}

	public static function replace_namespace( $content, $old_slug, $new_slug, $old_name, $new_name, $file_extension = '' ) {
		$new_slug_underscore = self::get_php_identifier( $new_slug ) . '_';
		$old_slug_underscore = str_replace( '-', '_', $old_slug ) . '_';

		if ( false !== strpos( $content, '<?php' ) ) {
			$content         = self::replace_localized_javascript_identifier( $content, $old_slug, $new_slug, $old_name );
			$updated_content = '';

			foreach ( token_get_all( $content ) as $token ) {
				if ( is_array( $token ) && self::is_php_identifier_token( $token[0] ) ) {
					$updated_content .= self::replace_php_identifier( $token[1], $old_slug, $new_slug, $old_name );
				} else {
					$token_content    = is_array( $token ) ? $token[1] : $token;
					$updated_content .= self::replace_namespace_value(
						$token_content,
						$old_slug,
						$new_slug,
						$old_name,
						$new_name,
						$old_slug_underscore,
						$new_slug_underscore
					);
				}
			}

			return $updated_content;
		}

		if ( 'js' === strtolower( $file_extension ) ) {
			return self::replace_javascript_namespace(
				$content,
				$old_slug,
				$new_slug,
				$old_name,
				$new_name,
				$old_slug_underscore,
				$new_slug_underscore
			);
		}

		return self::replace_namespace_value(
			$content,
			$old_slug,
			$new_slug,
			$old_name,
			$new_name,
			$old_slug_underscore,
			$new_slug_underscore
		);
	}

	private static function get_php_identifier( $slug ) {
		$identifier = preg_replace( '/[^a-z0-9_]/', '', str_replace( '-', '_', sanitize_key( $slug ) ) );

		if ( '' === $identifier ) {
			return 'theme';
		}

		if ( is_numeric( $identifier[0] ) ) {
			return 'theme_' . $identifier;
		}

		return $identifier;
	}

	private static function get_php_namespace( $slug ) {
		return implode( '_', array_map( 'ucfirst', explode( '_', self::get_php_identifier( $slug ) ) ) );
	}

	private static function get_javascript_identifier( $slug, $capitalize = false ) {
		$parts = preg_split( '/[^a-z0-9]+/', strtolower( $slug ), -1, PREG_SPLIT_NO_EMPTY );

		if ( empty( $parts ) ) {
			$parts = array( 'theme' );
		}

		if ( is_numeric( $parts[0][0] ) ) {
			array_unshift( $parts, 'theme' );
		}

		$identifier = array_shift( $parts );

		foreach ( $parts as $part ) {
			$identifier .= ucfirst( $part );
		}

		return $capitalize ? ucfirst( $identifier ) : $identifier;
	}

	private static function get_javascript_identifier_replacements( $old_slug, $new_slug, $old_name ) {
		$old_identifier        = self::get_javascript_identifier( $old_slug );
		$old_capitalized       = self::get_javascript_identifier( $old_slug, true );
		$old_name_identifier   = preg_replace( '/[^a-zA-Z0-9_$]/', '', $old_name );
		$new_identifier        = self::get_javascript_identifier( $new_slug );
		$new_capitalized       = self::get_javascript_identifier( $new_slug, true );
		$replacements          = array(
			$old_identifier      => $new_identifier,
			$old_capitalized     => $new_capitalized,
			$old_name_identifier => $new_capitalized,
		);
		$filtered_replacements = array_filter( $replacements, 'strlen', ARRAY_FILTER_USE_KEY );

		uksort(
			$filtered_replacements,
			function ( $first, $second ) {
				return strlen( $second ) - strlen( $first );
			}
		);

		return $filtered_replacements;
	}

	private static function replace_javascript_identifier( $identifier, $old_slug, $new_slug, $old_name, $preserve_plain_values = false ) {
		$replacements = self::get_javascript_identifier_replacements( $old_slug, $new_slug, $old_name );

		foreach ( $replacements as $old_identifier => $new_identifier ) {
			if ( 0 !== strpos( $identifier, $old_identifier ) ) {
				continue;
			}

			$suffix = substr( $identifier, strlen( $old_identifier ) );

			if (
				$preserve_plain_values &&
				(
					( '' === $suffix && ( $identifier === $old_slug || $identifier === $old_name ) ) ||
					( '' !== $suffix && ! preg_match( '/^[A-Z0-9]/', $suffix ) )
				)
			) {
				return $identifier;
			}

			return $new_identifier . $suffix;
		}

		return $identifier;
	}

	private static function replace_embedded_javascript_identifiers( $content, $old_slug, $new_slug, $old_name ) {
		return preg_replace_callback(
			'/[$A-Z_a-z][$0-9A-Z_a-z]*/',
			function ( $matches ) use ( $old_slug, $new_slug, $old_name ) {
				return self::replace_javascript_identifier( $matches[0], $old_slug, $new_slug, $old_name, true );
			},
			$content
		);
	}

	private static function replace_localized_javascript_identifier( $content, $old_slug, $new_slug, $old_name ) {
		$pattern = '/(wp_localize_script\s*\(\s*[^,]+,\s*)([\'\"])([$A-Z_a-z][$0-9A-Z_a-z]*)(\2)/';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $old_slug, $new_slug, $old_name ) {
				$identifier = self::replace_javascript_identifier( $matches[3], $old_slug, $new_slug, $old_name );

				return $matches[1] . $matches[2] . $identifier . $matches[4];
			},
			$content
		);
	}

	private static function replace_javascript_namespace( $content, $old_slug, $new_slug, $old_name, $new_name, $old_slug_underscore, $new_slug_underscore ) {
		$content = self::replace_embedded_javascript_identifiers( $content, $old_slug, $new_slug, $old_name );
		$pattern = <<<'REGEX'
~(?P<string>"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')
|(?P<template>`(?:\\.|[^`\\])*`)
|(?P<line_comment>//[^\r\n]*)
|(?P<block_comment>/\*.*?\*/)
|(?P<identifier>[$A-Z_a-z][$0-9A-Z_a-z]*)~sx
REGEX;

		return preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $old_slug, $new_slug, $old_name, $new_name, $old_slug_underscore, $new_slug_underscore ) {
				if ( ! empty( $matches['identifier'] ) ) {
					return self::replace_javascript_identifier( $matches[0], $old_slug, $new_slug, $old_name );
				}

				if ( ! empty( $matches['template'] ) ) {
					// Template literals may contain expressions, so leave them intact.
					return $matches[0];
				}

				return self::replace_namespace_value(
					$matches[0],
					$old_slug,
					$new_slug,
					$old_name,
					$new_name,
					$old_slug_underscore,
					$new_slug_underscore
				);
			},
			$content
		);
	}

	private static function is_php_identifier_token( $token_id ) {
		return T_STRING === $token_id ||
			T_VARIABLE === $token_id ||
			( defined( 'T_NAME_QUALIFIED' ) && constant( 'T_NAME_QUALIFIED' ) === $token_id ) ||
			( defined( 'T_NAME_FULLY_QUALIFIED' ) && constant( 'T_NAME_FULLY_QUALIFIED' ) === $token_id ) ||
			( defined( 'T_NAME_RELATIVE' ) && constant( 'T_NAME_RELATIVE' ) === $token_id );
	}

	private static function replace_php_identifier( $content, $old_slug, $new_slug, $old_name ) {
		$new_identifier = self::get_php_identifier( $new_slug );
		$new_namespace  = self::get_php_namespace( $new_slug );
		$old_identifier = self::get_php_identifier( $old_slug );
		$replacements   = array(
			$old_identifier . '_' => $new_identifier . '_',
			$old_identifier       => $new_identifier,
			$old_slug             => $new_identifier,
			$old_name             => $new_namespace,
		);

		return strtr( $content, array_filter( $replacements, 'strlen', ARRAY_FILTER_USE_KEY ) );
	}

	private static function replace_namespace_value( $content, $old_slug, $new_slug, $old_name, $new_name, $old_slug_underscore, $new_slug_underscore ) {

		// Generate placeholders
		$placeholder_slug            = md5( $old_slug );
		$placeholder_slug_underscore = md5( $old_slug_underscore );
		$placeholder_name            = md5( $old_name );

		// Replace old values with placeholders
		$content = str_replace( $old_slug_underscore, $placeholder_slug_underscore, $content );
		$content = str_replace( $old_slug, $placeholder_slug, $content );
		$content = str_replace( $old_name, $placeholder_name, $content );

		// Replace placeholders with new values
		$content = str_replace( $placeholder_slug_underscore, $new_slug_underscore, $content );
		$content = str_replace( $placeholder_slug, $new_slug, $content );
		$content = str_replace( $placeholder_name, $new_name, $content );

		return $content;
	}

	public static function clone_theme_to_folder( $location, $new_slug, $new_name ) {

		$theme    = wp_get_theme();
		$old_slug = $theme->get( 'TextDomain' );
		$old_name = $theme->get( 'Name' );

		// Get real path for our folder
		$theme_path = get_stylesheet_directory();

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $theme_path, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		// Add all the files (except for templates)
		foreach ( $files as $file ) {

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
					$contents = self::replace_namespace(
						$contents,
						$old_slug,
						$new_slug,
						$old_name,
						$new_name,
						pathinfo( $relative_path, PATHINFO_EXTENSION )
					);
				}
			}

			// Add current file to target
			file_put_contents( $location . DIRECTORY_SEPARATOR . $relative_path, $contents );
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

	public static function is_valid_screenshot_file( $file_path ) {
		return CBT_Theme_Utils::get_screenshot_file_extension( $file_path ) !== null;
	}

	public static function get_screenshot_file_extension( $file_path ) {
		$allowed_screenshot_types = array(
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'webp' => 'image/webp',
			'avif' => 'image/avif',
		);
		$filetype                 = wp_check_filetype( $file_path, $allowed_screenshot_types );
		if ( in_array( $filetype['type'], $allowed_screenshot_types, true ) ) {
			return $filetype['ext'];
		}
		return null;
	}

	public static function copy_screenshot( $file_path ) {

		$new_screeenshot_id = attachment_url_to_postid( $file_path );

		if ( ! $new_screeenshot_id ) {
			return new \WP_Error( 'screenshot_not_found', __( 'Screenshot not found', 'create-block-theme' ) );
		}

		$new_screenshot_metadata = wp_get_attachment_metadata( $new_screeenshot_id );
		$upload_dir              = wp_get_upload_dir();

		$new_screenshot_location = path_join( $upload_dir['basedir'], $new_screenshot_metadata['file'] );

		$new_screenshot_filetype = CBT_Theme_Utils::get_screenshot_file_extension( $file_path );
		$new_location            = path_join( get_stylesheet_directory(), 'screenshot.' . $new_screenshot_filetype );

		// copy and resize the image
		$image_editor = wp_get_image_editor( $new_screenshot_location );
		$image_editor->resize( 1200, 900, true );
		$image_editor->save( $new_location );

		return true;
	}

	public static function replace_screenshot( $new_screenshot_path ) {
		if ( ! CBT_Theme_Utils::is_valid_screenshot_file( $new_screenshot_path ) ) {
			return new \WP_Error( 'invalid_screenshot', __( 'Invalid screenshot file', 'create-block-theme' ) );
		}

		// Remove the old screenshot
		$old_screenshot = wp_get_theme()->get_screenshot( 'relative' );
		if ( $old_screenshot ) {
			unlink( path_join( get_stylesheet_directory(), $old_screenshot ) );
		}

		// Copy the new screenshot
		return CBT_Theme_Utils::copy_screenshot( $new_screenshot_path );
	}

	/**
	 * Get the current WordPress version.
	 *
	 * @return string The current WordPress in the format x.x (major.minor)
	 * Example: 6.5
	 */
	public static function get_current_wordpress_version() {
		$wp_version       = get_bloginfo( 'version' );
		$wp_version_parts = explode( '.', $wp_version );
		return $wp_version_parts[0] . '.' . $wp_version_parts[1];
	}
}
