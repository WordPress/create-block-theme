<?php
/**
 * Class WP_Create_Block_Theme_Admin
 *
 * @package Create_Block_Theme
 */
class Create_Block_Theme_AdminTest extends WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();
		$this->class_instance = new Create_Block_Theme_Admin();
	}

	/**
	 * Test if the class exists.
	 */
	public function test_theme_instance() {
		$class_name = get_class( $this->class_instance->theme );
		$expected   = 'WP_Theme';

		$this->assertEquals( $expected, $class_name );
	}

	/**
	 * Test if the export_child_theme correctly creates a child theme.
	 */
	public function test_export_child_theme() {
		$create_block_theme_admin = $this->getMockBuilder( 'Create_Block_Theme_Admin' )
		->setMethods( array( 'download_file' ) )
			->getMock();

		// Stub download_file method to avoid sending headers.
		$create_block_theme_admin->method( 'download_file' )
		->willReturn( true );

		$theme_name                = 'twentytwentythree';
		$expected_child_theme_name = 'twentytwentythree-child';

		switch_theme( $theme_name );
		$current_theme = wp_get_theme();

		$filename = $create_block_theme_admin->export_child_theme( $current_theme );

		// check that the zip file exists in the temp dir.
		$this->assertFileExists( $filename );

		// check that it contains a valid WordPress theme.
		$zip = new ZipArchive();
		$zip->open( $filename );
		$zip->extractTo( get_theme_root() . '/' . $expected_child_theme_name );
		$zip->close();

		$child_theme = wp_get_theme( $expected_child_theme_name );
		$this->assertTrue( $child_theme->exists() );
	}
}
