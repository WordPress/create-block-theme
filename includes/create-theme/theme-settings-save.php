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
	 * Keys whose string values are user-facing labels and benefit from
	 * `sanitize_text_field()` (HTML stripping, whitespace normalization).
	 */
	const TEXT_FIELD_KEYS = array( 'name', 'title', 'label', 'description' );

	/**
	 * Keys whose string values are slug-formatted and pass through `sanitize_key()`.
	 * Used for both leaf scalars and entries of slug-list keys (e.g., `postTypes`).
	 */
	const SLUG_FIELD_KEYS = array( 'slug', 'area' );

	/**
	 * Keys whose values are lists of slug strings (the entries — not the key — are slugs).
	 */
	const SLUG_LIST_KEYS = array( 'postTypes' );

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

		$wrote = CBT_Theme_JSON_Resolver::write_theme_file_contents( $merged );
		if ( true !== $wrote ) {
			return new WP_Error(
				'cbt_write_failed',
				__( 'Failed to write theme.json. Check filesystem permissions on the active theme directory.', 'create-block-theme' ),
				array( 'status' => 500 )
			);
		}

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
				if ( sanitize_key( $slug ) !== $slug || '' === $slug ) {
					return new WP_Error(
						'cbt_invalid_payload',
						sprintf(
							/* translators: %s: invalid slug */
							__( 'Invalid shadow slug "%s". Slugs must be lowercase alphanumeric with dashes or underscores.', 'create-block-theme' ),
							$slug
						),
						array( 'status' => 400 )
					);
				}
			}
		}

		// Validate required keys + slug format on customTemplates and templateParts
		// entries. `name` is required on both (used as the file-system slug,
		// e.g. templates/<name>.html). `area` is required on templateParts;
		// `title` is required on customTemplates.
		$entry_required_keys = array(
			'customTemplates' => array( 'name', 'title' ),
			'templateParts'   => array( 'name', 'area' ),
		);
		foreach ( $entry_required_keys as $list_key => $required_keys ) {
			if ( ! isset( $payload[ $list_key ] ) ) {
				continue;
			}
			foreach ( $payload[ $list_key ] as $entry ) {
				foreach ( $required_keys as $required_key ) {
					if ( ! isset( $entry[ $required_key ] ) || ! is_string( $entry[ $required_key ] ) || '' === $entry[ $required_key ] ) {
						return new WP_Error(
							'cbt_invalid_payload',
							sprintf(
								/* translators: 1: list key, 2: required key */
								__( 'Entries of "%1$s" must have a non-empty string "%2$s" field.', 'create-block-theme' ),
								$list_key,
								$required_key
							),
							array( 'status' => 400 )
						);
					}
				}
				// `name` must be a valid slug (used as filename).
				if ( sanitize_key( $entry['name'] ) !== $entry['name'] ) {
					return new WP_Error(
						'cbt_invalid_payload',
						sprintf(
							/* translators: 1: list key, 2: invalid name */
							__( 'Invalid "%1$s" entry name "%2$s". Names must be lowercase alphanumeric with dashes or underscores.', 'create-block-theme' ),
							$list_key,
							$entry['name']
						),
						array( 'status' => 400 )
					);
				}
			}
		}

		return $payload;
	}

	/**
	 * Recursively sanitize the payload, applying a context-aware sanitizer per
	 * leaf based on the parent key.
	 *
	 * - `name`/`title`/`label`/`description` → `sanitize_text_field()` (HTML-stripped labels).
	 * - `slug`/`area` → `sanitize_key()` (already validated to be slug-safe; this is belt-and-braces).
	 * - Entries inside `postTypes` lists → `sanitize_key()`.
	 * - Everything else (CSS values like `shadow`, `color`, `gradient`, `fontFamily`)
	 *   → `wp_kses_no_null()` (strip NULL bytes only; preserve whitespace, parens, commas).
	 *
	 * Booleans and numbers pass through unchanged.
	 *
	 * @param mixed  $value      Value to sanitize.
	 * @param string $parent_key The key under which `$value` lives (empty for the root).
	 *                           For list entries, the list's key name.
	 * @return mixed
	 */
	public static function sanitize( $value, $parent_key = '' ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				// Associative-key children inherit their own key as context.
				// List-entry children inherit the list's key as context.
				$child_context = is_string( $k ) ? $k : $parent_key;
				$out[ $k ]     = self::sanitize( $v, $child_context );
			}
			return $out;
		}
		if ( is_string( $value ) ) {
			if ( in_array( $parent_key, self::TEXT_FIELD_KEYS, true ) ) {
				return sanitize_text_field( $value );
			}
			if (
				in_array( $parent_key, self::SLUG_FIELD_KEYS, true ) ||
				in_array( $parent_key, self::SLUG_LIST_KEYS, true )
			) {
				return sanitize_key( $value );
			}
			return wp_kses_no_null( $value );
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
