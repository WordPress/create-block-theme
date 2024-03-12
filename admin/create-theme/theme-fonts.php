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

		$global_styles_id = MY_Theme_JSON_Resolver::get_user_global_styles_post_id();

		$user_settings = MY_Theme_JSON_Resolver::get_user_data();//->get_settings();
		// $theme_settings = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		// if ( ! isset( $user_settings['typography']['fontFamilies']['custom'] ) ) {
		// 	return;
		// }

		//TODO: Merge the arrays if they exist
		// $theme_settings['typography']['fontFamilies']['theme'] = $user_settings['typography']['fontFamilies']['custom'];

		// unset( $user_settings['typography']['fontFamilies']['custom'] );

		$request = new WP_REST_Request( 'POST', '/wp/v2/global-styles/' . $global_styles_id );
		$request->set_param( 'settings', array() );

		rest_do_request( $request );
	}

	public static function remove_deactivated_fonts_from_theme() {

	}

}
