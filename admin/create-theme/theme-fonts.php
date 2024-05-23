<?php


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
		$make_absolute = function ( $url ) {
			if ( str_starts_with( $url, 'file:./' ) ) {
				return str_replace( 'file:./', get_stylesheet_directory_uri() . '/', $url );
			}
			return $url;
		};

		if ( is_array( $src ) ) {
			foreach ( $src as &$url ) {
				$url = $make_absolute( $url );
			}
		} else {
			$src = $make_absolute( $src );
		}

		return $src;
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
					$font_face['src'] = CBT_Theme_Fonts::make_theme_font_src_absolute( $font_face['src'] );
				}
			}
		}

		return $font_families;
	}

	/**
	 * Copy any ACTIVATED fonts from USER configuration to THEME configuration including any font face assets.
	 * Remove any DEACTIVATED fronts from the THEME configuration.
	 */
	public static function persist_font_settings() {
		static::remove_deactivated_fonts_from_theme();
		static::copy_activated_fonts_to_theme();
	}

	public static function get_user_activated_fonts() {
		$user_settings = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		if ( ! isset( $user_settings['typography']['fontFamilies']['custom'] ) ) {
			return null;
		}

		return $user_settings['typography']['fontFamilies']['custom'];
	}

	public static function copy_activated_fonts_to_theme() {
		$user_settings = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		if ( ! isset( $user_settings['typography']['fontFamilies']['custom'] ) ) {
			return null;
		}

		$font_families_to_copy = $user_settings['typography']['fontFamilies']['custom'];

		// If there are no custom fonts, bounce out
		if ( is_null( $font_families_to_copy ) ) {
			return;
		}

		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();

		// copy font face assets to theme and change the src to the new location
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$theme_font_asset_location = get_stylesheet_directory() . '/assets/fonts/';
		if ( ! file_exists( $theme_font_asset_location ) ) {
				mkdir( $theme_font_asset_location, 0777, true );
		}
		foreach ( $font_families_to_copy as &$font_family ) {
			if ( ! isset( $font_family['fontFace'] ) ) {
				continue;
			}
			foreach ( $font_family['fontFace'] as &$font_face ) {
				$font_filename = basename( $font_face['src'] );
				$font_dir      = wp_get_font_dir();
				if ( str_contains( $font_face['src'], $font_dir['url'] ) ) {
					// If the file is hosted on this server then copy it to the theme
					copy( $font_dir['path'] . '/' . $font_filename, $theme_font_asset_location . '/' . $font_filename );
				} else {
					// otherwise download it from wherever it is hosted
					$tmp_file = download_url( $font_face['src'] );
					copy( $tmp_file, $theme_font_asset_location . $font_filename );
					unlink( $tmp_file );
				}

				$font_face['src'] = 'file:./assets/fonts/' . $font_filename;
			}
		}

		// Copy user fonts to theme
		if ( ! isset( $theme_json['settings']['typography']['fontFamilies'] ) ) {
			$theme_json['settings']['typography']['fontFamilies'] = array();
		}
		$theme_json['settings']['typography']['fontFamilies'] = array_merge(
			$theme_json['settings']['typography']['fontFamilies'],
			$font_families_to_copy
		);

		// Remove user fonts
		unset( $user_settings['typography']['fontFamilies']['custom'] );
		if ( empty( $user_settings['typography']['fontFamilies'] ) ) {
			unset( $user_settings['typography']['fontFamilies'] );
		}
		if ( empty( $user_settings['typography'] ) ) {
			unset( $user_settings['typography'] );
		}

		// Update the user settings
		CBT_Theme_JSON_Resolver::write_user_settings( $user_settings );

		// Update theme.json
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

	}

	public static function remove_deactivated_fonts_from_theme() {

		$user_settings = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_json    = CBT_Theme_JSON_Resolver::get_theme_file_contents();

		// If there are no deactivated theme fonts, bounce out
		if ( ! isset( $user_settings['typography']['fontFamilies']['theme'] ) ) {
			return;
		}

		$font_families_to_not_remove = $user_settings['typography']['fontFamilies']['theme'];

		// Remove font assets from theme
		$theme_font_asset_location = get_stylesheet_directory() . '/assets/fonts/';
		$font_families_to_remove   = array_values(
			array_filter(
				$theme_json['settings']['typography']['fontFamilies'],
				function( $theme_font_family ) use ( $font_families_to_not_remove ) {
					return ! in_array( $theme_font_family['slug'], array_column( $font_families_to_not_remove, 'slug' ), true );
				}
			)
		);
		foreach ( $font_families_to_remove as $font_family ) {
			if ( isset( $font_family['fontFace'] ) ) {
				foreach ( $font_family['fontFace'] as $font_face ) {
					$font_filename = basename( $font_face['src'] );
					if ( file_exists( $theme_font_asset_location . $font_filename ) ) {
						unlink( $theme_font_asset_location . $font_filename );
					}
				}
			}
		}

		// Remove user fonts from theme
		$theme_json['settings']['typography']['fontFamilies'] = array_values(
			array_filter(
				$theme_json['settings']['typography']['fontFamilies'],
				function( $theme_font_family ) use ( $font_families_to_not_remove ) {
					return in_array( $theme_font_family['slug'], array_column( $font_families_to_not_remove, 'slug' ), true );
				}
			)
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Remove user preferences for theme font activation
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
