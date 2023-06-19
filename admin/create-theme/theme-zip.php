<?php

require_once( __DIR__ . '/theme-media.php' );
require_once( __DIR__ . '/theme-blocks.php' );
require_once( __DIR__ . '/theme-templates.php' );
require_once( __DIR__ . '/theme-patterns.php' );
require_once( __DIR__ . '/cbt-zip-archive.php' );

class Theme_Zip {

	public static function create_zip( $filename ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'Zip Export not supported.' );
		}

		$theme_slug = get_stylesheet();
		if ( ! empty( $_POST['theme']['name'] ) ) {
			$theme_slug = Theme_Utils::get_theme_slug( $_POST['theme']['name'] );
		}

		$zip = new CbtZipArchive( $theme_slug );
		$zip->open( $filename, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		return $zip;
	}

	public static function add_theme_json_to_zip( $zip, $export_type ) {
		$zip->addFromStringToTheme(
			'theme.json',
			MY_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
		return $zip;
	}

	public static function copy_theme_to_zip( $zip, $new_slug, $new_name ) {

		// Get real path for our folder
		$theme_path = get_stylesheet_directory();

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $theme_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		// Add all the files (except for templates)
		foreach ( $files as $name => $file ) {

			// Skip directories (they would be added automatically)
			if ( ! $file->isDir() ) {

				// Get real and relative path for current file
				$file_path = wp_normalize_path( $file );

				// If the path is for templates/parts ignore it
				if (
					strpos( $file_path, 'block-template-parts/' ) ||
					strpos( $file_path, 'block-templates/' ) ||
					strpos( $file_path, 'templates/' ) ||
					strpos( $file_path, 'parts/' )
				) {
					continue;
				}

				$relative_path = substr( $file_path, strlen( $theme_path ) + 1 );

				// Replace only text files, skip png's and other stuff.
				$valid_extensions       = array( 'php', 'css', 'scss', 'js', 'txt', 'html' );
				$valid_extensions_regex = implode( '|', $valid_extensions );
				if ( ! preg_match( "/\.({$valid_extensions_regex})$/", $relative_path ) ) {
					$zip->addFileToTheme( $file_path, $relative_path );
				} else {
					$contents = file_get_contents( $file_path );

					// Replace namespace values if provided
					if ( $new_slug ) {
						$contents = Theme_Utils::replace_namespace( $contents, $new_slug, $new_name );
					}

					// Add current file to archive
					$zip->addFromStringToTheme( $relative_path, $contents );
				}
			}
		}

		return $zip;
	}

	/**
	 * Add block templates and parts to the zip.
	 *
	 * @since    0.0.2
	 * @param    object               $zip          The zip archive to add the templates to.
	 * @param    string               $export_type  Determine the templates that should be exported.
	 *                      current = templates from currently activated theme (but not a parent theme if there is one) as well as user edited templates
	 *                      user = only user edited templates
	 *                      all = all templates no matter what
	 */
	public static function add_templates_to_zip( $zip, $export_type, $new_slug ) {
		$theme_templates = Theme_Templates::get_theme_templates( $export_type );

		if ( $theme_templates->templates ) {
			$zip->addThemeDir( 'templates' );
		}

		if ( $theme_templates->parts ) {
			$zip->addThemeDir( 'parts' );
		}

		foreach ( $theme_templates->templates as $template ) {
			$template_data = Theme_Blocks::make_template_images_local( $template );
			$template_data = Theme_Templates::replace_template_namespace( $template_data, $new_slug );

			// If there are images in the template, add it as a pattern
			if ( count( $template_data->media ) > 0 ) {
				$pattern                 = Theme_Patterns::pattern_from_template( $template_data, $new_slug );
				$pattern_link_attributes = array(
					'slug' => $pattern['slug'],
				);
				$template_data->content  = Theme_Patterns::create_pattern_link( $pattern_link_attributes );

				// Add pattern to zip
				$zip->addFromStringToTheme(
					'patterns/' . $template_data->slug . '.php',
					$pattern['content']
				);

				// Add media assets to zip
				self::add_media_to_zip( $zip, $template_data->media );
			}

			// Add template to zip
			$zip->addFromStringToTheme(
				'templates/' . $template_data->slug . '.html',
				$template_data->content
			);

		}

		foreach ( $theme_templates->parts as $template_part ) {
			$template_data = Theme_Blocks::make_template_images_local( $template_part );
			$template_data = Theme_Templates::replace_template_namespace( $template_data, $new_slug );

			// If there are images in the template, add it as a pattern
			if ( count( $template_data->media ) > 0 ) {
				$pattern                 = Theme_Patterns::pattern_from_template( $template_data, $new_slug );
				$pattern_link_attributes = array(
					'slug' => $pattern['slug'],
				);
				$template_data->content  = Theme_Patterns::create_pattern_link( $pattern_link_attributes );

				// Add pattern to zip
				$zip->addFromStringToTheme(
					'patterns/' . $template_data->slug . '.php',
					$pattern['content']
				);

				// Add media assets to zip
				self::add_media_to_zip( $zip, $template_data->media );
			}

			// Add template to zip
			$zip->addFromStringToTheme(
				'parts/' . $template_data->slug . '.html',
				$template_data->content
			);
		}

		return $zip;
	}

	static function add_media_to_zip( $zip, $media ) {
		$media = array_unique( $media );
		foreach ( $media as $url ) {
			$folder_path   = Theme_Media::get_media_folder_path_from_url( $url );
			$download_file = download_url( $url );
			// If there was an error downloading the file, skip it.
			// TODO: Implement a warning if the file is missing
			if ( ! is_wp_error( $download_file ) ) {
				$content_array  = file( $download_file );
				$file_as_string = implode( '', $content_array );
				$zip->addFromStringToTheme( $folder_path . basename( $url ), $file_as_string );
			}
		}
	}

}
