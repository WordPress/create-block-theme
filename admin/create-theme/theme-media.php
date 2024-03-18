<?php

require_once( __DIR__ . '/theme-utils.php' );

class Theme_Media {
	public static function get_media_folder_path_from_url( $url ) {
		$extension        = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		$folder_path      = '';
		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp' );
		$video_extensions = array( 'mp4', 'm4v', 'webm', 'ogv', 'wmv', 'avi', 'mov', 'mpg', 'ogv', '3gp', '3g2' );
		if ( in_array( $extension, $image_extensions, true ) ) {
			$folder_path = '/assets/images/';
		} elseif ( in_array( $extension, $video_extensions, true ) ) {
			$folder_path = '/assets/videos/';
		} else {
			$folder_path = '/assets/';
		}
		return $folder_path;
	}

	public static function get_media_absolute_urls_from_blocks( $flatten_blocks ) {
		$media = array();

		foreach ( $flatten_blocks as $block ) {
			// Gets the absolute URLs of img in these blocks
			if (
				'core/image' === $block['blockName'] ||
				'core/video' === $block['blockName'] ||
				'core/cover' === $block['blockName'] ||
				'core/media-text' === $block['blockName']
			) {
				$html = new WP_HTML_Tag_Processor( $block['innerHTML'] );
				while ( $html->next_tag( 'img' ) ) {
					$url = $html->get_attribute( 'src' );
					if ( Theme_Utils::is_absolute_url( $url ) ) {
						$media[] = $url;
					}
				}
				$html = new WP_HTML_Tag_Processor( $html->__toString() );
				while ( $html->next_tag( 'video' ) ) {
					$url = $html->get_attribute( 'src' );
					if ( Theme_Utils::is_absolute_url( $url ) ) {
						$media[] = $url;
					}
					$poster_url = $html->get_attribute( 'poster' );
					if ( Theme_Utils::is_absolute_url( $poster_url ) ) {
						$media[] = $poster_url;
					}
				}
			}

			// Gets the absolute URLs of background images in these blocks
			if ( 'core/cover' === $block['blockName'] ) {
				$html = new WP_HTML_Tag_Processor( $block['innerHTML'] );
				while ( $html->next_tag( 'div' ) ) {
					$style = $html->get_attribute( 'style' );
					if ( $style ) {
						$matches = array();
						preg_match( '/background-image: url\((.*)\)/', $style, $matches );
						if ( isset( $matches[1] ) ) {
							$url = $matches[1];
							if ( Theme_Utils::is_absolute_url( $url ) ) {
								$media[] = $url;
							}
						}
					}
				}
			}
		}

		return $media;
	}

	public static function make_relative_media_url( $absolute_url ) {
		if ( ! empty( $absolute_url ) && Theme_Utils::is_absolute_url( $absolute_url ) ) {
			$folder_path = self::get_media_folder_path_from_url( $absolute_url );
			return '<?php echo esc_url( get_stylesheet_directory_uri() ); ?>' . $folder_path . basename( $absolute_url );
		}
		return $absolute_url;
	}

	public static function add_media_to_local( $media ) {
		foreach ( $media as $url ) {
			$download_file = download_url( $url );
			// TODO: implement a warning if the file is missing
			if ( ! is_wp_error( $download_file ) ) {
				$media_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . self::get_media_folder_path_from_url( $url );
				if ( ! is_dir( $media_path ) ) {
					wp_mkdir_p( $media_path );
				}
				rename( $download_file, $media_path . basename( $url ) );
			}
		}
	}
}
