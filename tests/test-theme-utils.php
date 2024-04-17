<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Utils extends WP_UnitTestCase {

	public function test_replace_namespace_in_pattern() {
		$pattern_string = '<?php
/**
 * Title: index
 * Slug: old-slug/index
 * Categories: hidden
 * Inserter: no
 */
?>
<!-- wp:template-part {"slug":"header-minimal","tagName":"header"} /-->
';

		$updated_pattern_string = Theme_Utils::replace_namespace( $pattern_string, 'old-slug', 'new-slug', 'Old Name', 'New Name' );
		$this->assertStringContainsString( 'Slug: new-slug/index', $updated_pattern_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_pattern_string );

	}
}
