<?php

require_once __DIR__ . '/base.php';

/**
 * Test the update method of the CBT_Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Readme::update
 * @group readme
 */
class CBT_ThemeReadme_Update extends CBT_Theme_Readme_UnitTestCase {

	/**
	 * @dataProvider data_test_update
	 */
	public function test_update( $data ) {
		$readme_content = CBT_Theme_Readme::get_content();
		$readme         = CBT_Theme_Readme::update( $data, $readme_content );

		// Check sanitazion before altering the content.
		$this->assertStringNotContainsString( "\r\n", $readme, 'The readme content contains DOS newlines.' );

		// Removes the newlines from the readme content to make it easier to search for strings.
		$readme_without_newlines = $this->remove_newlines( $readme );

		$expected_author              = 'Contributors: ' . $data['author'];
		$expected_wp_version          = 'Tested up to: ' . $data['wp_version'] ?? CBT_Theme_Utils::get_current_wordpress_version();
		$expected_image_credits       = '== Images ==' . $this->remove_newlines( $data['image_credits'] );
		$expected_recommended_plugins = '== Recommended Plugins ==' . $this->remove_newlines( $data['recommended_plugins'] );

		$this->assertStringContainsString( $expected_author, $readme_without_newlines, 'The expected author is missing.' );
		$this->assertStringContainsString( $expected_wp_version, $readme_without_newlines, 'The expected WP version is missing.' );
		$this->assertStringContainsString( $expected_image_credits, $readme_without_newlines, 'The expected image credits are missing.' );
		$this->assertStringContainsString( $expected_recommended_plugins, $readme_without_newlines, 'The expected recommended plugins are missing.' );

		// Assertion specific to font credits.
		if ( isset( $data['font_credits'] ) ) {
			$expected_font_credits = '== Fonts ==' . $this->remove_newlines( $data['font_credits'] );
			$this->assertStringContainsString( $expected_font_credits, $readme_without_newlines, 'The expected font credits are missing.' );
		}
	}

	public function data_test_update() {
		return array(
			'complete data'        => array(
				'data' => array(
					'description'         => 'New theme description',
					'author'              => 'New theme author',
					'wp_version'          => '12.12',
					'requires_wp'         => '',
					'image_credits'       => 'New image credits',
					'recommended_plugins' => 'New recommended plugins',
					'font_credits'        => 'Example font credits text',
				),
			),
			'missing font credits' => array(
				'data' => array(
					'description'         => 'New theme description',
					'author'              => 'New theme author',
					'wp_version'          => '12.12',
					'requires_wp'         => '',
					'image_credits'       => 'New image credits',
					'recommended_plugins' => 'New recommended plugins',
				),
			),
			/*
			 * This string contains DOS newlines.
			 * It uses double quotes to make PHP interpret the newlines as newlines and not as string literals.
			 */
			'Remove DOS newlines'  => array(
				'data' => array(
					'description'         => 'New theme description',
					'author'              => 'New theme author',
					'wp_version'          => '12.12',
					'requires_wp'         => '',
					'image_credits'       => "New image credits \r\n New image credits 2",
					'recommended_plugins' => "Plugin1 \r\n Plugin2 \r\n Plugin3",
					'font_credits'        => "Font1 \r\n Font2 \r\n Font3",
				),
			),
		);
	}
}
