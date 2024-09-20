<?php

class CBT_Theme_JSON {

	public static function add_theme_json_to_local( $export_type ) {
		file_put_contents(
			get_stylesheet_directory() . '/theme.json',
			CBT_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
	}

	public static function add_theme_json_variation_to_local( $export_type, $theme, $save_fonts = false ) {
		$variation_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;

		if ( ! file_exists( $variation_path ) ) {
			wp_mkdir_p( $variation_path );
		}

		if ( file_exists( $variation_path . $theme['slug'] . '.json' ) ) {
			return new WP_Error( 'variation_already_exists', __( 'Variation already exists.', 'create-block-theme' ) );
		}

		$theme_json = class_exists( 'WP_Theme_JSON_Gutenberg' ) ? new WP_Theme_JSON_Gutenberg() : new WP_Theme_JSON();
		$user_data  = CBT_Theme_JSON_Resolver::get_user_data();
		$theme_json->merge( $user_data );
		$variation          = $theme_json->get_data();
		$variation['title'] = $theme['name'];

		if (
			$save_fonts &&
			isset( $variation['settings']['typography']['fontFamilies'] )
		) {
				$font_families = $variation['settings']['typography']['fontFamilies'];
				// Copy the font assets to the theme assets folder.
				$copied_font_families = CBT_Theme_Fonts::copy_font_assets_to_theme( $font_families );
				// Update the the variation theme json with the font families with the new paths.
				$variation['settings']['typography']['fontFamilies'] = $copied_font_families;
		}

		file_put_contents(
			$variation_path . $theme['slug'] . '.json',
			CBT_Theme_JSON_Resolver::stringify( $variation )
		);

		return $variation;
	}
}
