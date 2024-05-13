<?php

require_once __DIR__ . '/base.php';

/**
 * Test the file_path method of the Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers Theme_Readme::file_path
 * @group readme
 */
class CBT_ThemeReadme_FilePath extends CBT_Theme_Readme_UnitTestCase {
	public function test_file_path() {
		$result   = Theme_Readme::file_path();
		$expected = get_stylesheet_directory() . '/readme.txt';
		$this->assertEquals( $expected, $result );

		$this->assertEquals( 'test-theme-readme', get_option( 'stylesheet' ) );
	}
}
