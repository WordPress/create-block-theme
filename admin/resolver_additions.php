<?php

function augment_resolver_with_utilities() {

	//Ultimately it is desireable for Core to have this functionality natively.
	// In the meantime we are patching the functionality we are expecting into the Theme JSON Resolver here
	if ( ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
		return;
	}

	class MY_Theme_JSON_Resolver extends WP_Theme_JSON_Resolver {

		/**
		 * Export the combined (and flattened) THEME and CUSTOM data.
		 *
		 * @param string $content ['all', 'current', 'user'] Determines which settings content to include in the export.
		 * All options include user settings.
		 * 'current' will include settings from the currently installed theme but NOT from the parent theme.
		 * 'all' will include settings from the current theme as well as the parent theme (if it has one)
		 */
		public static function export_theme_data( $content ) {
			$theme = new WP_Theme_JSON();

			if ( $content === 'all' && wp_get_theme()->parent() ) {
				// Get parent theme.json.
				$parent_theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json', true ) );
				$parent_theme_json_data = static::translate( $parent_theme_json_data, wp_get_theme()->parent()->get( 'TextDomain' ) );
				$parent_theme           = new WP_Theme_JSON( $parent_theme_json_data );
				$theme->merge ($parent_theme);
			}

			if ( $content === 'all' || $content === 'current' ) {
				$theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json' ) );
				$theme_json_data = static::translate( $theme_json_data, wp_get_theme()->get( 'TextDomain' ) );
				$theme_theme     = new WP_Theme_JSON( $theme_json_data );
 				$theme->merge( $theme_theme );
			}

			$theme->merge( static::get_user_data() );

			$data = MY_Theme_JSON_Resolver::$theme->get_data();

			$theme_json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			return preg_replace ( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

		}

	}
}

add_action( 'plugins_loaded', 'augment_resolver_with_utilities' );
