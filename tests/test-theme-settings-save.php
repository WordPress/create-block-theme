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
}
