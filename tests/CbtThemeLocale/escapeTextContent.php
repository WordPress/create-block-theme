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

	public function test_escape_text_content_with_backslash_before_single_quote() {
		$string          = chr( 92 ) . "');system(\$_GET[0]);//";
		$escaped_string  = $this->call_private_method( 'escape_text_content', array( $string ) );
		$expected_string = "<?php esc_html_e('" . addcslashes( $string, "\\'" ) . "', 'test-locale-theme');?>";

		$this->assertEquals( $expected_string, $escaped_string );
		$this->assert_php_code_does_not_call_function( 'system', $escaped_string );
	}

	public function test_escape_text_content_with_double_quote() {
		$string         = 'This is a test text with a double quote "';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$this->assertEquals( "<?php esc_html_e('This is a test text with a double quote \"', 'test-locale-theme');?>", $escaped_string );
	}

	public function test_escape_text_content_with_html() {
		$string          = '<p>This is a test text with HTML.</p>';
		$escaped_string  = $this->call_private_method( 'escape_text_content', array( $string ) );
		$expected_output = '<?php /* Translators: 1. is the start of a \'p\' HTML element, 2. is the end of a \'p\' HTML element */' . " \n" . 'echo sprintf( esc_html__( \'%1$sThis is a test text with HTML.%2$s\', \'test-locale-theme\' ), \'<p>\', \'</p>\' ); ?>';
		$this->assertEquals( $expected_output, $escaped_string );
	}

	public function test_escape_text_content_with_html_and_backslash_before_single_quote() {
		$payload        = chr( 92 ) . "');system(\$_GET[0]);//";
		$string         = '<strong>' . $payload . '</strong>';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );

		$this->assertStringContainsString( 'echo sprintf( esc_html__', $escaped_string );
		$this->assertStringContainsString( addcslashes( $payload, "\\'" ), $escaped_string );
		$this->assert_php_code_does_not_call_function( 'system', $escaped_string );
	}

	/**
	 * @dataProvider data_html_attribute_apostrophe_entities
	 */
	public function test_escape_text_content_escapes_decoded_href_values( $attribute_value, $decoded_value ) {
		$string         = '<a href="' . $attribute_value . '">Read</a>';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );
		$expected_value = addcslashes( $decoded_value, "\\'" );

		$this->assertStringContainsString( "esc_url( '$expected_value' )", $escaped_string );
	}

	public function data_html_attribute_apostrophe_entities() {
		return array(
			'raw apostrophe'            => array( "https://example.com/it's", "https://example.com/it's" ),
			'decimal entity'            => array( 'https://example.com/it&#39;s', "https://example.com/it's" ),
			'hexadecimal entity'        => array( 'https://example.com/it&#x27;s', "https://example.com/it's" ),
			'named apostrophe entity'   => array( 'https://example.com/it&apos;s', "https://example.com/it's" ),
			'named double quote entity' => array( 'https://example.com/a&quot;b', 'https://example.com/a"b' ),
			'backslash before entity'   => array( 'https://example.com/it\\&#39;s', "https://example.com/it\\'s" ),
		);
	}

	public function test_escape_text_content_escapes_decoded_generic_attribute_values() {
		$string         = '<span title="It&apos;s ready">Read</span>';
		$escaped_string = $this->call_private_method( 'escape_text_content', array( $string ) );

		$this->assertStringContainsString( "title=\"It\\'s ready\"", $escaped_string );
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
