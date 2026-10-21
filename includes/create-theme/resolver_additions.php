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

			if ( 'current' === $content && $current_theme->parent() ) {
				$parent_file = static::get_file_path_from_theme( 'theme.json', true );
				if ( $parent_file ) {
					$parent_data = static::read_json_file( $parent_file );
					if ( is_array( $parent_data ) ) {
						$data = static::merge_parent_preset_arrays( $data, $parent_data );
					}
				}
			}

			// Add the schema.
			if ( empty( $schema ) ) {
				global $wp_version;
				$theme_json_version = 'wp/' . substr( $wp_version, 0, 3 );
				if ( defined( 'IS_GUTENBERG_PLUGIN' ) ) {
					$theme_json_version = 'trunk';
				}
				$schema = 'https://schemas.wp.org/' . $theme_json_version . '/theme.json';
			}
			// Ensure $schema is the first property and version is second in the JSON output.
			$ordered_data = array( '$schema' => $schema );

			// Add version as the second property if it exists.
			if ( isset( $data['version'] ) ) {
				$ordered_data['version'] = $data['version'];
				unset( $data['version'] );
			}

			// Merge the remaining data.
			$data = array_merge( $ordered_data, $data );
			return static::stringify( $data );
		}

		/**
		 * Merge parent theme preset arrays into child theme data.
		 *
		 * For child themes, WordPress Core replaces indexed preset arrays (like color palettes,
		 * font sizes, gradients) entirely rather than merging them. This method traverses
		 * child data and backfills parent preset entries for any preset array present in child data,
		 * with child entries overriding parent entries that share the same slug.
		 *
		 * @param array $child_data  Child theme data.
		 * @param array $parent_data Parent theme data.
		 * @return array Merged theme data.
		 */
		public static function merge_parent_preset_arrays( array $child_data, array $parent_data ) {
			if ( empty( $child_data ) || empty( $parent_data ) ) {
				return $child_data;
			}

			$result = $child_data;

			foreach ( $child_data as $key => $child_value ) {
				// Non-arrays (scalars, booleans, null) are already preserved in $result.
				if ( ! is_array( $child_value ) ) {
					continue;
				}

				// Associative objects (settings, color, blocks, etc.): recurse.
				if ( ! static::is_list( $child_value ) ) {
					$parent_subdata = ( isset( $parent_data[ $key ] ) && is_array( $parent_data[ $key ] ) ) ? $parent_data[ $key ] : array();
					$result[ $key ] = static::merge_parent_preset_arrays( $child_value, $parent_subdata );
					continue;
				}

				// Slug-keyed preset lists: backfill parent entries if parent defines this preset.
				if ( static::is_preset_list( $child_value ) && isset( $parent_data[ $key ] ) && static::is_preset_list( $parent_data[ $key ] ) ) {
					$result[ $key ] = static::merge_preset_list( $child_value, $parent_data[ $key ] );
				}
			}

			return $result;
		}

		/**
		 * Check whether a value is a slug-keyed preset list (e.g. palette, fontSizes).
		 *
		 * @param mixed $value Value to check.
		 * @return bool Whether the value is a preset list.
		 */
		private static function is_preset_list( $value ) {
			return is_array( $value )
				&& static::is_list( $value )
				&& ! empty( $value )
				&& is_array( $value[0] )
				&& isset( $value[0]['slug'] );
		}

		/**
		 * Merge parent and child preset lists keyed by slug.
		 *
		 * Preserves parent item order with child overrides replacing parent items
		 * at their original positions, and new child entries appended.
		 *
		 * @param array $child_list  Child preset list.
		 * @param array $parent_list Parent preset list.
		 * @return array Merged preset list.
		 */
		private static function merge_preset_list( array $child_list, array $parent_list ) {
			$merged        = array();
			$slug_to_index = array();

			foreach ( $parent_list as $item ) {
				if ( is_array( $item ) && isset( $item['slug'] ) ) {
					$slug_to_index[ $item['slug'] ] = count( $merged );
				}
				$merged[] = $item;
			}

			foreach ( $child_list as $item ) {
				if ( is_array( $item ) && isset( $item['slug'] ) && isset( $slug_to_index[ $item['slug'] ] ) ) {
					$merged[ $slug_to_index[ $item['slug'] ] ] = $item;
				} else {
					if ( is_array( $item ) && isset( $item['slug'] ) ) {
						$slug_to_index[ $item['slug'] ] = count( $merged );
					}
					$merged[] = $item;
				}
			}

			return $merged;
		}

		/**
		 * Detect whether an array is a list (sequential integer keys starting at 0).
		 * Mirrors PHP 8.1+ `array_is_list()`.
		 *
		 * @param array $arr Array to check.
		 * @return bool Whether the array is a list.
		 */
		private static function is_list( array $arr ) {
			if ( function_exists( 'array_is_list' ) ) {
				return array_is_list( $arr );
			}
			if ( array() === $arr ) {
				return true;
			}
			$expected = 0;
			foreach ( $arr as $key => $_v ) {
				if ( $key !== $expected++ ) {
					return false;
				}
			}
			return true;
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
			// `wp_json_encode` returns false if the data contains an
			// unencodable value (resources, NAN/INF after wrapping, etc.).
			// Bail before opening a temp file so theme.json is never
			// truncated or replaced with empty content.
			if ( false === $theme_json ) {
				return false;
			}
			$target = static::get_file_path_from_theme( 'theme.json' );

			// Atomic write with a request-unique temp file: write to a
			// per-request sibling temp file, then rename into place. A
			// per-request name prevents two concurrent saves from clobbering
			// each other's staging payload (a shared `theme.json.tmp` is unsafe
			// — request A could rename request B's truncated contents, or one
			// could unlink the other's temp file mid-write). Each request
			// cleans up only its own temp file on failure.
			$tmp = $target . '.' . uniqid( '', true ) . '.tmp';
			// Suppress warnings so a permission/disk error returns false cleanly
			// rather than emitting a PHP warning that may be promoted to an
			// exception. Callers must check the boolean return value.
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$bytes = @file_put_contents( $tmp, $theme_json );
			if ( false === $bytes ) {
				return false;
			}

			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! @rename( $tmp, $target ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				@unlink( $tmp );
				return false;
			}

			static::clean_cached_data();
			// Bust the WP-level theme cache too — `clean_cached_data()` only
			// clears the JSON resolver's caches, not `wp_get_theme()`.
			wp_get_theme()->cache_delete();
			return true;
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
