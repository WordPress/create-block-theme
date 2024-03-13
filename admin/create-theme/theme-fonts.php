<?php


class Theme_Fonts {

	/**
	 * Copy any ACTIVATED fonts from USER configuration to THEME configuration including any font face assets.
	 * Remove any DEACTIVATED fronts from the THEME configuration.
	 */
	public static function persist_font_settings() {
		Theme_Fonts::copy_activated_fonts_to_theme();
		Theme_Fonts::remove_deactivated_fonts_from_theme();
	}

	public static function copy_activated_fonts_to_theme() {

		$user_settings = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_json    = MY_Theme_JSON_Resolver::get_theme_file_contents();

		// If there are no custom fonts, bounce out
		if ( ! isset( $user_settings['typography']['fontFamilies']['custom'] ) ) {
			return;
		}

		$font_families_to_copy = $user_settings['typography']['fontFamilies']['custom'];

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
		MY_Theme_JSON_Resolver::write_user_settings( $user_settings );

		// Update theme.json
		MY_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

	}

	public static function remove_deactivated_fonts_from_theme() {

	}

}
