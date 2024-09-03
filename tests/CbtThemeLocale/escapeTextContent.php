<?php

require_once __DIR__ . '/base.php';

/**
 * Tests for the CBT_Theme_Locale::escape_text_content method.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Locale::escape_text_content
 * @group locale
 */
class CBT_Theme_Locale_EscapeTextContent extends CBT_Theme_Locale_UnitTestCase {

	protected function call_private_method( $method_name, $args = array() ) {
		$reflection = new ReflectionClass( 'CBT_Theme_Locale' );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( null, $args );
	}

	public function test_escape_text_content() {
		$string         = 'This is a test text.';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( "<?php esc_html_e('This is a test text.', 'test-locale-theme');?>", $escaped_string );
	}

	public function test_escape_text_content_with_single_quote() {
		$string         = "This is a test text with a single quote '";
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( "<?php esc_html_e('This is a test text with a single quote \\'', 'test-locale-theme');?>", $escaped_string );
	}

	public function test_escape_text_content_with_double_quote() {
		$string         = 'This is a test text with a double quote "';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( "<?php esc_html_e('This is a test text with a double quote \"', 'test-locale-theme');?>", $escaped_string );
	}

	public function test_escape_text_content_with_html() {
		$string         = '<p>This is a test text with HTML.</p>';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( "<?php esc_html_e('<p>This is a test text with HTML.</p>', 'test-locale-theme');?>", $escaped_string );
	}

	public function test_escape_text_content_with_already_escaped_string() {
		$string         = "<?php esc_html_e('This is a test text.', 'test-locale-theme');?>";
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( $string, $escaped_string );
	}

	public function test_escape_text_content_with_non_string() {
		$string         = null;
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( $string, $escaped_string );
	}
}
