<?php

class Theme_Utils {
	public static function is_absolute_url( $url ) {
		return ! empty( $url ) && isset( parse_url( $url )['host'] );
	}

	public static function get_theme_slug( $new_theme_name ) {

		// If the source theme has a single-word slug but the new theme has a multi-word slug
		// then function will look like: function apple-bumpkin_support() and that won't work.
		// There are no issues if it is multi-word>single-word or multi>multi or single>single.
		// Due to the complexity of this situation (compared to the simplicity of the others)
		// this will enforce the usage of a singleword slug for those themes.

		$old_slug = wp_get_theme()->get( 'TextDomain' );
		$new_slug = sanitize_title( $new_theme_name );
		$new_slug = preg_replace( '/\s+/', '', $new_slug ); // Remove spaces

		if ( ! str_contains( $old_slug, '-' ) && str_contains( $new_slug, '-' ) ) {
			return str_replace( '-', '', $new_slug );
		}

		return $new_slug;
	}

	public static function copy_theme_to_dest( $dest, $new_slug, $new_name ) {
		// Get real path for our folder
		$theme_path = get_stylesheet_directory();

		$is_zip = false;
		// Check if dest is a zip
		if ( $dest instanceof ZipArchive ) {
			$is_zip = true;
		}

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $theme_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		// Add all the files (except for templates)
		foreach ( $files as $name => $file ) {

			// Get real and relative path for current file
			$file_path = wp_normalize_path( $file );

			$relative_path = substr( $file_path, strlen( $theme_path ) + 1 );

			if ( ! $is_zip && is_dir( $file_path ) && ! file_exists( $dest . DIRECTORY_SEPARATOR . $relative_path ) ) {
				wp_mkdir_p( $dest . DIRECTORY_SEPARATOR . $relative_path );
				continue;
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
			$valid_extensions       = array( 'php', 'css', 'scss', 'js', 'txt', 'html' );
			$valid_extensions_regex = implode( '|', $valid_extensions );
			if ( ! preg_match( "/\.({$valid_extensions_regex})$/", $relative_path ) ) {
				if ( $is_zip ) {
					$dest->addFile( $file_path, $relative_path );
				} elseif ( ! is_dir( $file_path ) ) {
					file_put_contents( $dest . DIRECTORY_SEPARATOR . $relative_path, file_get_contents( $file_path ) );
				}
			} else {
				$contents = file_get_contents( $file_path );

				// Replace namespace values if provided
				if ( $new_slug ) {
					$contents = self::replace_namespace( $contents, $new_slug, $new_name );
				}

				if ( $is_zip ) {
					// Add current file to archive
					$dest->addFromString( $relative_path, $contents );
				} else {
					file_put_contents( $dest . DIRECTORY_SEPARATOR . $relative_path, $contents );
				}
			}
		}

		return $dest;
	}

	public static function get_file_extension_from_url( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		return $extension;
	}

	static function replace_namespace( $content, $new_slug, $new_name ) {
		$old_slug            = wp_get_theme()->get( 'TextDomain' );
		$new_slug_underscore = str_replace( '-', '_', $new_slug );
		$old_slug_underscore = str_replace( '-', '_', $old_slug );
		$old_name            = wp_get_theme()->get( 'Name' );

		$patterns_and_replacements = array(
			'/\b' . preg_quote( $old_slug, '/' ) . '\b/' => $new_slug,
			'/\b' . preg_quote( $old_slug_underscore, '/' ) . '\b/' => $new_slug_underscore,
			'/\b' . preg_quote( $old_name, '/' ) . '\b/' => $new_name,
		);

		foreach ( $patterns_and_replacements as $pattern => $replacement ) {
			$content = preg_replace( $pattern, $replacement, $content );
		}

		return $content;
	}

}
