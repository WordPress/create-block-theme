<?php

require_once __DIR__ . '/base.php';

/**
 * Test the add_or_update_section method of the CBT_Theme_Readme class.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Readme::add_or_update_section
 * @group readme
 *
 */
class CBT_ThemeReadme_AddOrUpdateSection extends CBT_Theme_Readme_UnitTestCase {
	public function test_add_or_update_section() {
		$section_title   = 'Test Section';
		$section_content = 'Test content abc123';

		// Add a new section.
		$readme = CBT_Theme_Readme::add_or_update_section( $section_title, $section_content );

		// Check if the section was added.
		$this->assertStringContainsString( $section_title, $readme, 'The section title is missing.' );
		$this->assertStringContainsString( $section_content, $readme, 'The section content is missing' );

		// Update the section.
		$section_content_updated = 'Updated content xyz890';

		$readme = CBT_Theme_Readme::add_or_update_section( $section_title, $section_content_updated );

		// Check if the old content was updated.
		$this->assertStringNotContainsString( $section_content, $readme, 'The old content is still present.' );

		// Check if the new content was added.
		$this->assertStringContainsString( $section_title, $readme, 'The section title is missing.' );
		$this->assertStringContainsString( $section_content_updated, $readme, 'The updated content is missing.' );

		// Check if that the section title was added only once.
		$section_count = substr_count( $readme, $section_title );
		$this->assertEquals( 1, $section_count, 'The section title was added more than once.' );
	}

	public function test_add_or_update_section_with_no_content() {
		$section_title   = 'Test Section';
		$section_content = '';

		// Empty section should not be added.
		$readme = CBT_Theme_Readme::add_or_update_section( $section_title, $section_content );
		$this->assertStringNotContainsString( $section_title, $readme, 'The title of an empty section should not be added.' );
	}
}
