<?php

require_once( __DIR__ . '/theme-utils.php' );

class CBT_Theme_Media {

	/**
	 * Map a media URL to its target folder under the theme.
	 *
	 * Note: as of the validation added in add_media_to_local(), the `else`
	 * branch (unknown extension → `/assets/`) is unreachable from the
	 * download path because `is_allowed_media_url()` rejects unknown
	 * extensions before this function is consulted. The branch remains
	 * here because `make_relative_media_url()` also calls this function
	 * to rewrite URLs of already-local media in exported templates.
	 *
	 * @param string $url Media URL.
	 * @return string Relative folder path starting with `/assets/`.
	 */
	public static function get_media_folder_path_from_url( $url ) {
		$extension        = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		$folder_path      = '';
		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp' );
		$video_extensions = array( 'mp4', 'm4v', 'webm', 'ogv', 'wmv', 'avi', 'mov', 'mpg', 'ogv', '3gp', '3g2' );
		if ( in_array( $extension, $image_extensions, true ) ) {
			$folder_path = apply_filters( 'cbt_media_folder_path_images', '/assets/images/' );
		} elseif ( in_array( $extension, $video_extensions, true ) ) {
			$folder_path = apply_filters( 'cbt_media_folder_path_videos', '/assets/videos/' );
		} else {
			$folder_path = apply_filters( 'cbt_media_folder_path_others', '/assets/' );
		}
		return $folder_path;
	}

	/**
	 * Allowlist check on the URL's path extension before we attempt to download it.
	 *
	 * Strips any query string before extracting the extension so that
	 * `evil.php?disguised=cat.jpg` is correctly identified as `.php`.
	 *
	 * @param string $url Absolute URL.
	 * @return bool True if the extension is in the media allowlist.
	 */
	public static function is_allowed_media_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}
		$path      = wp_parse_url( $url, PHP_URL_PATH );
		$extension = strtolower( pathinfo( (string) $path, PATHINFO_EXTENSION ) );
		$allowed   = array(
			// images
			'jpg',
			'jpeg',
			'png',
			'gif',
			'svg',
			'webp',
			// videos
			'mp4',
			'm4v',
			'webm',
			'ogv',
			'wmv',
			'avi',
			'mov',
			'mpg',
			'3gp',
			'3g2',
		);
		return in_array( $extension, $allowed, true );
	}

	/**
	 * Post-download MIME-type allowlist for downloaded media bodies.
	 *
	 * Uses wp_check_filetype_and_ext() to detect the real type of the bytes
	 * on disk so that, e.g., a `.jpg`-named file whose body is PHP source
	 * is rejected before we move it into the theme directory.
	 *
	 * @param string $tmp_file Local path to the downloaded file.
	 * @param string $url      The originating URL (used to hint the basename).
	 * @return bool True if the file's detected type is in the allowlist.
	 */
	public static function is_allowed_media_file( $tmp_file, $url ) {
		if ( ! is_string( $tmp_file ) || ! file_exists( $tmp_file ) ) {
			return false;
		}
		$allowed = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/svg+xml',
			'image/webp',
			'video/mp4',
			'video/webm',
			'video/ogg',
			'video/x-msvideo',
			'video/quicktime',
			'video/mpeg',
			'video/3gpp',
			'video/3gpp2',
		);
		$check   = wp_check_filetype_and_ext( $tmp_file, basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
		$type    = isset( $check['type'] ) ? $check['type'] : false;
		return is_string( $type ) && in_array( $type, $allowed, true );
	}

	/**
	 * Get the absolute URLs of all media files for a template
	 */
	public static function get_media_absolute_urls_from_template( $template ) {

		$template_blocks = parse_blocks( $template->content );
		$blocks          = _flatten_blocks( $template_blocks );

		$media = array();

		foreach ( $blocks as $block ) {
			// Gets the absolute URLs of img in these blocks
			if ( 'core/image' === $block['blockName'] ||
				'core/video' === $block['blockName'] ||
				'core/cover' === $block['blockName'] ||
				'core/media-text' === $block['blockName']
			) {
				$html = new WP_HTML_Tag_Processor( $block['innerHTML'] );
				while ( $html->next_tag( 'img' ) ) {
					$url = $html->get_attribute( 'src' );
					if ( CBT_Theme_Utils::is_absolute_url( $url ) ) {
						$media[] = $url;
					}
				}
				$html = new WP_HTML_Tag_Processor( $html->__toString() );
				while ( $html->next_tag( 'video' ) ) {
					$url = $html->get_attribute( 'src' );
					if ( CBT_Theme_Utils::is_absolute_url( $url ) ) {
						$media[] = $url;
					}
					$poster_url = $html->get_attribute( 'poster' );
					if ( CBT_Theme_Utils::is_absolute_url( $poster_url ) ) {
						$media[] = $poster_url;
					}
				}
			}

			// Gets the absolute URLs of background images in Cover blocks
			if ( 'core/cover' === $block['blockName'] ) {
				// 1) Parse inline styles for background-image
				$html = new WP_HTML_Tag_Processor( $block['innerHTML'] );
				while ( $html->next_tag( 'div' ) ) {
					$style = $html->get_attribute( 'style' );
					if ( $style ) {
						$matches = array();
						// Match url(...) with or without quotes
						preg_match( '/background-image:\s*url\(("|\')?(.*?)(\1)\)/i', $style, $matches );
						if ( isset( $matches[1] ) ) {
							// In quoted match, the URL is in group 2; otherwise group 2 also holds the URL
							$url = isset( $matches[2] ) ? $matches[2] : $matches[1];
							if ( CBT_Theme_Utils::is_absolute_url( $url ) ) {
								$media[] = $url;
							}
						}
					}
				}

				// 2) Handle repeated background set via block attributes
				if ( isset( $block['attrs']['style']['background']['backgroundImage']['url'] ) ) {
					$cover_bg_url = $block['attrs']['style']['background']['backgroundImage']['url'];
					if ( CBT_Theme_Utils::is_absolute_url( $cover_bg_url ) ) {
						$media[] = $cover_bg_url;
					}
				}
			}

			// Gets the absolute URLs of background images in Group blocks
			if ( 'core/group' === $block['blockName'] ) {
				if ( isset( $block['attrs']['style']['background']['backgroundImage']['url'] ) && CBT_Theme_Utils::is_absolute_url( $block['attrs']['style']['background']['backgroundImage']['url'] ) ) {
					$media[] = $block['attrs']['style']['background']['backgroundImage']['url'];
				}
			}
		}

		return $media;
	}

	/**
	 * Create a relative URL based on the absolute URL of a media file
	 *
	 * @param string $absolute_url
	 * @return string $relative_url
	 */
	public static function make_relative_media_url( $absolute_url ) {
		if ( ! empty( $absolute_url ) && CBT_Theme_Utils::is_absolute_url( $absolute_url ) ) {
			$folder_path = self::get_media_folder_path_from_url( $absolute_url );
			if ( is_child_theme() ) {
				return '<?php echo esc_url( get_stylesheet_directory_uri() ); ?>' . $folder_path . basename( $absolute_url );
			}
			return '<?php echo esc_url( get_template_directory_uri() ); ?>' . $folder_path . basename( $absolute_url );
		}
		return $absolute_url;
	}

	/**
	 * Add media files to the local theme
	 */
	public static function add_media_to_local( $media ) {

		foreach ( $media as $url ) {

			// Pre-download URL extension allowlist — see is_allowed_media_url().
			if ( ! self::is_allowed_media_url( $url ) ) {
				continue;
			}

			$download_file = download_url( $url );

			if ( is_wp_error( $download_file ) ) {
				//we're going to try again with a new URL
				//see, we might be running this in a docker container
				//and if that's the case let's try again on port 80
				$parsed_url = parse_url( $url );
				if ( 'localhost' === $parsed_url['host'] && '80' !== $parsed_url['port'] ) {
					$download_file = download_url( str_replace( 'localhost:' . $parsed_url['port'], 'localhost:80', $url ) );
				}
			}

			// TODO: implement a warning if the file is missing
			if ( is_wp_error( $download_file ) ) {
				continue;
			}

			// Post-download MIME allowlist — defence-in-depth against
			// content/extension mismatch.
			if ( ! self::is_allowed_media_file( $download_file, $url ) ) {
				@unlink( $download_file );
				continue;
			}

			$media_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . self::get_media_folder_path_from_url( $url );
			if ( ! is_dir( $media_path ) ) {
				wp_mkdir_p( $media_path );
			}
			rename( $download_file, $media_path . basename( $url ) );
		}

	}


	/**
	 * Replace the absolute URLs of media in a template with relative URLs
	 */
	public static function make_template_images_local( $template ) {

		$template->media = self::get_media_absolute_urls_from_template( $template );

		// Replace the absolute URLs with relative URLs in the templates
		foreach ( $template->media as $media_url ) {
			$local_media_url   = CBT_Theme_Media::make_relative_media_url( $media_url );
			$template->content = str_replace( $media_url, $local_media_url, $template->content );
		}

		return $template;
	}
}
