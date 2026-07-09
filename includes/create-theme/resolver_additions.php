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
		 * @param array $options Export options.
		 * All options include user settings.
		 * 'current' will include settings from the currently installed theme but NOT from the parent theme.
		 * 'all' will include settings from the current theme as well as the parent theme (if it has one)
		 * 'variation' will include just the user custom styles and settings.
		 */
		public static function export_theme_data( $content, $extra_theme_data = null, $options = array() ) {
			$current_theme = wp_get_theme();
			$theme         = static::create_theme_json();

			if ( 'all' === $content && $current_theme->parent() ) {
				// Get parent theme.json.
				$parent_theme_json_data = static::get_theme_file_contents( true );
				$parent_theme_json_data = static::translate( $parent_theme_json_data, $current_theme->parent()->get( 'TextDomain' ) );

				// Get the schema from the parent JSON.
				if ( array_key_exists( '$schema', $parent_theme_json_data ) ) {
					$schema = $parent_theme_json_data['$schema'];
				}

				$parent_theme = static::create_theme_json( $parent_theme_json_data );
				$theme->merge( $parent_theme );
			}

			if ( 'all' === $content || 'current' === $content ) {
				$theme_json_data = static::get_theme_file_contents();
				$theme_json_data = static::translate( $theme_json_data, wp_get_theme()->get( 'TextDomain' ) );

				// Get the schema from the parent JSON.
				if ( array_key_exists( '$schema', $theme_json_data ) ) {
					$schema = $theme_json_data['$schema'];
				}

				$theme_theme = static::create_theme_json( $theme_json_data );
				$theme->merge( $theme_theme );
			}

			// Merge the User Data.
			$user_data = static::get_user_data();
			if ( ! empty( $options['removeCustomColorPrefix'] ) ) {
				$user_data = static::maybe_remove_custom_prefix_from_user_color_palette_slugs( $user_data );
			}
			$theme->merge( $user_data );

			// Merge the extra theme data received as a parameter
			if ( ! empty( $extra_theme_data ) ) {
				$extra_data = static::create_theme_json( $extra_theme_data );
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
		 * Remove the custom- prefix from user-created color slugs when the
		 * normalized slug does not conflict with an existing color slug.
		 *
		 * WordPress stores colors added in the editor as custom presets. When
		 * those colors are saved to the theme palette, their generated custom-
		 * prefix is redundant unless the unprefixed slug already exists.
		 *
		 * @param WP_Theme_JSON $user_data User theme JSON data.
		 * @return WP_Theme_JSON User theme JSON data, with normalized color slugs when applicable.
		 */
		private static function maybe_remove_custom_prefix_from_user_color_palette_slugs( $user_data ) {
			$theme_json_data = static::get_theme_file_contents();
			$theme_palette   = $theme_json_data['settings']['color']['palette'] ?? array();

			$raw_user_data  = $user_data->get_raw_data();
			$custom_palette = $raw_user_data['settings']['color']['palette']['custom'] ?? null;

			if ( empty( $custom_palette ) || ! is_array( $custom_palette ) ) {
				return $user_data;
			}

			$existing_slugs    = array_merge(
				static::get_color_palette_slugs( $theme_palette ),
				static::get_color_palette_slugs( $custom_palette )
			);
			$normalized_slugs  = array();
			$slug_replacements = array();

			foreach ( $custom_palette as $index => $color ) {
				$color_slug = $color['slug'] ?? '';

				if ( empty( $color_slug ) || 0 !== strpos( $color_slug, 'custom-' ) ) {
					continue;
				}

				$slug_without_prefix = substr( $color_slug, strlen( 'custom-' ) );

				if (
					'' === $slug_without_prefix ||
					in_array( $slug_without_prefix, $existing_slugs, true ) ||
					in_array( $slug_without_prefix, $normalized_slugs, true )
				) {
					continue;
				}

				$custom_palette[ $index ]['slug'] = $slug_without_prefix;
				$slug_replacements[ $color_slug ] = $slug_without_prefix;
				$normalized_slugs[]               = $slug_without_prefix;
			}

			if ( empty( $slug_replacements ) ) {
				return $user_data;
			}

			$raw_user_data['settings']['color']['palette']['custom'] = $custom_palette;

			if ( isset( $raw_user_data['styles'] ) ) {
				static::replace_color_slug_references( $raw_user_data['styles'], $slug_replacements );
			}

			return static::create_theme_json( $raw_user_data );
		}

		/**
		 * Get color slugs from a theme.json palette.
		 *
		 * @param array $palette Color palette data.
		 * @return array Color slugs.
		 */
		private static function get_color_palette_slugs( $palette ) {
			if ( empty( $palette ) || ! is_array( $palette ) ) {
				return array();
			}

			if ( isset( $palette[0] ) ) {
				return array_filter( array_column( $palette, 'slug' ) );
			}

			$slugs = array();

			foreach ( $palette as $palette_group ) {
				if ( is_array( $palette_group ) ) {
					$slugs = array_merge( $slugs, array_filter( array_column( $palette_group, 'slug' ) ) );
				}
			}

			return $slugs;
		}

		/**
		 * Create the appropriate theme JSON object for the current environment.
		 *
		 * @param array $data Theme JSON data.
		 * @return WP_Theme_JSON Theme JSON object.
		 */
		private static function create_theme_json( $data = array() ) {
			return class_exists( 'WP_Theme_JSON_Gutenberg' )
				? new WP_Theme_JSON_Gutenberg( $data )
				: new WP_Theme_JSON( $data );
		}

		/**
		 * Replace color slug references in style values after slug normalization.
		 *
		 * @param mixed $data Theme JSON style data.
		 * @param array $slug_replacements Slug replacements keyed by original slug.
		 */
		private static function replace_color_slug_references( &$data, $slug_replacements ) {
			if ( is_array( $data ) ) {
				foreach ( $data as &$value ) {
					static::replace_color_slug_references( $value, $slug_replacements );
				}
				unset( $value );
				return;
			}

			if ( ! is_string( $data ) ) {
				return;
			}

			foreach ( $slug_replacements as $old_slug => $new_slug ) {
				$data = str_replace(
					array(
						'var:preset|color|' . $old_slug,
						'var(--wp--preset--color--' . $old_slug . ')',
					),
					array(
						'var:preset|color|' . $new_slug,
						'var(--wp--preset--color--' . $new_slug . ')',
					),
					$data
				);
			}
		}

		/**
		 * Get the user data.
		 *
		 * This is a copy of the parent function with the addition of the Gutenberg resolver.
		 *
		 * @return WP_Theme_JSON User theme JSON data.
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

		public static function get_theme_file_contents( $parent = false ) {
			$theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json', $parent ) );
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
