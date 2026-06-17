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
		$path             = wp_parse_url( $url, PHP_URL_PATH );
		$basename         = strtolower( basename( (string) $path ) );
		$extension        = strtolower( pathinfo( $basename, PATHINFO_EXTENSION ) );
		$folder_path      = '';
		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp' );
		$video_extensions = array( 'mp4', 'm4v', 'webm', 'ogv', 'wmv', 'avi', 'mov', 'mpg', 'mpeg', '3gp', '3g2' );
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
	 * Defends against two bypass classes:
	 * 1. Query string disguise: strips the query before extracting the extension
	 *    so `evil.php?disguised=cat.jpg` is correctly identified as `.php`.
	 * 2. Multi-extension polyglots: rejects URLs whose basename contains ANY
	 *    dangerous extension segment (`evil.php.jpg` → rejected) regardless of
	 *    the final extension — defends against historical Apache configs that
	 *    execute any filename containing `.php` anywhere.
	 *
	 * @param string $url Absolute URL.
	 * @return bool True if the basename is safe and the final extension is in the media allowlist.
	 */
	public static function is_allowed_media_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}
		$path     = wp_parse_url( $url, PHP_URL_PATH );
		$basename = strtolower( basename( (string) $path ) );

		// Reject if ANY dot-separated segment of the basename is a dangerous
		// extension. This blocks multi-extension polyglots like `evil.php.jpg`
		// that execute on Apache hosts with `AddHandler ... .php`.
		$dangerous = array(
			'php',
			'phtml',
			'phar',
			'php3',
			'php4',
			'php5',
			'php7',
			'php8',
			'phps',
			'html',
			'htm',
			'xhtml',
			'htaccess',
			'htpasswd',
			'cgi',
			'pl',
			'py',
			'rb',
			'sh',
			'asp',
			'aspx',
			'jsp',
			'js',
			'mjs',
		);
		foreach ( explode( '.', $basename ) as $segment ) {
			if ( in_array( $segment, $dangerous, true ) ) {
				return false;
			}
		}

		$extension = pathinfo( $basename, PATHINFO_EXTENSION );
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
			'mpeg',
			'3gp',
			'3g2',
		);
		return in_array( $extension, $allowed, true );
	}

	/**
	 * Magic-byte verification of a downloaded media file against its URL extension.
	 *
	 * The downloaded bytes must match the format claimed by the URL extension —
	 * a `.jpg` URL whose body is SVG/PHP/anything-else is rejected so we never
	 * persist content that doesn't match its filename on disk (which would also
	 * confuse browsers that MIME-sniff regardless of Content-Type).
	 *
	 * libmagic-based MIME detection (via wp_check_filetype_and_ext) is
	 * unreliable here: WordPress Core's default mime registry omits SVG and
	 * uses inconsistent mappings for some video formats. Verifying magic bytes
	 * directly is version-independent and gives a stronger guarantee.
	 *
	 * Recognised magic per extension:
	 *  - jpg / jpeg → `\xff\xd8\xff`
	 *  - png        → `\x89PNG`
	 *  - gif        → `GIF8` (covers GIF87a and GIF89a)
	 *  - webp       → `RIFF....WEBP`
	 *  - svg        → `<svg` somewhere in the first 1024 bytes
	 *  - mp4 / m4v / mov / 3gp / 3g2 → `ftyp` at offset 4 (ISO BMFF)
	 *  - webm       → `\x1a\x45\xdf\xa3` (EBML)
	 *  - ogv        → `OggS`
	 *  - wmv        → ASF GUID `\x30\x26\xb2\x75\x8e\x66\xcf\x11`
	 *  - avi        → `RIFF....AVI `
	 *  - mpg / mpeg → `\x00\x00\x01\xb3` (sequence) or `\x00\x00\x01\xba` (system)
	 *
	 * @param string $tmp_file Local path to the downloaded file.
	 * @param string $url      The originating URL — its extension determines
	 *                         which magic-byte family the body must match.
	 * @return bool True if the file's leading bytes match the magic signature
	 *              expected for the URL's extension.
	 */
	public static function is_allowed_media_file( $tmp_file, $url ) {
		if ( ! is_string( $tmp_file ) || ! file_exists( $tmp_file ) ) {
			return false;
		}

		// Derive the extension from the URL's path (ignore query/fragment).
		$path      = (string) wp_parse_url( $url, PHP_URL_PATH );
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		// Read 1024 bytes — covers all fixed-position magic-byte formats and
		// gives enough room for SVG's `<svg` tag after an optional XML
		// declaration / BOM / whitespace.
		$fp = fopen( $tmp_file, 'rb' );
		if ( false === $fp ) {
			return false;
		}
		$head = fread( $fp, 1024 );
		fclose( $fp );

		if ( false === $head || strlen( $head ) < 4 ) {
			return false;
		}

		switch ( $extension ) {
			case 'jpg':
			case 'jpeg':
				return "\xff\xd8\xff" === substr( $head, 0, 3 );

			case 'png':
				return "\x89PNG" === substr( $head, 0, 4 );

			case 'gif':
				return 'GIF8' === substr( $head, 0, 4 );

			case 'webp':
				return strlen( $head ) >= 12
					&& 'RIFF' === substr( $head, 0, 4 )
					&& 'WEBP' === substr( $head, 8, 4 );

			case 'svg':
				return false !== stripos( $head, '<svg' );

			case 'mp4':
			case 'm4v':
			case 'mov':
			case '3gp':
			case '3g2':
				return strlen( $head ) >= 8 && 'ftyp' === substr( $head, 4, 4 );

			case 'webm':
				return "\x1a\x45\xdf\xa3" === substr( $head, 0, 4 );

			case 'ogv':
				return 'OggS' === substr( $head, 0, 4 );

			case 'wmv':
				return strlen( $head ) >= 8
					&& "\x30\x26\xb2\x75\x8e\x66\xcf\x11" === substr( $head, 0, 8 );

			case 'avi':
				return strlen( $head ) >= 12
					&& 'RIFF' === substr( $head, 0, 4 )
					&& 'AVI ' === substr( $head, 8, 4 );

			case 'mpg':
			case 'mpeg':
				$first_four = substr( $head, 0, 4 );
				return "\x00\x00\x01\xb3" === $first_four || "\x00\x00\x01\xba" === $first_four;

			default:
				return false;
		}
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
			// Derive the filename from the parsed URL path so query strings
			// (which can contain `/`) don't leak into the exported asset
			// reference — `basename($url)` on `cat.jpg?/evil.php` would
			// otherwise return `evil.php`.
			$filename = basename( (string) wp_parse_url( $absolute_url, PHP_URL_PATH ) );
			if ( is_child_theme() ) {
				return '<?php echo esc_url( get_stylesheet_directory_uri() ); ?>' . $folder_path . $filename;
			}
			return '<?php echo esc_url( get_template_directory_uri() ); ?>' . $folder_path . $filename;
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
				if ( isset( $parsed_url['host'], $parsed_url['port'] )
					&& 'localhost' === $parsed_url['host']
					&& '80' !== $parsed_url['port'] ) {
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

			$filename   = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			$media_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . self::get_media_folder_path_from_url( $url );
			if ( ! is_dir( $media_path ) ) {
				wp_mkdir_p( $media_path );
			}
			rename( $download_file, $media_path . $filename );
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
