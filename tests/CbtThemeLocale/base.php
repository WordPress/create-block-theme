<?php
/**
 * Base test case for Theme Locale tests.
 *
 * @package Create_Block_Theme
 */
abstract class CBT_Theme_Locale_UnitTestCase extends WP_UnitTestCase {

	/**
	 * Stores the original active theme slug in order to restore it in tear down.
	 *
	 * @var string|null
	 */
	private $orig_active_theme_slug;

	/**
	 * Stores the custom test theme directory.
	 *
	 * @var string|null;
	 */
	private $test_theme_dir;

	/**
	 * Sets up tests.
	 */
	public function set_up() {
		parent::set_up();

		// Store the original active theme.
		$this->orig_active_theme_slug = get_option( 'stylesheet' );

		// Create a test theme directory.
		$this->test_theme_dir = DIR_TESTDATA . '/themes/';

		// Register test theme directory.
		register_theme_directory( $this->test_theme_dir );

		// Switch to the test theme.
		switch_theme( 'test-theme-locale' );
	}

	/**
	 * Tears down tests.
	 */
	public function tear_down() {
		parent::tear_down();

		// Restore the original active theme.
		switch_theme( $this->orig_active_theme_slug );
	}

	/**
	 * Assert that generated PHP source does not contain a callable function token.
	 *
	 * @param string $function_name The function name that must not be callable.
	 * @param string $php_code      The generated PHP source to inspect.
	 */
	protected function assert_php_code_does_not_call_function( $function_name, $php_code ) {
		$tokens = token_get_all( $php_code );

		foreach ( $tokens as $token ) {
			if (
				is_array( $token ) &&
				T_STRING === $token[0] &&
				0 === strcasecmp( $function_name, $token[1] )
			) {
				$this->fail( sprintf( 'Generated PHP should not call %s().', $function_name ) );
			}
		}

		$this->assertTrue( true );
	}
}
