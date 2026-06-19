<?php

require_once( __DIR__ . '/theme-media.php' );
require_once( __DIR__ . '/theme-templates.php' );
require_once( __DIR__ . '/theme-patterns.php' );
require_once( __DIR__ . '/cbt-zip-archive.php' );

class CBT_Theme_Zip {

	public static function create_zip( $filename, $theme_slug = null ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'Zip Export not supported.' );
		}

		if ( ! $theme_slug ) {
			$theme_slug = get_stylesheet();
		}

		$zip = new CBT_Zip_Archive( $theme_slug );
		$zip->open( $filename, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		return $zip;
	}

	public static function add_theme_json_to_zip( $zip, $theme_json ) {
		$zip->addFromStringToTheme(
			'theme.json',
			$theme_json
		);
		return $zip;
	}

	public static function add_activated_fonts_to_zip( $zip, $theme_json_string ) {

		$theme_json = json_decode( $theme_json_string, true );

		$font_families_to_copy     = CBT_Theme_Fonts::get_user_activated_fonts();
		$theme_font_asset_location = 'assets/fonts';
		$font_slugs_to_remove      = array();

		if ( ! $font_families_to_copy ) {
			return $theme_json_string;
		}

		foreach ( $font_families_to_copy as &$font_family ) {
			if ( ! isset( $font_family['fontFace'] ) ) {
				continue;
			}
			$font_slugs_to_remove[] = $font_family['slug'];
			foreach ( $font_family['fontFace'] as &$font_face ) {
				$font_face['src'] = (array) $font_face['src'];
				// Build a fresh srcs list so rejected sources (disallowed URL,
				// failed download, MIME mismatch) are dropped rather than
				// persisted into the exported theme.json.
				$kept_srcs = array();
				foreach ( $font_face['src'] as $font_src_index => $font_src ) {
					if ( is_string( $font_src ) && str_starts_with( $font_src, 'file:' ) ) {
						// Already a theme asset — keep as-is.
						$kept_srcs[] = $font_src;
						continue;
					}

					// Pre-download URL extension allowlist — see CBT_Theme_Fonts::is_allowed_font_url().
					if ( ! CBT_Theme_Fonts::is_allowed_font_url( $font_src ) ) {
						continue;
					}

					$font_src_path        = (string) wp_parse_url( $font_src, PHP_URL_PATH );
					$font_filename        = basename( $font_src_path );
					$font_pretty_filename = CBT_Theme_Fonts::make_filename_from_fontface( $font_face, $font_src_path, $font_src_index );
					$font_family_dir_name = sanitize_title( $font_family['name'] );
					$font_family_dir_path = path_join( $theme_font_asset_location, $font_family_dir_name );
					$font_face_path       = path_join( $font_family_dir_path, $font_pretty_filename );

					$font_dir = wp_get_font_dir();
					if ( str_contains( $font_src, $font_dir['url'] ) ) {
						$local_source = path_join( $font_dir['path'], $font_filename );
						// Magic-byte allowlist on the local source too — the
						// WP user-fonts directory is the input we don't trust:
						// anything that ended up there (via a separate flow,
						// or a polyglot) shouldn't be zipped verbatim into
						// the exported theme just because the URL extension
						// looked OK.
						if ( ! CBT_Theme_Fonts::is_allowed_font_file( $local_source, $font_src ) ) {
							continue;
						}
						$zip->addFileToTheme( $local_source, $font_face_path );
					} else {
						// otherwise download it from wherever it is hosted
						$tmp_file = download_url( $font_src );
						if ( is_wp_error( $tmp_file ) ) {
							continue;
						}
						// Post-download MIME allowlist.
						if ( ! CBT_Theme_Fonts::is_allowed_font_file( $tmp_file, $font_src ) ) {
							@unlink( $tmp_file );
							continue;
						}
						// Read the bytes into memory before unlinking.
						$bytes = file_get_contents( $tmp_file );
						@unlink( $tmp_file );
						if ( false === $bytes ) {
							continue;
						}
						$zip->addFromStringToTheme( $font_face_path, $bytes );
					}
					$kept_srcs[] = 'file:./assets/fonts/' . path_join( $font_family_dir_name, $font_pretty_filename );
				}
				$font_face['src'] = $kept_srcs;
			}
		}

		if ( ! isset( $theme_json['settings']['typography']['fontFamilies'] ) ) {
			$theme_json['settings']['typography']['fontFamilies'] = array();
		}

		// Remove user fonts that have already been added to the theme_json
		// otherwise they will be duplicated when we add them next
		foreach ( $theme_json['settings']['typography']['fontFamilies'] as $key => $theme_font_family ) {
			if ( in_array( $theme_font_family['slug'], $font_slugs_to_remove, true ) ) {
				unset( $theme_json['settings']['typography']['fontFamilies'][ $key ] );
			}
		}

		// Copy user fonts to theme
		$theme_json['settings']['typography']['fontFamilies'] = array_merge(
			$theme_json['settings']['typography']['fontFamilies'],
			$font_families_to_copy
		);

		return wp_json_encode( $theme_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	}

	public static function copy_theme_to_zip( $zip, $new_slug, $new_name ) {

		$theme    = wp_get_theme();
		$old_slug = $theme->get( 'TextDomain' );
		$old_name = $theme->get( 'Name' );

		// Get real path for our folder
		$theme_path = get_stylesheet_directory();

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $theme_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		// Add all the files (except for templates)
		foreach ( $files as $file ) {

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
						$contents = CBT_Theme_Utils::replace_namespace( $contents, $old_slug, $new_slug, $old_name, $new_name );
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
	public static function add_templates_to_zip( $zip, $export_type ) {

		$theme_templates  = CBT_Theme_Templates::get_theme_templates( $export_type );
		$template_folders = get_block_theme_folders();

		if ( $theme_templates->templates ) {
			$zip->addThemeDir( $template_folders['wp_template'] );
		}

		if ( $theme_templates->parts ) {
			$zip->addThemeDir( $template_folders['wp_template_part'] );
		}

		foreach ( $theme_templates->templates as $template ) {

			$template->media = CBT_Theme_Media::get_media_absolute_urls_from_template( $template );
			$validated_media = array();
			if ( ! empty( $template->media ) ) {
				$validated_media = self::add_media_to_zip( $zip, $template->media );
			}
			$template = CBT_Theme_Templates::prepare_template_for_export(
				$template,
				null,
				array(
					'localizeText'   => false,
					'removeNavRefs'  => true,
					'localizeImages' => true,
					'validatedMedia' => $validated_media,
				)
			);

			// Write the template content
			$zip->addFromStringToTheme(
				path_join( $template_folders['wp_template'], $template->slug . '.html' ),
				$template->content
			);

			// Write the pattern if it exists
			if ( isset( $template->pattern ) ) {
				$zip->addFromStringToTheme(
					'patterns/' . $template->slug . '.php',
					$template->pattern
				);
			}
		}

		foreach ( $theme_templates->parts as $template ) {
			$template->media = CBT_Theme_Media::get_media_absolute_urls_from_template( $template );
			$validated_media = array();
			if ( ! empty( $template->media ) ) {
				$validated_media = self::add_media_to_zip( $zip, $template->media );
			}
			$template = CBT_Theme_Templates::prepare_template_for_export(
				$template,
				null,
				array(
					'localizeText'   => false,
					'removeNavRefs'  => true,
					'localizeImages' => true,
					'validatedMedia' => $validated_media,
				)
			);

			// Write the template content
			$zip->addFromStringToTheme(
				path_join( $template_folders['wp_template_part'], $template->slug . '.html' ),
				$template->content
			);

			// Write the pattern if it exists
			if ( isset( $template->pattern ) ) {
				$zip->addFromStringToTheme(
					'patterns/' . $template->slug . '.php',
					$template->pattern
				);
			}
		}

		return $zip;
	}

	static function add_media_to_zip( $zip, $media ) {
		$media       = array_unique( $media );
		$added_media = array();
		foreach ( $media as $url ) {

			// Pre-download URL extension allowlist — see CBT_Theme_Media::is_allowed_media_url().
			if ( ! CBT_Theme_Media::is_allowed_media_url( $url ) ) {
				continue;
			}

			$folder_path   = CBT_Theme_Media::get_media_folder_path_from_url( $url );
			$download_file = download_url( $url );

			if ( is_wp_error( $download_file ) ) {
				//we're going to try again with a new URL
				//see, we might be running this in a docker container
				//and if that's the case let's try again on port 80
				$parsed_url = parse_url( $url );
				if ( isset( $parsed_url['host'], $parsed_url['port'] )
					&& 'localhost' === $parsed_url['host']
					&& '80' !== $parsed_url['port'] ) {
					$download_file = download_url( str_replace( 'localhost:' . $parsed_url['port'], 'localhost:80', $url ) );
				}
			}

			// If there was an error downloading the file, skip it.
			// TODO: Implement a warning if the file is missing
			if ( is_wp_error( $download_file ) ) {
				continue;
			}

			// Post-download MIME allowlist.
			if ( ! CBT_Theme_Media::is_allowed_media_file( $download_file, $url ) ) {
				@unlink( $download_file );
				continue;
			}

			// Read the bytes into memory before unlinking — ZipArchive defers
			// reading files added via addFile() until close() is called, so
			// removing the tmp file immediately would leave an empty entry
			// in the final archive.
			$file_name = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			$bytes     = file_get_contents( $download_file );
			@unlink( $download_file );
			if ( false === $bytes ) {
				continue;
			}
			if ( $zip->addFromStringToTheme( ltrim( $folder_path, '/' ) . $file_name, $bytes ) ) {
				$added_media[] = $url;
			}
		}

		return $added_media;

	}
}
