<?php

require_once __DIR__ . '/base.php';

/**
 * Test the create method of the CBT_Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Readme::create
 * @group readme
 */
class CBT_ThemeReadme_Create extends CBT_Theme_Readme_UnitTestCase {

	/**
	 * @dataProvider data_test_create
	 */
	public function test_create( $data ) {
		$readme = CBT_Theme_Readme::create( $data );

		// Removes the newlines from the readme content to make it easier to search for strings.
		$readme_without_newlines = str_replace( "\n", '', $readme );

		$expected_name                = '== ' . $data['name'] . ' ==';
		$expected_description         = '== Description ==' . $data['description'];
		$expected_uri                 = 'Theme URI: ' . $data['uri'];
		$expected_author              = 'Contributors: ' . $data['author'];
		$expected_author_uri          = 'Author URI: ' . $data['author_uri'];
		$expected_wp_version          = 'Tested up to: ' . $data['wp_version'] ?? CBT_Theme_Utils::get_current_wordpress_version();
		$expected_php_version         = 'Requires PHP: ' . $data['required_php_version'];
		$expected_license             = 'License: ' . $data['license'];
		$expected_license_uri         = 'License URI: ' . $data['license_uri'];
		$expected_image_credits       = '== Images ==' . $data['image_credits'];
		$expected_recommended_plugins = '== Recommended Plugins ==' . $data['recommended_plugins'];

		$this->assertStringContainsString( $expected_name, $readme_without_newlines, 'The expected name is missing.' );
		$this->assertStringContainsString( $expected_author, $readme_without_newlines, 'The expected author is missing.' );
		$this->assertStringContainsString( $expected_wp_version, $readme_without_newlines, 'The expected WP version is missing.' );
		$this->assertStringContainsString( $expected_image_credits, $readme_without_newlines, 'The expected image credits are missing.' );
		$this->assertStringContainsString( $expected_recommended_plugins, $readme_without_newlines, 'The expected recommended plugins are missing.' );

		// Assetion specific to child themes.
		if ( isset( $data['is_child_theme'] ) && $data['is_child_theme'] ) {
			$this->assertStringContainsString(
				$data['name'] . ' is a child theme of Test Readme Theme (https://example.org/themes/test-readme-theme), (C) the WordPress team, [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)',
				$readme_without_newlines,
				'The expected reference to the parent theme is missing.'
			);
		}

		// Assetion specific to child themes.
		if ( isset( $data['is_cloned_theme'] ) && $data['is_cloned_theme'] ) {
			$this->assertStringContainsString(
				$data['name'] . ' is based on Test Readme Theme (https://example.org/themes/test-readme-theme), (C) the WordPress team, [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)',
				$readme_without_newlines,
				'The expected reference to the parent theme is missing.'
			);
		}

		// Assertion specific to font credits.
		if ( isset( $data['font_credits'] ) ) {
			$expected_font_credits = '== Fonts ==' . $data['font_credits'];
			$this->assertStringContainsString( $expected_font_credits, $readme_without_newlines, 'The expected font credits are missing.' );
		}
	}

	public function data_test_create() {
		return array(
			'complete data for a nomal theme'  => array(
				'data' => array(
					'name'                 => 'My Theme',
					'description'          => 'New theme description',
					'uri'                  => 'https://example.com',
					'author'               => 'New theme author',
					'author_uri'           => 'https://example.com/author',
					'copyright_year'       => '2077',
					'wp_version'           => '12.12',
					'required_php_version' => '10.0',
					'license'              => 'GPLv2 or later',
					'license_uri'          => 'https://www.gnu.org/licenses/gpl-2.0.html',
					'image_credits'        => 'The images were taken from https://example.org and have a CC0 license.',
					'recommended_plugins'  => 'The theme is best used with the following plugins: Plugin 1, Plugin 2, Plugin 3.',
					'font_credits'         => 'Font credit example text',
				),
			),
			'complete data for a child theme'  => array(
				'data' => array(
					'name'                 => 'My Child Theme',
					'description'          => 'New child theme description',
					'uri'                  => 'https://example.com',
					'author'               => 'New theme author',
					'author_uri'           => 'https://example.com/author',
					'copyright_year'       => '2078',
					'wp_version'           => '13.13',
					'required_php_version' => '11.0',
					'license'              => 'GPLv2 or later',
					'license_uri'          => 'https://www.gnu.org/licenses/gpl-2.0.html',
					'image_credits'        => 'The images were taken from https://example.org and have a CC0 license.',
					'recommended_plugins'  => 'The theme is best used with the following plugins: Plugin 1, Plugin 2, Plugin 3.',
					'is_child_theme'       => true,
					'font_credits'         => 'Font credit example text',
				),
			),
			'complete data for a cloned theme' => array(
				'data' => array(
					'name'                 => 'My Cloned Theme',
					'description'          => 'New cloned theme description',
					'uri'                  => 'https://example.com',
					'author'               => 'New theme author',
					'author_uri'           => 'https://example.com/author',
					'copyright_year'       => '2079',
					'wp_version'           => '14.14',
					'required_php_version' => '12.0',
					'license'              => 'GPLv2 or later',
					'license_uri'          => 'https://www.gnu.org/licenses/gpl-2.0.html',
					'image_credits'        => 'The images were taken from https://example.org and have a CC0 license.',
					'recommended_plugins'  => 'The theme is best used with the following plugins: Plugin 1, Plugin 2, Plugin 3.',
					'is_cloned_theme'      => true,
					'font_credits'         => 'Font credit example text',
				),
			),
			'missing font credits'             => array(
				'data' => array(
					'name'                 => 'My Theme',
					'description'          => 'New theme description',
					'uri'                  => 'https://example.com',
					'author'               => 'New theme author',
					'author_uri'           => 'https://example.com/author',
					'copyright_year'       => '2077',
					'wp_version'           => '12.12',
					'required_php_version' => '10.0',
					'license'              => 'GPLv2 or later',
					'license_uri'          => 'https://www.gnu.org/licenses/gpl-2.0.html',
					'image_credits'        => 'The images were taken from https://example.org and have a CC0 license.',
					'recommended_plugins'  => 'The theme is best used with the following plugins: Plugin 1, Plugin 2, Plugin 3.',
				),
			),
			// TODO: Add more test cases.
		);
	}
}
