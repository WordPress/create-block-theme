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

	public static function get_file_extension_from_url( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		return $extension;
	}

	public static function replace_namespace( $content, $new_slug, $new_name ) {
		$old_slug            = wp_get_theme()->get( 'TextDomain' );
		$new_slug_underscore = str_replace( '-', '_', $new_slug );
		$old_slug_underscore = str_replace( '-', '_', $old_slug );
		$old_name            = wp_get_theme()->get( 'Name' );

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

	public static function add_templates_to_folder( $location, $export_type, $new_slug ) {
		$theme_templates = Theme_Templates::get_theme_templates( $export_type );

		if ( $theme_templates->templates ) {
			wp_mkdir_p( $location . DIRECTORY_SEPARATOR . 'templates' );
		}

		if ( $theme_templates->parts ) {
			wp_mkdir_p( $location . DIRECTORY_SEPARATOR . 'parts' );
		}

		foreach ( $theme_templates->templates as $template ) {
			$template = Theme_Blocks::make_template_images_local( $template );
			$template = Theme_Templates::replace_template_namespace( $template, $new_slug );

			// If there are images in the template, add it as a pattern
			if ( count( $template->media ) > 0 ) {
				$pattern                 = Theme_Patterns::pattern_from_template( $template, $new_slug );
				$pattern_link_attributes = array(
					'slug' => $pattern['slug'],
				);
				$template->content       = Theme_Patterns::create_pattern_link( $pattern_link_attributes );

				// Add pattern to folder
				$pattern_path = $location . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . $template->slug . '.php';
				file_put_contents( $pattern_path, $pattern['content'] );

				// Add media assets to folder
				self::add_media_to_folder( $location, $template->media );
			}

			// Add template to folder
			$template_path = $location . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template->slug . '.html';
			file_put_contents( $template_path, $template->content );

		}

		foreach ( $theme_templates->parts as $template_part ) {
			$template_part = Theme_Blocks::make_template_images_local( $template_part );
			$template_part = Theme_Templates::replace_template_namespace( $template_part, $new_slug );

			// If there are images in the template, add it as a pattern
			if ( count( $template_part->media ) > 0 ) {
				$pattern                 = Theme_Patterns::pattern_from_template( $template_part, $new_slug );
				$pattern_link_attributes = array(
					'slug' => $pattern['slug'],
				);
				$template_part->content  = Theme_Patterns::create_pattern_link( $pattern_link_attributes );

				// Add pattern to folder
				$pattern_path = $location . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . $template_part->slug . '.php';
				file_put_contents( $pattern_path, $pattern['content'] );

				// Add media assets to folder
				self::add_media_to_folder( $location, $template_part->media );
			}

			// Add template part to folder
			$template_path = $location . DIRECTORY_SEPARATOR . 'parts' . DIRECTORY_SEPARATOR . $template_part->slug . '.html';
			file_put_contents( $template_path, $template_part->content );
		}
	}

	public static function add_media_to_folder( $location, $media ) {
		$media = array_unique( $media );
		foreach ( $media as $url ) {
			$folder_path   = Theme_Media::get_media_folder_path_from_url( $url );
			$download_file = download_url( $url );
			// If there was an error downloading the file, skip it.
			// TODO: Implement a warning if the file is missing
			if ( ! is_wp_error( $download_file ) ) {
				$content_array  = file( $download_file );
				$file_as_string = implode( '', $content_array );
				file_put_contents( $location . DIRECTORY_SEPARATOR . $folder_path . basename( $url ), $file_as_string );
			}
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

}
