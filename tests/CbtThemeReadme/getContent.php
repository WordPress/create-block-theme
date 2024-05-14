<?php

require_once __DIR__ . '/base.php';

/**
 * Test the get_content method of the CBT_Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Readme::get_content
 * @group readme
 */
class CBT_ThemeReadme_GetContent extends CBT_Theme_Readme_UnitTestCase {
	public function test_get_content() {
		$result   = CBT_Theme_Readme::get_content();
		$expected = file_get_contents( CBT_Theme_Readme::file_path() );
		$this->assertEquals( $expected, $result );
	}
}
