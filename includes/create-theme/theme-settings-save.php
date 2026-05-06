<?php
/**
 * Theme Settings Save
 *
 * Persists Edit Theme Settings modal payloads to the active theme's theme.json.
 * Accepts a partial-`theme.json` payload (only the keys the user edited),
 * deep-merges it into the existing file, and writes the result. Reifies the
 * `removedShadowDefaults` operational key into the standard
 * `settings.shadow.defaultPresets` + `settings.shadow.presets` shape.
 *
 * Callers must enforce capability checks (`edit_theme_options`) before
 * invoking `run()`. The service performs payload-shape validation and
 * sanitization but does not authenticate.
 *
 * @package Create_Block_Theme
 */
class CBT_Theme_Settings_Save {

	const ALLOWED_TOP_LEVEL_KEYS = array(
		'settings',
		'customTemplates',
		'templateParts',
		'removedShadowDefaults',
	);

	/**
	 * Persist a partial theme.json payload to the active theme's theme.json.
	 *
	 * @param array $payload Partial-theme.json payload from the modal.
	 * @return array|WP_Error Merged theme.json on success, WP_Error on validation
	 *                       or write failure.
	 */
	public static function run( array $payload ) {
		$validated = self::validate( $payload );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$sanitized = self::sanitize( $validated );

		$current = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$merged = self::merge( $current, $sanitized );

		if ( array_key_exists( 'removedShadowDefaults', $sanitized ) ) {
			$merged = self::reify_shadow_removals( $merged, $sanitized['removedShadowDefaults'] );
		}

		CBT_Theme_JSON_Resolver::write_theme_file_contents( $merged );

		return $merged;
	}

	/**
	 * Validate payload shape. Rejects unknown top-level keys and shape
	 * mismatches. Returns the payload with the operational key extracted but
	 * otherwise unchanged on success.
	 *
	 * @param array $payload Raw payload from the request.
	 * @return array|WP_Error
	 */
	public static function validate( array $payload ) {
		foreach ( $payload as $key => $value ) {
			if ( ! in_array( $key, self::ALLOWED_TOP_LEVEL_KEYS, true ) ) {
				return new WP_Error(
					'cbt_invalid_payload',
					sprintf(
						/* translators: %s: unknown payload key */
						__( 'Unknown top-level key: %s', 'create-block-theme' ),
						$key
					),
					array( 'status' => 400 )
				);
			}
		}

		if ( isset( $payload['settings'] ) && ! is_array( $payload['settings'] ) ) {
			return new WP_Error(
				'cbt_invalid_payload',
				__( '"settings" must be an object.', 'create-block-theme' ),
				array( 'status' => 400 )
			);
		}

		foreach ( array( 'customTemplates', 'templateParts' ) as $list_key ) {
			if ( ! isset( $payload[ $list_key ] ) ) {
				continue;
			}
			if ( ! is_array( $payload[ $list_key ] ) ) {
				return new WP_Error(
					'cbt_invalid_payload',
					sprintf(
						/* translators: %s: payload key */
						__( '"%s" must be an array.', 'create-block-theme' ),
						$list_key
					),
					array( 'status' => 400 )
				);
			}
			foreach ( $payload[ $list_key ] as $entry ) {
				if ( ! is_array( $entry ) ) {
					return new WP_Error(
						'cbt_invalid_payload',
						sprintf(
							/* translators: %s: payload key */
							__( 'Entries of "%s" must be objects.', 'create-block-theme' ),
							$list_key
						),
						array( 'status' => 400 )
					);
				}
			}
		}

		if ( isset( $payload['removedShadowDefaults'] ) ) {
			if ( ! is_array( $payload['removedShadowDefaults'] ) ) {
				return new WP_Error(
					'cbt_invalid_payload',
					__( '"removedShadowDefaults" must be an array of slugs.', 'create-block-theme' ),
					array( 'status' => 400 )
				);
			}
			foreach ( $payload['removedShadowDefaults'] as $slug ) {
				if ( ! is_string( $slug ) ) {
					return new WP_Error(
						'cbt_invalid_payload',
						__( 'Entries of "removedShadowDefaults" must be strings.', 'create-block-theme' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		return $payload;
	}

	/**
	 * Recursively sanitize string leaves in the payload. Booleans and numbers
	 * pass through; arrays recurse; strings are run through `sanitize_text_field`.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function sanitize( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$out[ $k ] = self::sanitize( $v );
			}
			return $out;
		}
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		return $value;
	}

	/**
	 * Deep-merge $payload into $current. At every level, associative-array
	 * values are merged recursively; lists, scalars, and empty arrays replace
	 * the existing value. Missing parent keys are created.
	 *
	 * The operational key `removedShadowDefaults` is dropped here — it's
	 * handled separately by `reify_shadow_removals()`.
	 *
	 * @param array $current
	 * @param array $payload
	 * @return array
	 */
	public static function merge( array $current, array $payload ) {
		$result = $current;
		foreach ( $payload as $key => $value ) {
			if ( 'removedShadowDefaults' === $key ) {
				continue;
			}
			if (
				is_array( $value ) &&
				! self::is_list( $value ) &&
				isset( $result[ $key ] ) &&
				is_array( $result[ $key ] ) &&
				! self::is_list( $result[ $key ] )
			) {
				$result[ $key ] = self::merge( $result[ $key ], $value );
			} else {
				$result[ $key ] = $value;
			}
		}
		return $result;
	}

	/**
	 * Translate `removedShadowDefaults: [slug, ...]` into the theme.json shape:
	 * `settings.shadow.defaultPresets: false` plus the kept core shadow
	 * defaults re-registered under `settings.shadow.presets`. User-defined
	 * presets (slugs that are not core defaults) are preserved.
	 *
	 * Idempotent: running the same removal twice produces the same output.
	 *
	 * @param array    $merged        Merged theme.json (post `merge()`).
	 * @param string[] $removed_slugs Slugs of core defaults to remove.
	 * @return array
	 */
	public static function reify_shadow_removals( array $merged, array $removed_slugs ) {
		$core_presets = self::get_core_shadow_presets();
		if ( empty( $core_presets ) ) {
			return $merged;
		}

		$core_slugs = array_column( $core_presets, 'slug' );
		$kept       = array_values(
			array_filter(
				$core_presets,
				static function ( $preset ) use ( $removed_slugs ) {
					return ! in_array( $preset['slug'], $removed_slugs, true );
				}
			)
		);

		$existing = isset( $merged['settings']['shadow']['presets'] ) && is_array( $merged['settings']['shadow']['presets'] )
			? $merged['settings']['shadow']['presets']
			: array();

		// Strip any existing presets whose slug matches a core default slug —
		// we re-register the kept defaults below, so this prevents duplication.
		$user_customs = array_values(
			array_filter(
				$existing,
				static function ( $preset ) use ( $core_slugs ) {
					return isset( $preset['slug'] ) && ! in_array( $preset['slug'], $core_slugs, true );
				}
			)
		);

		if ( ! isset( $merged['settings'] ) || ! is_array( $merged['settings'] ) ) {
			$merged['settings'] = array();
		}
		if ( ! isset( $merged['settings']['shadow'] ) || ! is_array( $merged['settings']['shadow'] ) ) {
			$merged['settings']['shadow'] = array();
		}

		$merged['settings']['shadow']['defaultPresets'] = false;
		$merged['settings']['shadow']['presets']        = array_merge( $user_customs, $kept );

		return $merged;
	}

	/**
	 * Fetch the core shadow defaults via `wp_get_global_settings`. Returns an
	 * empty array if core does not expose shadow defaults (older WP versions
	 * or unusual environments) — in which case shadow reification is a no-op
	 * and the caller's `defaultPresets` flag round-trips literally.
	 *
	 * `wp_get_global_settings` returns presets keyed by origin
	 * (`['default' => [...]]`); we extract the `default` slot.
	 *
	 * @return array<int, array{slug: string, name?: string, shadow: string}>
	 */
	public static function get_core_shadow_presets() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}
		$presets = wp_get_global_settings( array( 'shadow', 'presets' ) );
		if ( is_array( $presets ) && isset( $presets['default'] ) && is_array( $presets['default'] ) ) {
			return $presets['default'];
		}
		return array();
	}

	/**
	 * Detect whether an array is a list (sequential integer keys starting at 0).
	 * Mirrors PHP 8.1+ `array_is_list()`.
	 *
	 * @param array $arr
	 * @return bool
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
}
