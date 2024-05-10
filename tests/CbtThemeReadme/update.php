<?php

require_once __DIR__ . '/base.php';

/**
 * Test the update method of the Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers Theme_Readme::update
 * @group readme
 */
class CBT_ThemeReadme_Update extends CBT_Theme_Readme_UnitTestCase {

	/**
	 * @dataProvider data_test_update
	 */
	public function test_update( $data ) {
		$readme = Theme_Readme::update( $data );

		// Removes the newlines from the readme content to make it easier to search for strings.
		$readme_without_newlines = str_replace( "\n", '', $readme );

		$expected_author              = 'Contributors: ' . $data['author'];
		$expected_wp_version          = 'Tested up to: ' . $data['wp_version'] ?? get_bloginfo( 'version' );
		$expected_image_credits       = '== Images ==' . $data['image_credits'];
		$expected_recommended_plugins = '== Recommended Plugins ==' . $data['recommended_plugins'];

		$this->assertStringContainsString( $expected_author, $readme_without_newlines, 'The expected author is missing.' );
		$this->assertStringContainsString( $expected_wp_version, $readme_without_newlines, 'The expected WP version is missing.' );
		$this->assertStringContainsString( $expected_image_credits, $readme_without_newlines, 'The expected image credits are missing.' );
		$this->assertStringContainsString( $expected_recommended_plugins, $readme_without_newlines, 'The expected recommended plugins are missing.' );
	}

	public function data_test_update() {
		return array(
			'complete data' => array(
				'data' => array(
					'description'         => 'New theme description',
					'author'              => 'New theme author',
					'wp_version'          => '12.12',
					'image_credits'       => 'New image credits',
					'recommended_plugins' => 'New recommended plugins',
				),
			),
			// TODO: Add more test cases.
		);
	}
}
