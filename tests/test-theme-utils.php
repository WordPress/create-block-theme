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
 * Inserter: no
 */
?>
<!-- wp:template-part {"slug":"header-minimal","tagName":"header"} /-->
';

		$updated_pattern_string = CBT_Theme_Utils::replace_namespace( $pattern_string, 'old-slug', 'new-slug', 'Old Name', 'New Name' );
		$this->assertStringContainsString( 'Slug: new-slug/index', $updated_pattern_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_pattern_string );

	}

	public function test_replace_namespace_in_code() {
		$code_string = "<?php
/**
 * old-slug functions and definitions
 *
 * @package old-slug
 * @since old-slug 1.0
 */

if ( ! function_exists( 'old_slug_support' ) ) :

	function old_slug_support() {
";

		$updated_code_string = CBT_Theme_Utils::replace_namespace( $code_string, 'old-slug', 'new-slug', 'Old Name', 'New Name' );
		$this->assertStringContainsString( '@package new-slug', $updated_code_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_code_string );
		$this->assertStringContainsString( 'function new_slug_support', $updated_code_string );
		$this->assertStringContainsString( "function_exists( 'new_slug_support' )", $updated_code_string );
	}

	public function test_replace_namespace_in_code_with_single_word_slug() {
		$code_string = "<?php
/**
 * oldslug functions and definitions
 *
 * @package oldslug
 * @since oldslug 1.0
 */

if ( ! function_exists( 'oldslug_support' ) ) :

	function oldslug_support() {
";

		$updated_code_string = CBT_Theme_Utils::replace_namespace( $code_string, 'oldslug', sanitize_title( 'New Slug' ), 'OldSlug', 'New Slug' );
		$this->assertStringContainsString( '@package new-slug', $updated_code_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_code_string );
		$this->assertStringContainsString( 'function new_slug_support', $updated_code_string );
		$this->assertStringContainsString( "function_exists( 'new_slug_support' )", $updated_code_string );
	}
}
