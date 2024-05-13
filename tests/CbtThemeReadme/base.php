<?php
/**
 * Base test case for Theme Readme tests.
 *
 * @package Create_Block_Theme
 */
abstract class CBT_Theme_Readme_UnitTestCase extends WP_UnitTestCase {

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
	 * Stores the original readme.txt content.
	 *
	 * @var string|null;
	 */
	private $orig_readme_content;

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
		switch_theme( 'test-theme-readme' );

		// Store the original readme.txt content.
		$this->orig_readme_content = Theme_Readme::get_content();
	}

	/**
	 * Tears down tests.
	 */
	public function tear_down() {
		parent::tear_down();

		// Restore the original readme.txt content.
		file_put_contents( Theme_Readme::file_path(), $this->orig_readme_content );

		// Restore the original active theme.
		switch_theme( $this->orig_active_theme_slug );
	}
}
