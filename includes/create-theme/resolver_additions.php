<?php

function cbt_augment_resolver_with_utilities() {

	//Ultimately it is desireable for Core to have this functionality natively.
	// In the meantime we are patching the functionality we are expecting into the Theme JSON Resolver here
	if ( ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
		return;
	}

	class CBT_Theme_JSON_Resolver extends WP_Theme_JSON_Resolver {

		/**
		 * Export the combined (and flattened) THEME and CUSTOM data.
		 *
		 * @param string $content ['all', 'current', 'user'] Determines which settings content to include in the export.
		 * @param array $extra_theme_data Any theme json extra data to be included in the export.
		 * All options include user settings.
		 * 'current' will include settings from the currently installed theme but NOT from the parent theme.
		 * 'all' will include settings from the current theme as well as the parent theme (if it has one)
		 * 'variation' will include just the user custom styles and settings.
		 */
		public static function export_theme_data( $content, $extra_theme_data = null ) {
			$current_theme = wp_get_theme();
			if ( class_exists( 'WP_Theme_JSON_Gutenberg' ) ) {
				$theme = new WP_Theme_JSON_Gutenberg();
			} else {
				$theme = new WP_Theme_JSON();
			}

			if ( 'all' === $content && $current_theme->parent() ) {
				// Get parent theme.json.
				$parent_theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json', true ) );
				$parent_theme_json_data = static::translate( $parent_theme_json_data, $current_theme->parent()->get( 'TextDomain' ) );

				// Get the schema from the parent JSON.
				$schema = $parent_theme_json_data['$schema'];
				if ( array_key_exists( 'schema', $parent_theme_json_data ) ) {
					$schema = $parent_theme_json_data['$schema'];
				}

				if ( class_exists( 'WP_Theme_JSON_Gutenberg' ) ) {
					$parent_theme = new WP_Theme_JSON_Gutenberg( $parent_theme_json_data );
				} else {
					$parent_theme = new WP_Theme_JSON( $parent_theme_json_data );
				}
				$theme->merge( $parent_theme );
			}

			if ( 'all' === $content || 'current' === $content ) {
				$theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json' ) );
				$theme_json_data = static::translate( $theme_json_data, wp_get_theme()->get( 'TextDomain' ) );

				// Get the schema from the parent JSON.
				if ( array_key_exists( 'schema', $theme_json_data ) ) {
					$schema = $theme_json_data['$schema'];
				}

				if ( class_exists( 'WP_Theme_JSON_Gutenberg' ) ) {
					$theme_theme = new WP_Theme_JSON_Gutenberg( $theme_json_data );
				} else {
					$theme_theme = new WP_Theme_JSON( $theme_json_data );
				}
				$theme->merge( $theme_theme );
			}

			// Merge the User Data
			$theme->merge( static::get_user_data() );

			// Merge the extra theme data received as a parameter
			if ( ! empty( $extra_theme_data ) ) {
				if ( class_exists( 'WP_Theme_JSON_Gutenberg' ) ) {
					$extra_data = new WP_Theme_JSON_Gutenberg( $extra_theme_data );
				} else {
					$extra_data = new WP_Theme_JSON( $extra_theme_data );
				}
				$theme->merge( $extra_data );
			}

			$data = $theme->get_data();

			// Add the schema.
			if ( empty( $schema ) ) {
				global $wp_version;
				$theme_json_version = 'wp/' . substr( $wp_version, 0, 3 );
				if ( defined( 'IS_GUTENBERG_PLUGIN' ) ) {
					$theme_json_version = 'trunk';
				}
				$schema = 'https://schemas.wp.org/' . $theme_json_version . '/theme.json';
			}
			$data['$schema'] = $schema;
			return static::stringify( $data );
		}

		/**
		 * Get the user data.
		 *
		 * This is a copy of the parent function with the addition of the Gutenberg resolver.
		 *
		 * @return array
		 */
		public static function get_user_data() {
			// Determine the correct method to retrieve user data
			return class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' )
				? WP_Theme_JSON_Resolver_Gutenberg::get_user_data()
				: parent::get_user_data();
		}

		/**
		 * Stringify the array data.
		 *
		 * $data is an array of data to be converted to a JSON string.
		 * @return string JSON string.
		 */
		public static function stringify( $data ) {
			$data = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			// Convert spaces to tabs
			return preg_replace( '~(?:^|\G)\h{4}~m', "\t", $data );
		}

		public static function get_theme_file_contents() {
			$theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json' ) );
			return $theme_json_data;
		}

		public static function write_theme_file_contents( $theme_json_data ) {
			$theme_json = wp_json_encode( $theme_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			file_put_contents( static::get_file_path_from_theme( 'theme.json' ), $theme_json );
			static::clean_cached_data();
		}

		public static function write_user_settings( $user_settings ) {
			$global_styles_id = static::get_user_global_styles_post_id();
			$request          = new WP_REST_Request( 'POST', '/wp/v2/global-styles/' . $global_styles_id );
			$request->set_param( 'settings', $user_settings );
			rest_do_request( $request );
			static::clean_cached_data();
		}

		public static function clean_cached_data() {
			parent::clean_cached_data();

			if ( class_exists( 'WP_Theme_JSON_Resolver_Gutenberg' ) ) {
				WP_Theme_JSON_Resolver_Gutenberg::clean_cached_data();
			}

			//TODO: Clearing the cache should clear this too.
			// Does this clear the Gutenberg equivalent?
			static::$theme_json_file_cache = array();
		}
	}
}

add_action( 'plugins_loaded', 'cbt_augment_resolver_with_utilities' );
