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

	public function test_stringify_strips_null_values() {
		$data = array(
			'version' => 3,
			'styles'  => array(
				'blocks' => array(
					'core/quote' => array(
						'border' => array(
							'color' => null,
							'style' => null,
							'width' => null,
							'left'  => array(
								'color' => 'currentColor',
								'style' => 'solid',
								'width' => '4px',
							),
						),
					),
				),
			),
		);

		$decoded = json_decode( CBT_Theme_JSON_Resolver::stringify( $data ), true );
		$this->assertSame(
			array(
				'left' => array(
					'color' => 'currentColor',
					'style' => 'solid',
					'width' => '4px',
				),
			),
			$decoded['styles']['blocks']['core/quote']['border']
		);
		$this->assertStringNotContainsString( 'null', CBT_Theme_JSON_Resolver::stringify( $data ) );
	}

	public function test_strip_null_values_preserves_falsy_values() {
		$data = array(
			'custom' => false,
			'zero'   => 0,
			'empty'  => '',
			'list'   => array(),
			'none'   => null,
		);

		$this->assertSame(
			array(
				'custom' => false,
				'zero'   => 0,
				'empty'  => '',
				'list'   => array(),
			),
			CBT_Theme_JSON_Resolver::strip_null_values( $data )
		);
	}
}
