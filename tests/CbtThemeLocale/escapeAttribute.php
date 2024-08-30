<?php
require_once __DIR__ . '/base.php';

/**
 * Tests for the CBT_Theme_Locale::escape_attribute method.
 *
 * @package Create_Block_Theme
 * @covers CBT_Theme_Locale::escape_attribute
 * @group locale
 */
class CBT_Theme_Locale_EscapeAttribute extends CBT_Theme_Locale_UnitTestCase {

	protected function call_private_method( $method_name, $args = array() ) {
		$reflection = new ReflectionClass( 'CBT_Theme_Locale' );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( null, $args );
	}

	public function test_escape_attribute() {
		$string          = 'This is a test attribute.';
		$escaped_string  = $this->call_private_method( 'escape_attribute', array( $string ) );
		$expected_string = "<?php esc_attr_e('This is a test attribute.', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";
		$this->assertEquals( $expected_string, $escaped_string );
	}

	public function test_escape_attribute_with_single_quote() {
		$string          = "This is a test attribute with a single quote '";
		$escaped_string  = $this->call_private_method( 'escape_attribute', array( $string ) );
		$expected_string = "<?php esc_attr_e('This is a test attribute with a single quote \\'', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";
		$this->assertEquals( $expected_string, $escaped_string );
	}

	public function test_escape_attribute_with_double_quote() {
		$string          = 'This is a test attribute with a double quote "';
		$escaped_string  = $this->call_private_method( 'escape_attribute', array( $string ) );
		$expected_string = "<?php esc_attr_e('This is a test attribute with a double quote \"', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";
		$this->assertEquals( $expected_string, $escaped_string );
	}

	public function test_escape_attribute_with_empty_string() {
		$string         = '';
		$escaped_string = $this->call_private_method( 'escape_attribute', array( $string ) );
		$this->assertEquals( $string, $escaped_string );
	}

	public function test_escape_attribute_with_already_escaped_string() {
		$string         = "<?php esc_attr_e('This is already escaped.', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";
		$escaped_string = $this->call_private_method( 'escape_attribute', array( $string ) );
		$this->assertEquals( $string, $escaped_string );
	}

	public function test_escape_attribute_with_non_string() {
		$string         = null;
		$escaped_string = $this->call_private_method( 'escape_attribute', array( $string ) );
		$this->assertEquals( $string, $escaped_string );
	}
}
