<?php

require_once __DIR__ . '/base.php';

/**
 * Test the file_path method of the Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers Theme_Readme::write
 * @group readme
 */
class CBT_ThemeReadme_Write extends CBT_Theme_Readme_UnitTestCase {
	public function test_write() {
		$test_content = 'Test content abc123';
		Theme_Readme::write( $test_content );
		$result = Theme_Readme::get_content();

		$this->assertEquals( $test_content, $result, 'The content was not written to the file.' );
	}
}
