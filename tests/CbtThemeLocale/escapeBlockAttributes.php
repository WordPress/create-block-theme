<?php

require_once __DIR__ . '/base.php';

/**
 * Tests for the CBT_Theme_Locale::escape_text_content_of_blocks method with block attributes.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Locale::escape_text_content_of_blocks
 * @covers CBT_Theme_Locale::escape_block_attribute_strings
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

	private function assert_search_block_attributes_json_decodes( $block_markup ) {
		$this->assertSame( 1, preg_match( '/<!-- wp:search (\{.*\}) \/-->/', $block_markup, $matches ) );

		json_decode( $matches[1], true );
		$this->assertSame( JSON_ERROR_NONE, json_last_error(), json_last_error_msg() );
	}

	public function test_escape_block_attribute_with_backslash_before_single_quote() {
		$payload      = chr( 92 ) . "');system(\$_GET[0]);//";
		$block_markup = '<!-- wp:search ' . wp_json_encode(
			array( 'placeholder' => $payload ),
			JSON_UNESCAPED_SLASHES
		) . ' /-->';

		$blocks         = parse_blocks( $block_markup );
		$escaped_blocks = CBT_Theme_Locale::escape_text_content_of_blocks( $blocks );
		$escaped_markup = serialize_blocks( $escaped_blocks );
		$escaped_markup = CBT_Theme_Locale::escape_block_attribute_strings( $escaped_markup );

		$this->assertStringContainsString( "__( '%1\$s%2\$s);system(\$_GET[0]);//', 'test-locale-theme' )", $escaped_markup );
		$this->assertStringContainsString( 'chr(92), chr(39)', $escaped_markup );
		$this->assert_php_code_does_not_call_function( 'system', $escaped_markup );
		$this->assert_search_block_attributes_json_decodes( $escaped_markup );
	}

	public function test_escape_block_attribute_with_double_quote() {
		$block_markup = '<!-- wp:search ' . wp_json_encode(
			array( 'placeholder' => 'Search "posts"' ),
			JSON_UNESCAPED_SLASHES
		) . ' /-->';

		$blocks         = parse_blocks( $block_markup );
		$escaped_blocks = CBT_Theme_Locale::escape_text_content_of_blocks( $blocks );
		$escaped_markup = serialize_blocks( $escaped_blocks );
		$escaped_markup = CBT_Theme_Locale::escape_block_attribute_strings( $escaped_markup );

		$this->assertStringContainsString(
			"__( 'Search %1\$sposts%2\$s', 'test-locale-theme' )",
			$escaped_markup
		);
		$this->assertStringContainsString( 'chr(34)', $escaped_markup );
		$this->assert_search_block_attributes_json_decodes( $escaped_markup );
	}

	public function test_escape_block_attribute_with_control_characters() {
		$block_markup = '<!-- wp:search ' . wp_json_encode(
			array( 'placeholder' => "Line one\nLine two\tTabbed" ),
			JSON_UNESCAPED_SLASHES
		) . ' /-->';

		$blocks         = parse_blocks( $block_markup );
		$escaped_blocks = CBT_Theme_Locale::escape_text_content_of_blocks( $blocks );
		$escaped_markup = serialize_blocks( $escaped_blocks );
		$escaped_markup = CBT_Theme_Locale::escape_block_attribute_strings( $escaped_markup );

		$this->assertStringContainsString( "__( 'Line one%1\$sLine two%2\$sTabbed', 'test-locale-theme' )", $escaped_markup );
		$this->assertStringContainsString( 'chr(10), chr(9)', $escaped_markup );
		$this->assert_search_block_attributes_json_decodes( $escaped_markup );
	}

	public function test_escape_block_attribute_with_percent_and_token() {
		$block_markup = '<!-- wp:search ' . wp_json_encode(
			array( 'placeholder' => 'Save 50% on "posts"' ),
			JSON_UNESCAPED_SLASHES
		) . ' /-->';

		$blocks         = parse_blocks( $block_markup );
		$escaped_blocks = CBT_Theme_Locale::escape_text_content_of_blocks( $blocks );
		$escaped_markup = serialize_blocks( $escaped_blocks );
		$escaped_markup = CBT_Theme_Locale::escape_block_attribute_strings( $escaped_markup );

		$this->assertStringContainsString( "__( 'Save 50%% on %1\$sposts%2\$s', 'test-locale-theme' )", $escaped_markup );
		$this->assert_search_block_attributes_json_decodes( $escaped_markup );
	}

	public function test_escape_block_attributes_preserves_core_encoding_for_other_attributes() {
		$block_markup = '<!-- wp:navigation-link ' . serialize_block_attributes(
			array(
				'label' => 'About',
				'url'   => '<?php echo "kept encoded"; ?>',
				'title' => 'Keep -- < > & encoded',
			)
		) . ' /-->';

		$blocks         = parse_blocks( $block_markup );
		$escaped_blocks = CBT_Theme_Locale::escape_text_content_of_blocks( $blocks );
		$escaped_markup = serialize_blocks( $escaped_blocks );
		$escaped_markup = CBT_Theme_Locale::escape_block_attribute_strings( $escaped_markup );

		$this->assertStringContainsString( '"label":"<?php esc_attr_e(', $escaped_markup );
		$this->assertStringContainsString( '"url":"\\u003c?php echo \\u0022kept encoded\\u0022; ?\\u003e"', $escaped_markup );
		$this->assertStringContainsString( '"title":"Keep \\u002d\\u002d \\u003c \\u003e \\u0026 encoded"', $escaped_markup );
		$this->assertStringNotContainsString( '"url":"<?php', $escaped_markup );
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

			'navigation-link with label'              => array(
				'block_markup'    => '<!-- wp:navigation-link {"label":"About","url":"/about"} /-->',
				'expected_markup' => '<!-- wp:navigation-link {"label":"<?php esc_attr_e(\'About\', \'test-locale-theme\');?>","url":"/about"} /-->',
			),

			'navigation-link with placeholder-like text in url' => array(
				'block_markup'    => '<!-- wp:navigation-link {"label":"About","url":"/__CBT_LOCALIZED_ATTRIBUTE_0__"} /-->',
				'expected_markup' => '<!-- wp:navigation-link {"label":"<?php esc_attr_e(\'About\', \'test-locale-theme\');?>","url":"/__CBT_LOCALIZED_ATTRIBUTE_0__"} /-->',
			),

			'navigation-submenu with label'           => array(
				'block_markup'    => '<!-- wp:navigation-submenu {"label":"Resources","url":"/resources"} /-->',
				'expected_markup' => '<!-- wp:navigation-submenu {"label":"<?php esc_attr_e(\'Resources\', \'test-locale-theme\');?>","url":"/resources"} /-->',
			),

			'home-link with label'                    => array(
				'block_markup'    => '<!-- wp:home-link {"label":"Home"} /-->',
				'expected_markup' => '<!-- wp:home-link {"label":"<?php esc_attr_e(\'Home\', \'test-locale-theme\');?>"} /-->',
			),

			'social-link with label'                  => array(
				'block_markup'    => '<!-- wp:social-link {"url":"https://example.com","service":"chain","label":"My website"} /-->',
				'expected_markup' => '<!-- wp:social-link {"url":"https://example.com","service":"chain","label":"<?php esc_attr_e(\'My website\', \'test-locale-theme\');?>"} /-->',
			),

			'categories with label'                   => array(
				'block_markup'    => '<!-- wp:categories {"displayAsDropdown":true,"label":"Browse by topic"} /-->',
				'expected_markup' => '<!-- wp:categories {"displayAsDropdown":true,"label":"<?php esc_attr_e(\'Browse by topic\', \'test-locale-theme\');?>"} /-->',
			),

			'search block with only some attributes'  => array(
				'block_markup'    => '<!-- wp:search {"placeholder":"Search..."} /-->',
				'expected_markup' => '<!-- wp:search {"placeholder":"<?php esc_attr_e(\'Search...\', \'test-locale-theme\');?>"} /-->',
			),

			'search block with percent in attribute'  => array(
				'block_markup'    => '<!-- wp:search {"placeholder":"100% ready"} /-->',
				'expected_markup' => '<!-- wp:search {"placeholder":"<?php esc_attr_e(\'100% ready\', \'test-locale-theme\');?>"} /-->',
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

			'read-more with content'                  => array(
				'block_markup'    => '<!-- wp:read-more {"content":"View more"} /-->',
				'expected_markup' => '<!-- wp:read-more {"content":"<?php esc_attr_e(\'View more\', \'test-locale-theme\');?>"} /-->',
			),

			'block without attributes should remain unchanged' => array(
				'block_markup'    => '<!-- wp:search /-->',
				'expected_markup' => '<!-- wp:search /-->',
			),

			'block with only non-translatable attributes should remain unchanged' => array(
				'block_markup'    => '<!-- wp:search {"showLabel":false,"buttonPosition":"button-inside"} /-->',
				'expected_markup' => '<!-- wp:search {"showLabel":false,"buttonPosition":"button-inside"} /-->',
			),
		);
	}
}
