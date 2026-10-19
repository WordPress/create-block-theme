<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Tags extends WP_UnitTestCase {

	/**
	 * Tests that normalize_tags converts display labels to slugs, lowercases custom tags,
	 * handles comma-separated strings as well as arrays, trims whitespace, and deduplicates.
	 */
	public function test_normalize_tags() {
		$tags = array(
			'Blog',
			'blog',
			'  Block Editor Styles  ',
			'Site Editor',
			'RTL-Language-Support',
			'translation-ready',
			'Food & Drink',
			'',
			'   ',
		);

		$normalized = CBT_Theme_Tags::normalize_tags( $tags );

		$this->assertSame(
			array(
				'blog',
				'block-styles',
				'full-site-editing',
				'rtl-language-support',
				'translation-ready',
				'food-and-drink',
			),
			$normalized
		);

		// Also verify comma-separated string input.
		$string_input = 'Blog, Block Editor Styles, custom-tag';
		$this->assertSame(
			array(
				'blog',
				'block-styles',
				'custom-tag',
			),
			CBT_Theme_Tags::normalize_tags( $string_input )
		);
	}

	/**
	 * Tests that theme_tags_list merges checkbox tags and custom tags, normalizes them,
	 * removes duplicates, and formats the output as a comma-separated string.
	 */
	public function test_theme_tags_list() {
		$theme = array(
			'tags-subject'  => array( 'Blog' ),
			'tags-features' => array( 'Block Editor Styles' ),
			'tags_custom'   => 'blog, RTL-Language-Support, translation-ready, Site Editor',
		);

		$tags_list = CBT_Theme_Tags::theme_tags_list( $theme );

		$this->assertSame(
			'blog, block-styles, rtl-language-support, translation-ready, full-site-editing',
			$tags_list
		);
	}
}
