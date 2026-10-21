<?php
/**
 * @package Create_Block_Theme
 */
class Test_Resolver_Preset_Merge extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		if ( ! class_exists( 'CBT_Theme_JSON_Resolver' ) && function_exists( 'cbt_augment_resolver_with_utilities' ) ) {
			cbt_augment_resolver_with_utilities();
		}
	}

	public function test_merge_appends_child_presets_and_overrides() {
		$parent = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'base',
							'color' => '#ffffff',
							'name'  => 'Base',
						),
						array(
							'slug'  => 'contrast',
							'color' => '#000000',
							'name'  => 'Contrast',
						),
					),
				),
			),
		);

		$child = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'base',
							'color' => '#f0f0f0',
							'name'  => 'Custom Base',
						),
						array(
							'slug'  => 'accent',
							'color' => '#0073aa',
							'name'  => 'Accent',
						),
					),
				),
			),
		);

		$result  = CBT_Theme_JSON_Resolver::merge_parent_preset_arrays( $child, $parent );
		$palette = $result['settings']['color']['palette'];

		$this->assertCount( 3, $palette );
		$this->assertSame( array( 'base', 'contrast', 'accent' ), array_column( $palette, 'slug' ) );
		$this->assertSame( '#f0f0f0', $palette[0]['color'] );
		$this->assertSame( 'Custom Base', $palette[0]['name'] );
		$this->assertSame( '#000000', $palette[1]['color'] );
		$this->assertSame( '#0073aa', $palette[2]['color'] );
	}

	public function test_merge_empty_child_preset_clears() {
		$parent = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'base',
							'color' => '#ffffff',
							'name'  => 'Base',
						),
					),
				),
			),
		);

		$child = array(
			'settings' => array(
				'color' => array(
					'palette' => array(),
				),
			),
		);

		$result = CBT_Theme_JSON_Resolver::merge_parent_preset_arrays( $child, $parent );
		$this->assertSame( array(), $result['settings']['color']['palette'] );
	}

	public function test_merge_preserves_non_preset_deltas() {
		$parent = array(
			'settings' => array(
				'layout'  => array(
					'contentSize' => '645px',
					'wideSize'    => '1200px',
				),
				'spacing' => array(
					'units' => array( '%', 'px', 'em' ),
				),
			),
		);

		$child = array(
			'settings' => array(
				'layout'  => array(
					'contentSize' => '800px',
				),
				'spacing' => array(
					'units' => array( '%', 'rem' ),
				),
			),
		);

		$result = CBT_Theme_JSON_Resolver::merge_parent_preset_arrays( $child, $parent );

		$this->assertSame( array( 'contentSize' => '800px' ), $result['settings']['layout'] );
		$this->assertSame( array( '%', 'rem' ), $result['settings']['spacing']['units'] );
	}
}
