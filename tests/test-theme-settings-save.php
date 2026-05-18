<?php
/**
 * @package Create_Block_Theme
 */
class Test_CBT_Theme_Settings_Save extends WP_UnitTestCase {

	/* ---------------------------------------------------------------- *
	 * validate()
	 * ---------------------------------------------------------------- */

	public function test_validate_accepts_known_top_level_keys() {
		$payload = array(
			'settings'              => array( 'color' => array( 'custom' => true ) ),
			'customTemplates'       => array(),
			'templateParts'         => array(),
			'removedShadowDefaults' => array( 'natural' ),
		);
		$this->assertSame( $payload, CBT_Theme_Settings_Save::validate( $payload ) );
	}

	public function test_validate_rejects_unknown_top_level_key() {
		$result = CBT_Theme_Settings_Save::validate( array( 'unexpected' => 1 ) );
		$this->assertWPError( $result );
		$this->assertSame( 'cbt_invalid_payload', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_validate_rejects_settings_not_array() {
		$result = CBT_Theme_Settings_Save::validate( array( 'settings' => 'oops' ) );
		$this->assertWPError( $result );
	}

	public function test_validate_rejects_custom_templates_entries_not_objects() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'customTemplates' => array( 'not-an-object' ) )
		);
		$this->assertWPError( $result );
	}

	public function test_validate_rejects_removed_shadow_defaults_entries_not_strings() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'removedShadowDefaults' => array( 1 ) )
		);
		$this->assertWPError( $result );
	}

	/* ---------------------------------------------------------------- *
	 * sanitize()
	 * ---------------------------------------------------------------- */

	public function test_sanitize_strips_tags_from_string_values() {
		$out = CBT_Theme_Settings_Save::sanitize(
			array(
				'settings' => array(
					'color' => array(
						'palette' => array(
							array(
								'slug'  => 'brand',
								'name'  => '<script>alert(1)</script>Brand',
								'color' => '#fff',
							),
						),
					),
				),
			)
		);
		$this->assertSame( 'Brand', $out['settings']['color']['palette'][0]['name'] );
	}

	public function test_sanitize_preserves_booleans_and_numbers() {
		$out = CBT_Theme_Settings_Save::sanitize(
			array(
				'settings' => array(
					'color'   => array(
						'custom'         => false,
						'defaultPalette' => true,
					),
					'spacing' => array( 'padding' => 16 ),
				),
			)
		);
		$this->assertSame( false, $out['settings']['color']['custom'] );
		$this->assertSame( true, $out['settings']['color']['defaultPalette'] );
		$this->assertSame( 16, $out['settings']['spacing']['padding'] );
	}

	public function test_sanitize_preserves_camel_case_keys() {
		$out = CBT_Theme_Settings_Save::sanitize(
			array( 'settings' => array( 'color' => array( 'defaultPalette' => true ) ) )
		);
		$this->assertArrayHasKey( 'defaultPalette', $out['settings']['color'] );
	}

	/* ---------------------------------------------------------------- *
	 * merge()
	 * ---------------------------------------------------------------- */

	public function test_merge_partial_settings_color_palette_leaves_other_keys_untouched() {
		$current = array(
			'settings' => array(
				'color'   => array(
					'palette'   => array(
						array(
							'slug'  => 'old',
							'color' => '#000',
						),
					),
					'gradients' => array(
						array(
							'slug'     => 'g1',
							'gradient' => 'linear-gradient(red,blue)',
						),
					),
					'custom'    => true,
				),
				'spacing' => array( 'padding' => true ),
			),
		);
		$payload = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'new',
							'color' => '#fff',
						),
					),
				),
			),
		);
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( 'new', $out['settings']['color']['palette'][0]['slug'] );
		$this->assertSame( 'g1', $out['settings']['color']['gradients'][0]['slug'] );
		$this->assertTrue( $out['settings']['color']['custom'] );
		$this->assertTrue( $out['settings']['spacing']['padding'] );
	}

	public function test_merge_empty_palette_array_clears_palette() {
		$current = array(
			'settings' => array(
				'color' => array( 'palette' => array( array( 'slug' => 'a' ) ) ),
			),
		);
		$payload = array( 'settings' => array( 'color' => array( 'palette' => array() ) ) );
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( array(), $out['settings']['color']['palette'] );
	}

	public function test_merge_creates_missing_parent_keys() {
		$current = array(); // theme.json without any settings
		$payload = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'brand',
							'color' => '#fff',
						),
					),
				),
			),
		);
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( 'brand', $out['settings']['color']['palette'][0]['slug'] );
	}

	public function test_merge_replaces_template_parts_array_wholesale() {
		$current = array(
			'templateParts' => array(
				array(
					'name' => 'header',
					'area' => 'header',
				),
				array(
					'name' => 'footer',
					'area' => 'footer',
				),
			),
		);
		$payload = array(
			'templateParts' => array(
				array(
					'name' => 'sidebar',
					'area' => 'uncategorized',
				),
			),
		);
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertCount( 1, $out['templateParts'] );
		$this->assertSame( 'sidebar', $out['templateParts'][0]['name'] );
	}

	public function test_merge_does_not_emit_removed_shadow_defaults_key() {
		$out = CBT_Theme_Settings_Save::merge(
			array(),
			array( 'removedShadowDefaults' => array( 'natural' ) )
		);
		$this->assertArrayNotHasKey( 'removedShadowDefaults', $out );
	}

	/* ---------------------------------------------------------------- *
	 * merge() — RFC 7396 semantics
	 * ---------------------------------------------------------------- */

	public function test_merge_null_deletes_existing_key() {
		$current = array(
			'settings' => array(
				'color' => array(
					'custom'         => true,
					'defaultPalette' => true,
				),
			),
		);
		$payload = array(
			'settings' => array( 'color' => array( 'custom' => null ) ),
		);
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertArrayNotHasKey( 'custom', $out['settings']['color'] );
		$this->assertTrue( $out['settings']['color']['defaultPalette'] );
	}

	public function test_merge_null_for_missing_key_is_no_op() {
		$current = array( 'settings' => array( 'color' => array( 'custom' => true ) ) );
		$payload = array( 'settings' => array( 'color' => array( 'doesNotExist' => null ) ) );
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertTrue( $out['settings']['color']['custom'] );
		$this->assertArrayNotHasKey( 'doesNotExist', $out['settings']['color'] );
	}

	public function test_merge_empty_object_at_top_level_is_no_op() {
		// The footgun the JSON-Merge-Patch contract closes: `settings: {}` must
		// not wipe everything in the existing settings tree.
		$current = array(
			'settings' => array(
				'color'   => array( 'custom' => true ),
				'spacing' => array( 'padding' => true ),
			),
		);
		$payload = array( 'settings' => array() );
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( $current, $out );
	}

	public function test_merge_empty_object_nested_is_no_op() {
		$current = array(
			'settings' => array(
				'color' => array(
					'custom'  => true,
					'palette' => array( array( 'slug' => 'a' ) ),
				),
			),
		);
		$payload = array( 'settings' => array( 'color' => array() ) );
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( $current, $out );
	}

	public function test_merge_empty_for_existing_list_still_clears() {
		// `palette: []` should still clear the palette, because palette is a
		// list (its current value is a sequential array).
		$current = array(
			'settings' => array(
				'color' => array( 'palette' => array( array( 'slug' => 'a' ) ) ),
			),
		);
		$payload = array( 'settings' => array( 'color' => array( 'palette' => array() ) ) );
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( array(), $out['settings']['color']['palette'] );
	}

	public function test_merge_empty_for_missing_list_is_no_op() {
		// payload `customTemplates: []` against a theme.json that doesn't have
		// a customTemplates key at all → no-op (don't create an empty list).
		$current = array( 'settings' => array() );
		$payload = array( 'customTemplates' => array() );
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertArrayNotHasKey( 'customTemplates', $out );
	}

	/* ---------------------------------------------------------------- *
	 * reify_shadow_removals()
	 * ---------------------------------------------------------------- */

	public function test_reify_shadow_removals_sets_default_presets_false_and_re_registers_kept() {
		$core_presets = CBT_Theme_Settings_Save::get_core_shadow_presets();
		if ( empty( $core_presets ) ) {
			$this->markTestSkipped( 'WP core does not expose shadow defaults in this environment.' );
		}
		$out = CBT_Theme_Settings_Save::reify_shadow_removals( array(), array( $core_presets[0]['slug'] ) );
		$this->assertFalse( $out['settings']['shadow']['defaultPresets'] );
		$kept_slugs = array_column( $out['settings']['shadow']['presets'], 'slug' );
		$this->assertNotContains( $core_presets[0]['slug'], $kept_slugs );
		$this->assertCount( count( $core_presets ) - 1, $kept_slugs );
	}

	public function test_reify_shadow_removals_is_idempotent() {
		$core_presets = CBT_Theme_Settings_Save::get_core_shadow_presets();
		if ( empty( $core_presets ) ) {
			$this->markTestSkipped( 'WP core does not expose shadow defaults in this environment.' );
		}
		$once  = CBT_Theme_Settings_Save::reify_shadow_removals( array(), array( $core_presets[0]['slug'] ) );
		$twice = CBT_Theme_Settings_Save::reify_shadow_removals( $once, array( $core_presets[0]['slug'] ) );
		$this->assertSame( $once, $twice );
	}

	public function test_reify_shadow_removals_preserves_user_custom_presets() {
		$core_presets = CBT_Theme_Settings_Save::get_core_shadow_presets();
		if ( empty( $core_presets ) ) {
			$this->markTestSkipped( 'WP core does not expose shadow defaults in this environment.' );
		}
		$user_custom = array(
			'slug'   => 'my-custom-shadow',
			'name'   => 'Mine',
			'shadow' => '0 0 5px #000',
		);
		$current     = array(
			'settings' => array(
				'shadow' => array( 'presets' => array( $user_custom ) ),
			),
		);
		$out         = CBT_Theme_Settings_Save::reify_shadow_removals( $current, array( $core_presets[0]['slug'] ) );
		$slugs       = array_column( $out['settings']['shadow']['presets'], 'slug' );
		$this->assertContains( 'my-custom-shadow', $slugs );
	}

	/* ---------------------------------------------------------------- *
	 * sanitize() — context-aware (CSS values must round-trip)
	 * ---------------------------------------------------------------- */

	public function test_sanitize_preserves_complex_shadow_value() {
		$shadow = '6px 6px 0px -3px rgb(255, 255, 255), 6px 6px rgb(0, 0, 0)';
		$out    = CBT_Theme_Settings_Save::sanitize(
			array(
				'settings' => array(
					'shadow' => array(
						'presets' => array(
							array(
								'slug'   => 'outlined',
								'name'   => 'Outlined',
								'shadow' => $shadow,
							),
						),
					),
				),
			)
		);
		$this->assertSame( $shadow, $out['settings']['shadow']['presets'][0]['shadow'] );
	}

	public function test_sanitize_preserves_gradient_value() {
		$gradient = 'linear-gradient(135deg, rgb(6, 147, 227) 0%, rgb(155, 81, 224) 100%)';
		$out      = CBT_Theme_Settings_Save::sanitize(
			array(
				'settings' => array(
					'color' => array(
						'gradients' => array(
							array(
								'slug'     => 'vivid',
								'name'     => 'Vivid',
								'gradient' => $gradient,
							),
						),
					),
				),
			)
		);
		$this->assertSame( $gradient, $out['settings']['color']['gradients'][0]['gradient'] );
	}

	public function test_sanitize_post_types_entries_are_slug_normalized() {
		$out = CBT_Theme_Settings_Save::sanitize(
			array(
				'customTemplates' => array(
					array(
						'name'      => 'page-wide',
						'title'     => 'Wide Page',
						'postTypes' => array( 'Page', 'POST' ),
					),
				),
			)
		);
		// `sanitize_key` lowercases.
		$this->assertSame( array( 'page', 'post' ), $out['customTemplates'][0]['postTypes'] );
	}

	/* ---------------------------------------------------------------- *
	 * validate() — slug rejection
	 * ---------------------------------------------------------------- */

	public function test_validate_rejects_removed_shadow_slug_with_invalid_chars() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'removedShadowDefaults' => array( 'natural', 'has spaces' ) )
		);
		$this->assertWPError( $result );
		$this->assertSame( 'cbt_invalid_payload', $result->get_error_code() );
	}

	public function test_validate_rejects_custom_template_name_with_invalid_chars() {
		$result = CBT_Theme_Settings_Save::validate(
			array(
				'customTemplates' => array(
					array(
						'name'  => 'Has Caps',
						'title' => 'Caps Page',
					),
				),
			)
		);
		$this->assertWPError( $result );
	}

	public function test_validate_accepts_well_formed_slugs() {
		$payload = array(
			'removedShadowDefaults' => array( 'natural', 'sharp_2' ),
			'customTemplates'       => array(
				array(
					'name'  => 'page-wide-2',
					'title' => 'Wide Page',
				),
			),
		);
		$this->assertSame( $payload, CBT_Theme_Settings_Save::validate( $payload ) );
	}

	public function test_validate_rejects_custom_template_entry_missing_title() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'customTemplates' => array( array( 'name' => 'page-wide' ) ) )
		);
		$this->assertWPError( $result );
		$this->assertStringContainsString( 'title', $result->get_error_message() );
	}

	public function test_validate_rejects_template_part_entry_missing_area() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'templateParts' => array( array( 'name' => 'sidebar' ) ) )
		);
		$this->assertWPError( $result );
		$this->assertStringContainsString( 'area', $result->get_error_message() );
	}

	public function test_validate_rejects_entry_with_empty_required_key() {
		$result = CBT_Theme_Settings_Save::validate(
			array(
				'customTemplates' => array(
					array(
						'name'  => 'page-wide',
						'title' => '',
					),
				),
			)
		);
		$this->assertWPError( $result );
	}

	public function test_validate_rejects_template_part_missing_name() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'templateParts' => array( array( 'area' => 'header' ) ) )
		);
		$this->assertWPError( $result );
		$this->assertStringContainsString( 'name', $result->get_error_message() );
	}

	public function test_validate_accepts_complete_template_part_entry() {
		$payload = array(
			'templateParts' => array(
				array(
					'name' => 'sidebar',
					'area' => 'uncategorized',
				),
			),
		);
		$this->assertSame( $payload, CBT_Theme_Settings_Save::validate( $payload ) );
	}

	/* ---------------------------------------------------------------- *
	 * validate() — JSON shape (object vs list) enforcement
	 *
	 * PHP's `json_decode(..., true)` flattens `{}` and `[]` to the same empty
	 * array, but a non-empty JSON list passed where an object is expected
	 * (or vice versa) is detectable and should be rejected.
	 * ---------------------------------------------------------------- */

	public function test_validate_rejects_settings_passed_as_non_empty_list() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'settings' => array( array( 'foo' => 'bar' ) ) )
		);
		$this->assertWPError( $result );
		$this->assertStringContainsString( 'object', $result->get_error_message() );
	}

	public function test_validate_rejects_custom_templates_passed_as_object() {
		$result = CBT_Theme_Settings_Save::validate(
			array(
				'customTemplates' => array(
					'page-wide' => array(
						'name'  => 'page-wide',
						'title' => 'Wide Page',
					),
				),
			)
		);
		$this->assertWPError( $result );
		$this->assertStringContainsString( 'list', $result->get_error_message() );
	}

	public function test_validate_rejects_removed_shadow_defaults_passed_as_object() {
		$result = CBT_Theme_Settings_Save::validate(
			array( 'removedShadowDefaults' => array( 'natural' => true ) )
		);
		$this->assertWPError( $result );
	}

	public function test_validate_accepts_empty_arrays_for_either_shape() {
		// Empty arrays are intentionally permitted regardless of expected
		// shape — they're handled in merge() (no-op for object positions,
		// clear for list positions where the existing value is a list).
		$payload = array(
			'settings'              => array(),
			'customTemplates'       => array(),
			'templateParts'         => array(),
			'removedShadowDefaults' => array(),
		);
		$this->assertSame( $payload, CBT_Theme_Settings_Save::validate( $payload ) );
	}

	/* ---------------------------------------------------------------- *
	 * merge() — list normalization
	 * ---------------------------------------------------------------- */

	public function test_merge_writes_lists_with_sequential_keys() {
		// Routes a proper list payload through the `is_list`-true branch and
		// confirms it lands with sequential integer keys (i.e., emits as a
		// JSON list, not an object, when serialized).
		$current = array();
		$payload = array(
			'customTemplates' => array(
				array(
					'name'  => 'a',
					'title' => 'A',
				),
				array(
					'name'  => 'b',
					'title' => 'B',
				),
			),
		);
		$out     = CBT_Theme_Settings_Save::merge( $current, $payload );
		$this->assertSame( array( 0, 1 ), array_keys( $out['customTemplates'] ) );
	}

	/* ---------------------------------------------------------------- *
	 * run() — write-failure path
	 *
	 * Verified via integration: a hardened service should surface a write
	 * failure as WP_Error rather than returning the merged payload as if it
	 * had been persisted. Smoke tests the unhappy path of
	 * `CBT_Theme_JSON_Resolver::write_theme_file_contents`.
	 * ---------------------------------------------------------------- */

	public function test_run_returns_wp_error_on_write_failure() {
		// Create a temp theme directory with a theme.json, then make the
		// directory read-only so the atomic write (write-temp-then-rename)
		// can't create its `theme.json.tmp` sibling. The service must surface
		// the failure as WP_Error rather than reporting SUCCESS.
		$tmp_dir    = sys_get_temp_dir() . '/cbt-test-' . uniqid();
		$theme_json = $tmp_dir . '/theme.json';
		mkdir( $tmp_dir );
		file_put_contents( $theme_json, '{}' );
		chmod( $tmp_dir, 0555 );
		// Best-effort guard: skip if running as root (chmod is meaningless).
		if ( is_writable( $tmp_dir ) ) {
			chmod( $tmp_dir, 0755 );
			unlink( $theme_json );
			rmdir( $tmp_dir );
			$this->markTestSkipped( 'Cannot make directory read-only in this environment.' );
		}

		$filter = static function () use ( $tmp_dir ) {
			return $tmp_dir;
		};
		add_filter( 'stylesheet_directory', $filter );
		add_filter( 'template_directory', $filter );

		$result = CBT_Theme_Settings_Save::run(
			array( 'settings' => array( 'color' => array( 'custom' => true ) ) )
		);

		remove_filter( 'stylesheet_directory', $filter );
		remove_filter( 'template_directory', $filter );

		// Cleanup.
		chmod( $tmp_dir, 0755 );
		unlink( $theme_json );
		rmdir( $tmp_dir );

		$this->assertWPError( $result );
		// A read-only theme directory blocks both lockfile creation and the
		// atomic write. Accept either failure code; both are correct surfaces
		// for "the filesystem said no" and both protect against the original
		// silent-success bug.
		$this->assertContains(
			$result->get_error_code(),
			array( 'cbt_lock_failed', 'cbt_write_failed' )
		);
		$this->assertContains(
			(int) $result->get_error_data()['status'],
			array( 500, 503 )
		);
	}

	/* ---------------------------------------------------------------- *
	 * writer — encoding-failure path must not touch the existing file
	 * ---------------------------------------------------------------- */

	public function test_writer_does_not_overwrite_on_encoding_failure() {
		// Set up a temp theme directory with a known theme.json. Point the
		// resolver at it via the stylesheet_directory filter. Call the writer
		// with an unencodable payload (a PHP resource). The writer must
		// return false and leave the original theme.json byte-for-byte
		// untouched. Guards against the bug where wp_json_encode returns
		// false and we'd otherwise truncate the existing file or write an
		// empty string in its place.
		$tmp_dir       = sys_get_temp_dir() . '/cbt-encode-test-' . uniqid();
		$theme_json    = $tmp_dir . '/theme.json';
		$known_content = '{"version":3,"settings":{"existing":"data"}}';
		mkdir( $tmp_dir );
		file_put_contents( $theme_json, $known_content );

		$filter = static function () use ( $tmp_dir ) {
			return $tmp_dir;
		};
		add_filter( 'stylesheet_directory', $filter );
		add_filter( 'template_directory', $filter );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$resource = fopen( 'php://memory', 'r' );
		$payload  = array( 'unencodable' => $resource );
		$result   = CBT_Theme_JSON_Resolver::write_theme_file_contents( $payload );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $resource );

		remove_filter( 'stylesheet_directory', $filter );
		remove_filter( 'template_directory', $filter );

		$on_disk = file_get_contents( $theme_json );

		// Cleanup before asserts so the temp tree is removed even if asserts fail.
		unlink( $theme_json );
		rmdir( $tmp_dir );

		$this->assertFalse( $result, 'Writer should return false when wp_json_encode fails.' );
		$this->assertSame( $known_content, $on_disk, 'theme.json must be byte-for-byte unchanged on encode failure.' );
	}
}
