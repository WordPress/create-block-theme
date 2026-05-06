<?php
/**
 * Tests for CBT_Theme_Save service.
 *
 * Note: cache invalidation (`wp_get_theme()->cache_delete()`) is intentionally
 * not asserted here. Observing it would require either mocking the static
 * `wp_get_theme()` helper or wiring up a counter via filters — both
 * disproportionate to the value of locking in a single line of behavior.
 *
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Save extends WP_UnitTestCase {

	protected static $admin_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$admin_id );
	}

	public function test_run_with_empty_options_returns_true() {
		$result = CBT_Theme_Save::run( array() );
		$this->assertTrue( $result );
	}

	public function test_run_without_options_does_not_invoke_any_save_step() {
		$test_theme_slug = $this->create_blank_theme();

		// Stage a user font that would be moved into the theme if saveFonts ran.
		$this->stage_user_font();
		$user_settings_before = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();

		CBT_Theme_Save::run( array() );

		$user_settings_after = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();

		// User settings untouched: persist_font_settings was not called.
		$this->assertEquals( $user_settings_before, $user_settings_after );

		$this->uninstall_theme( $test_theme_slug );
	}

	/**
	 * Regression: previously `true === $options['processOnlySavedTemplates']`
	 * was read without isset(), which raised an Undefined index notice when
	 * saveTemplates was passed without processOnlySavedTemplates.
	 *
	 * The matcher is intentionally narrowed to the key name so unrelated
	 * pre-existing notices/warnings in downstream code do not fail this test.
	 */
	public function test_save_templates_without_process_only_flag_does_not_warn() {
		$test_theme_slug = $this->create_blank_theme();

		set_error_handler(
			static function ( $errno, $errstr ) {
				if ( false !== strpos( $errstr, 'processOnlySavedTemplates' ) ) {
					throw new \ErrorException( $errstr, 0, $errno );
				}
				return false;
			}
		);

		try {
			$result = CBT_Theme_Save::run( array( 'saveTemplates' => true ) );
		} finally {
			restore_error_handler();
		}

		$this->assertTrue( $result );

		$this->uninstall_theme( $test_theme_slug );
	}

	/**
	 * @dataProvider truthy_flag_provider
	 */
	public function test_truthy_save_fonts_values_trigger_persist( $truthy_value ) {
		$test_theme_slug = $this->create_blank_theme();
		$this->stage_user_font();

		// Sanity: user font is staged before run().
		$user_settings_before = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$this->assertArrayHasKey( 'typography', $user_settings_before );

		CBT_Theme_Save::run( array( 'saveFonts' => $truthy_value ) );

		// persist_font_settings clears user-space typography after moving it into the theme.
		$user_settings_after = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$this->assertArrayNotHasKey(
			'typography',
			$user_settings_after,
			'saveFonts flag value did not trigger persist'
		);

		$this->uninstall_theme( $test_theme_slug );
	}

	public function truthy_flag_provider() {
		return array(
			'bool true'     => array( true ),
			'int 1'         => array( 1 ),
			'string "1"'    => array( '1' ),
			'string "true"' => array( 'true' ),
		);
	}

	/**
	 * @dataProvider falsy_flag_provider
	 */
	public function test_falsy_save_fonts_values_skip_persist( $falsy_value ) {
		$test_theme_slug = $this->create_blank_theme();
		$this->stage_user_font();

		$options = array( 'saveFonts' => $falsy_value );
		CBT_Theme_Save::run( $options );

		// User typography should remain in place because persist did not run.
		$user_settings_after = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$this->assertArrayHasKey(
			'typography',
			$user_settings_after,
			'falsy saveFonts value should not have triggered persist'
		);

		$this->uninstall_theme( $test_theme_slug );
	}

	public function falsy_flag_provider() {
		return array(
			'bool false'     => array( false ),
			'int 0'          => array( 0 ),
			'empty string'   => array( '' ),
			'null'           => array( null ),
			'string "false"' => array( 'false' ),
			'string "FALSE"' => array( 'FALSE' ),
			'string "0"'     => array( '0' ),
		);
	}

	public function test_missing_save_fonts_key_skips_persist() {
		$test_theme_slug = $this->create_blank_theme();
		$this->stage_user_font();

		CBT_Theme_Save::run( array( 'saveStyle' => false ) );

		$user_settings_after = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$this->assertArrayHasKey( 'typography', $user_settings_after );

		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_run_propagates_wp_error_from_patterns_step() {
		$test_theme_slug = $this->create_blank_theme();

		// Create a wp_block post; CBT_Theme_Patterns will try to write a file
		// for it under the theme's patterns/ directory.
		$pattern_name = 'cbt-test-conflict-pattern';
		self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_title'   => $pattern_name,
				'post_content' => '<!-- wp:paragraph --><p>conflict</p><!-- /wp:paragraph -->',
			)
		);

		// Pre-create a file with the same pattern slug so the second write conflicts.
		$patterns_dir = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns';
		if ( ! is_dir( $patterns_dir ) ) {
			wp_mkdir_p( $patterns_dir );
		}
		file_put_contents( $patterns_dir . DIRECTORY_SEPARATOR . $pattern_name . '.php', '<?php // pre-existing' );

		$result = CBT_Theme_Save::run( array( 'savePatterns' => true ) );

		$this->assertWPError( $result );
		$this->assertEquals( 'pattern_already_exists', $result->get_error_code() );

		$this->uninstall_theme( $test_theme_slug );
	}

	private function create_blank_theme() {
		$test_theme_slug = 'cbttesttheme';

		delete_theme( $test_theme_slug );

		$request = new WP_REST_Request( 'POST', '/create-block-theme/v1/create-blank' );
		$request->set_param( 'name', $test_theme_slug );
		$request->set_param( 'description', '' );
		$request->set_param( 'uri', '' );
		$request->set_param( 'author', '' );
		$request->set_param( 'author_uri', '' );
		$request->set_param( 'tags_custom', '' );
		$request->set_param( 'recommended_plugins', '' );

		rest_do_request( $request );

		CBT_Theme_JSON_Resolver::clean_cached_data();

		return $test_theme_slug;
	}

	private function uninstall_theme( $theme_slug ) {
		CBT_Theme_JSON_Resolver::write_user_settings( array() );
		delete_theme( $theme_slug );
	}

	private function stage_user_font() {
		$font_dir              = wp_get_font_dir();
		$font_test_source      = __DIR__ . '/data/fonts/OpenSans-Regular.ttf';
		$font_test_url         = $font_dir['url'] . '/open-sans-normal-400.ttf';
		$font_test_destination = $font_dir['path'] . '/open-sans-normal-400.ttf';

		if ( ! file_exists( $font_dir['path'] ) ) {
			mkdir( $font_dir['path'] );
		}
		copy( $font_test_source, $font_test_destination );

		$settings = array();
		$settings['typography']['fontFamilies']['custom'] = array(
			array(
				'slug'       => 'open-sans',
				'name'       => 'Open Sans',
				'fontFamily' => 'Open Sans',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Open Sans',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => $font_test_url,
					),
				),
			),
		);

		CBT_Theme_JSON_Resolver::write_user_settings( $settings );
	}
}
