<?php
/**
 * Theme Fonts
 *
 * @package Create_Block_Theme
 */
class CBT_Theme_Fonts {

	/**
	 * Make the font face theme src urls absolute.
	 *
	 * It replaces the 'file:./' prefix with the theme directory uri.
	 *
	 * Example: 'file:./assets/fonts/my-font.ttf' -> 'http://example.com/wp-content/themes/my-theme/assets/fonts/my-font.ttf'
	 * Example: [ 'https://example.com/assets/fonts/my-font.ttf' ] -> [ 'https://example.com/assets/fonts/my-font.ttf' ]
	 *
	 * @param array|string $src
	 * @return array|string
	 */
	private static function make_theme_font_src_absolute( $src ) {
		$make_absolute = function( $url ) {
			if ( str_starts_with( $url, 'file:./' ) ) {
				return str_replace( 'file:./', get_stylesheet_directory_uri() . '/', $url );
			}
			return $url;
		};

		if ( is_array( $src ) ) {
			return array_map( $make_absolute, $src );
		}

		return $make_absolute( $src );
	}

	/**
	 * Get all fonts from the theme.json data + all the style variations.
	 *
	 * @return array
	 */
	public static function get_all_fonts() {
		$font_families = array();
		$theme         = CBT_Theme_JSON_Resolver::get_merged_data();
		$settings      = $theme->get_settings();

		if ( isset( $settings['typography']['fontFamilies']['theme'] ) ) {
			$font_families = array_merge( $font_families, $settings['typography']['fontFamilies']['theme'] );
		}

		if ( isset( $settings['typography']['fontFamilies']['custom'] ) ) {
			$font_families = array_merge( $font_families, $settings['typography']['fontFamilies']['custom'] );
		}

		$variations = CBT_Theme_JSON_Resolver::get_style_variations();

		foreach ( $variations as $variation ) {
			if ( isset( $variation['settings']['typography']['fontFamilies']['theme'] ) ) {
				$font_families = array_merge( $font_families, $variation['settings']['typography']['fontFamilies']['theme'] );
			}
		}

		// Iterates through the font families and makes the urls absolute to use in the frontend code.
		foreach ( $font_families as &$font_family ) {
			if ( isset( $font_family['fontFace'] ) ) {
				foreach ( $font_family['fontFace'] as &$font_face ) {
					$font_face['src'] = self::make_theme_font_src_absolute( $font_face['src'] );
				}
			}
		}

		return $font_families;
	}

	/**
	 * Copy any ACTIVATED fonts from USER configuration to THEME configuration including any font face assets.
	 * Remove any DEACTIVATED fonts from the THEME configuration.
	 */
	public static function persist_font_settings() {
		self::remove_deactivated_fonts_from_theme();
		self::copy_activated_fonts_to_theme();
	}

	public static function get_user_activated_fonts() {
		$user_settings = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		return $user_settings['typography']['fontFamilies']['custom'] ?? null;
	}

	/**
	 * Make a pretty filename from a font face.
	 *
	 * The filename is based on the font family name, weight, style, unicode range and the source index.
	 * Example:
	 *     $font_face = [ 'fontFamily' => 'Open Sans', 'fontWeight' => '400', 'fontStyle' => 'normal' ]
	 *     $src = 'https://example.com/assets/fonts/open-sans-regular.ttf'
	 *     $src_index = 0
	 *     Returns: 'open-sans-400-normal.ttf'
	 *
	 * @param array  $font_face
	 * @param string $src
	 * @param int    $src_index
	 * @return string
	 */
	public static function make_filename_from_fontface( $font_face, $src, $src_index = 0 ) {
		$font_extension = pathinfo( $src, PATHINFO_EXTENSION );
		$font_filename  = sanitize_title( $font_face['fontFamily'] )
			. ( isset( $font_face['fontWeight'] ) ? '-' . sanitize_title( $font_face['fontWeight'] ) : '' )
			. ( isset( $font_face['fontStyle'] ) ? '-' . sanitize_title( $font_face['fontStyle'] ) : '' )
			. ( isset( $font_face['unicodeRange'] ) ? '-' . sanitize_title( $font_face['unicodeRange'] ) : '' )
			. ( 0 !== $src_index ? '-' . $src_index : '' )
			. '.'
			. $font_extension;

		return $font_filename;
	}

	/*
	 * Copy the font assets to the theme.
	 *
	 * @param array $font_families The font families to copy.
	 * @return array $font_families The font families with the font face src updated to the theme font asset location.
	 */
	public static function copy_font_assets_to_theme( $font_families ) {
		$theme_font_asset_location = path_join( get_stylesheet_directory(), 'assets/fonts/' );
		// Create the font asset directory if it does not exist.
		wp_mkdir_p( $theme_font_asset_location );

		foreach ( $font_families as &$font_family ) {
			if ( ! isset( $font_family['fontFace'] ) ) {
				continue;
			}

			$font_family_dir_name = sanitize_title( $font_family['name'] );
			$font_family_dir_path = path_join( $theme_font_asset_location, $font_family_dir_name );
			// Crete a font family specific directory if it does not exist.
			wp_mkdir_p( $font_family_dir_path );

			foreach ( $font_family['fontFace'] as &$font_face ) {
				// src can be a string or an array
				// if it is a string, cast it to an array
				$font_face['src'] = (array) $font_face['src'];
				foreach ( $font_face['src'] as $font_src_index => &$font_src ) {
					if ( str_starts_with( $font_src, 'file:' ) ) {
						// If the font source starts with 'file:' then it's already a theme asset.
						continue;
					}
					$font_filename        = basename( $font_src );
					$font_pretty_filename = self::make_filename_from_fontface( $font_face, $font_src, $font_src_index );
					$font_face_path       = path_join( $font_family_dir_path, $font_pretty_filename );
					$font_dir             = wp_get_font_dir();
					if ( str_contains( $font_src, $font_dir['url'] ) ) {
						// If the file is hosted on this server then copy it to the theme
						copy( path_join( $font_dir['path'], $font_filename ), $font_face_path );
					} else {
						// otherwise download it from wherever it is hosted
						$tmp_file = download_url( $font_src );
						copy( $tmp_file, $font_face_path );
						unlink( $tmp_file );
					}
					$font_face_family_path               = path_join( $font_family_dir_name, $font_pretty_filename );
					$font_face['src'][ $font_src_index ] = path_join( 'file:./assets/fonts/', $font_face_family_path );
				}
			}
		}

		return $font_families;
	}

	public static function copy_activated_fonts_to_theme() {
		$font_families_to_copy = self::get_user_activated_fonts();

		if ( is_null( $font_families_to_copy ) ) {
			return;
		}

		$theme_json           = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$copied_font_families = self::copy_font_assets_to_theme( $font_families_to_copy );

		$theme_json['settings']['typography']['fontFamilies'] = array_merge(
			$theme_json['settings']['typography']['fontFamilies'] ?? array(),
			$copied_font_families
		);

		$user_settings = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		unset( $user_settings['typography']['fontFamilies']['custom'] );
		if ( empty( $user_settings['typography']['fontFamilies'] ) ) {
			unset( $user_settings['typography']['fontFamilies'] );
		}
		if ( empty( $user_settings['typography'] ) ) {
			unset( $user_settings['typography'] );
		}

		CBT_Theme_JSON_Resolver::write_user_settings( $user_settings );
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );
	}

	/**
	 * Remove font face assets from the theme that are not in the user configuration.
	 *
	 * @param array $font_families_to_not_remove
	 * @param array $theme_font_families
	 */
	private static function remove_deactivated_font_assets( $font_families_to_not_remove, $theme_font_families ) {
		/* Bail if there are no theme font families, which can happen
		 * if the theme.json file, missing, or if the theme is a child theme, in
		 * which case the font families are inherited from the parent theme.
			*/
		if ( is_null( $theme_font_families ) ) {
			return;
		}

		$theme_font_asset_location = get_stylesheet_directory() . '/assets/fonts/';
		$font_families_to_remove   = array_filter(
			$theme_font_families,
			function( $theme_font_family ) use ( $font_families_to_not_remove ) {
				return ! in_array( $theme_font_family['slug'], array_column( $font_families_to_not_remove, 'slug' ), true );
			}
		);

		foreach ( $font_families_to_remove as $font_family ) {
			if ( isset( $font_family['fontFace'] ) ) {
				foreach ( $font_family['fontFace'] as $font_face ) {
					// src can be a string or an array
					// if it is a string, cast it to an array
					$srcs = (array) $font_face['src'];
					foreach ( $srcs as $font_src ) {
						$font_filename = basename( $font_src );
						$file_path     = $theme_font_asset_location . $font_filename;
						if ( file_exists( $file_path ) ) {
							unlink( $file_path );
						}
					}
				}
			}
		}
	}

	/**
	 * Remove any deactivated fonts from the theme configuration.
	 * This includes removing the font face assets from the theme,
	 * but does not remove the font face assets from the user configuration.
	 *
	 * This is because the user may have deactivated a font, but still want to use it in the future.
	 */
	public static function remove_deactivated_fonts_from_theme() {
		$user_settings = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_json    = CBT_Theme_JSON_Resolver::get_theme_file_contents();

		if ( ! isset( $user_settings['typography']['fontFamilies']['theme'] ) ) {
			return;
		}

		$font_families_to_not_remove = $user_settings['typography']['fontFamilies']['theme'];
		$theme_font_families         = $theme_json['settings']['typography']['fontFamilies'] ?? null;

		self::remove_deactivated_font_assets( $font_families_to_not_remove, $theme_font_families );

		if ( ! is_null( $theme_font_families ) ) {
			$theme_json['settings']['typography']['fontFamilies'] = array_filter(
				$theme_font_families,
				function( $theme_font_family ) use ( $font_families_to_not_remove ) {
					return in_array( $theme_font_family['slug'], array_column( $font_families_to_not_remove, 'slug' ), true );
				}
			);
		}

		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		unset( $user_settings['typography']['fontFamilies']['theme'] );
		if ( empty( $user_settings['typography']['fontFamilies'] ) ) {
			unset( $user_settings['typography']['fontFamilies'] );
		}
		if ( empty( $user_settings['typography'] ) ) {
			unset( $user_settings['typography'] );
		}

		CBT_Theme_JSON_Resolver::write_user_settings( $user_settings );
	}
}
