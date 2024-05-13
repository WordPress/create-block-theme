<?php

require_once __DIR__ . '/base.php';

/**
 * Test the get_content method of the Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers Theme_Readme::get_content
 * @group readme
 */
class CBT_ThemeReadme_GetContent extends CBT_Theme_Readme_UnitTestCase {
	public function test_get_content() {
		$result   = Theme_Readme::get_content();
		$expected = file_get_contents( Theme_Readme::file_path() );
		$this->assertEquals( $expected, $result );
	}
}
