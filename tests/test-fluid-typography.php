<?php
/**
 * @package Create_Block_Theme
 * @group fluid-typography
 */
class Test_Create_Block_Theme_Fluid_Typography extends WP_UnitTestCase {

	/**
	 * Test that fluid typography sizes are synced correctly
	 */
	public function test_sync_fluid_typography_sizes() {
		$data = array(
			'settings' => array(
				'typography' => array(
					'fontSizes' => array(
						array(
							'slug'  => 'custom-fluid',
							'name'  => 'Custom Fluid',
							'fluid' => array(
								'min' => '1.5rem',
								'max' => '5.63rem',
							),
							'size'  => '3rem', // Old value, should be updated
						),
						array(
							'slug' => 'normal',
							'name' => 'Normal',
							'size' => '1rem', // No fluid, should remain unchanged
						),
					),
				),
			),
		);

		$result = CBT_Theme_Fluid_Typography::sync_fluid_typography_sizes( $data );

		// Verify the fluid size was synced
		$this->assertEquals(
			'5.63rem',
			$result['settings']['typography']['fontSizes'][0]['size'],
			'Fluid typography size should be synced with fluid.max'
		);

		// Verify the non-fluid size remained unchanged
		$this->assertEquals(
			'1rem',
			$result['settings']['typography']['fontSizes'][1]['size'],
			'Non-fluid typography size should remain unchanged'
		);
	}

	/**
	 * Test that global styles filter works correctly
	 */
	public function test_sync_global_styles_fluid_typography() {
		$prepared_post                = new stdClass();
		$prepared_post->post_content = wp_json_encode(
			array(
				'settings' => array(
					'typography' => array(
						'fontSizes' => array(
							array(
								'slug'  => 'custom-fluid',
								'name'  => 'Custom Fluid',
								'fluid' => array(
									'min' => '1.5rem',
									'max' => '5.63rem',
								),
								'size'  => '3rem',
							),
						),
					),
				),
			)
		);

		$result = CBT_Theme_Fluid_Typography::sync_global_styles_fluid_typography( $prepared_post );

		$data = json_decode( $result->post_content, true );

		$this->assertEquals(
			'5.63rem',
			$data['settings']['typography']['fontSizes'][0]['size'],
			'Global styles should have synced fluid typography sizes'
		);
	}

	/**
	 * Test that missing typography doesn't cause errors
	 */
	public function test_sync_with_no_typography() {
		$data = array(
			'settings' => array(),
		);

		$result = CBT_Theme_Fluid_Typography::sync_fluid_typography_sizes( $data );

		$this->assertArrayNotHasKey( 'typography', $result['settings'] );
		$this->assertEqualsCanonicalizing( array(), $result['settings'] );
	}

	/**
	 * Test that missing fontSizes doesn't cause errors
	 */
	public function test_sync_with_no_font_sizes() {
		$data = array(
			'settings' => array(
				'typography' => array(),
			),
		);

		$result = CBT_Theme_Fluid_Typography::sync_fluid_typography_sizes( $data );

		$this->assertArrayNotHasKey( 'fontSizes', $result['settings']['typography'] );
	}

	/**
	 * Test that invalid JSON in global styles filter is handled gracefully
	 */
	public function test_sync_global_styles_with_invalid_json() {
		$prepared_post                = new stdClass();
		$prepared_post->post_content = 'invalid json';

		$result = CBT_Theme_Fluid_Typography::sync_global_styles_fluid_typography( $prepared_post );

		// Should return the prepared post unchanged
		$this->assertEquals( 'invalid json', $result->post_content );
	}
}
