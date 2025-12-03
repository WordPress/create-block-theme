<?php

require_once __DIR__ . '/base.php';

/**
 * Tests for the CBT_Theme_Locale::escape_text_content_of_blocks method with block attributes.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Locale::escape_text_content_of_blocks
 * @group locale
 */
class CBT_Theme_Locale_EscapeBlockAttributes extends CBT_Theme_Locale_UnitTestCase {

	/**
	 * @dataProvider data_test_escape_block_attributes
	 */
	public function test_escape_block_attributes( $block_markup, $expected_markup ) {
		// Parse the block markup.
		$blocks = parse_blocks( $block_markup );
		// Escape the text content of the blocks.
		$escaped_blocks = CBT_Theme_Locale::escape_text_content_of_blocks( $blocks );
		// Serialize the blocks to get the markup.
		$escaped_markup = serialize_blocks( $escaped_blocks );
		// Process block attributes after serialization to avoid JSON encoding of PHP tags.
		$escaped_markup = CBT_Theme_Locale::escape_block_attribute_strings( $escaped_markup );

		$this->assertEquals( $expected_markup, $escaped_markup, 'The markup result is not as the expected one.' );
	}

	public function data_test_escape_block_attributes() {
		return array(

			'search block with label, placeholder, and buttonText' => array(
				'block_markup'    => '<!-- wp:search {"label":"Search","placeholder":"Type here...","buttonText":"Search"} /-->',
				'expected_markup' => '<!-- wp:search {"label":"<?php esc_attr_e(\'Search\', \'test-locale-theme\');?>","placeholder":"<?php esc_attr_e(\'Type here...\', \'test-locale-theme\');?>","buttonText":"<?php esc_attr_e(\'Search\', \'test-locale-theme\');?>"} /-->',
			),

			'query-pagination-previous with label'    => array(
				'block_markup'    => '<!-- wp:query-pagination-previous {"label":"Previous"} /-->',
				'expected_markup' => '<!-- wp:query-pagination-previous {"label":"<?php esc_attr_e(\'Previous\', \'test-locale-theme\');?>"} /-->',
			),

			'query-pagination-next with label'        => array(
				'block_markup'    => '<!-- wp:query-pagination-next {"label":"Next"} /-->',
				'expected_markup' => '<!-- wp:query-pagination-next {"label":"<?php esc_attr_e(\'Next\', \'test-locale-theme\');?>"} /-->',
			),

			'comments-pagination-next with label'     => array(
				'block_markup'    => '<!-- wp:comments-pagination-next {"label":"⪻ newer"} /-->',
				'expected_markup' => '<!-- wp:comments-pagination-next {"label":"<?php esc_attr_e(\'⪻ newer\', \'test-locale-theme\');?>"} /-->',
			),

			'comments-pagination-previous with label' => array(
				'block_markup'    => '<!-- wp:comments-pagination-previous {"label":"older ⪼"} /-->',
				'expected_markup' => '<!-- wp:comments-pagination-previous {"label":"<?php esc_attr_e(\'older ⪼\', \'test-locale-theme\');?>"} /-->',
			),

			'post-excerpt with moreText'              => array(
				'block_markup'    => '<!-- wp:post-excerpt {"moreText":"— read more","showMoreOnNewLine":false} /-->',
				'expected_markup' => '<!-- wp:post-excerpt {"moreText":"<?php esc_attr_e(\'— read more\', \'test-locale-theme\');?>","showMoreOnNewLine":false} /-->',
			),

			'post-navigation-link with label'         => array(
				'block_markup'    => '<!-- wp:post-navigation-link {"label":"Custom Label"} /-->',
				'expected_markup' => '<!-- wp:post-navigation-link {"label":"<?php esc_attr_e(\'Custom Label\', \'test-locale-theme\');?>"} /-->',
			),

			'search block with only some attributes'  => array(
				'block_markup'    => '<!-- wp:search {"placeholder":"Search..."} /-->',
				'expected_markup' => '<!-- wp:search {"placeholder":"<?php esc_attr_e(\'Search...\', \'test-locale-theme\');?>"} /-->',
			),

			'query pagination blocks in context'      => array(
				'block_markup'    => '<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous {"label":"Previous"} /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next {"label":"Next"} /-->
<!-- /wp:query-pagination -->',
				'expected_markup' => '<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous {"label":"<?php esc_attr_e(\'Previous\', \'test-locale-theme\');?>"} /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next {"label":"<?php esc_attr_e(\'Next\', \'test-locale-theme\');?>"} /-->
<!-- /wp:query-pagination -->',
			),
		);
	}
}
