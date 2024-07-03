<?php

require_once __DIR__ . '/base.php';

/**
 * Tests for the CBT_Theme_Locale::escape_string method.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Locale::escape_string
 * @group locale
 */
class CBT_Theme_Locale_EscapeString extends CBT_Theme_Locale_UnitTestCase {
	public function test_escape_string() {
		$string         = 'This is a test text.';
		$escaped_string = CBT_Theme_Locale::escape_string( $string );
		$this->assertEquals( "<?php echo wp_kses_post( __('This is a test text.', 'test-locale-theme') );?>", $escaped_string );
	}

	public function test_escape_string_with_single_quote() {
		$string         = "This is a test text with a single quote '";
		$escaped_string = CBT_Theme_Locale::escape_string( $string );
		$this->assertEquals( "<?php echo wp_kses_post( __('This is a test text with a single quote \\'', 'test-locale-theme') );?>", $escaped_string );
	}

	public function test_escape_string_with_double_quote() {
		$string         = 'This is a test text with a double quote "';
		$escaped_string = CBT_Theme_Locale::escape_string( $string );
		$this->assertEquals( "<?php echo wp_kses_post( __('This is a test text with a double quote \"', 'test-locale-theme') );?>", $escaped_string );
	}

	public function test_escape_string_with_html() {
		$string         = '<p>This is a test text with HTML.</p>';
		$escaped_string = CBT_Theme_Locale::escape_string( $string );
		$this->assertEquals( "<?php echo wp_kses_post( __('<p>This is a test text with HTML.</p>', 'test-locale-theme') );?>", $escaped_string );
	}

	public function test_escape_string_with_already_escaped_string() {
		$string         = "<?php echo wp_kses_post( __('This is a test text.', 'test-locale-theme') );?>";
		$escaped_string = CBT_Theme_Locale::escape_string( $string );
		$this->assertEquals( $string, $escaped_string );
	}

	public function test_escape_string_with_non_string() {
		$string         = null;
		$escaped_string = CBT_Theme_Locale::escape_string( $string );
		$this->assertEquals( $string, $escaped_string );
	}
}

