<?php
/**
 * Class WP_Create_Block_Theme_Admin
 *
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Fonts extends WP_UnitTestCase {

	protected static $admin_id;
	protected static $editor_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id  = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$editor_id = $factory->user->create(
			array(
				'role' => 'editor',
			)
		);
	}

	public function test_copy_activated_fonts_to_theme() {

		wp_set_current_user( self::$admin_id );

		$user_data_begin = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();

		$test_theme_slug = $this->create_blank_theme();

		$this->activate_user_font();

		$user_data_before  = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_before = CBT_Theme_JSON_Resolver::get_theme_data()->get_settings();

		$this->save_theme();

		$user_data_after  = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_after = CBT_Theme_JSON_Resolver::get_theme_data()->get_settings();

		// ensure that the font was added and then removed from user space
		$this->assertarraynothaskey( 'typography', $user_data_begin );
		$this->assertequals( 'open-sans', $user_data_before['typography']['fontFamilies']['custom'][0]['slug'] );
		$this->assertarraynothaskey( 'typography', $user_data_after );

		// Ensure that the font was added to the theme
		$this->assertCount( 1, $theme_data_before['typography']['fontFamilies']['theme'] );
		$this->assertCount( 2, $theme_data_after['typography']['fontFamilies']['theme'] );
		$this->assertEquals( 'open-sans', $theme_data_after['typography']['fontFamilies']['theme'][1]['slug'] );

		// Ensure that the URL was changed to a local file and that it was copied to where it should be
		$this->assertEquals( 'file:./assets/fonts/open-sans-normal-400.ttf', $theme_data_after['typography']['fontFamilies']['theme'][1]['fontFace'][0]['src'][0] );
		$this->assertTrue( file_exists( get_stylesheet_directory() . '/assets/fonts/open-sans-normal-400.ttf' ) );

		$this->uninstall_theme( $test_theme_slug );

	}

	public function test_remove_deactivated_fonts_from_theme() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		// Create a theme with multiple fonts
		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'open-sans',
				'name'       => 'Open Sans',
				'fontFamily' => 'Open Sans',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Open Sans',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'file:./assets/fonts/open-sans-normal-400.ttf',
					),
				),
			),
			array(
				'slug'       => 'deactivated-font',
				'name'       => 'Deactivated Font',
				'fontFamily' => 'Deactivated Font',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Deactivated Font',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'file:./assets/fonts/deactivated-font.ttf',
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Create the font files
		$font_dir = get_stylesheet_directory() . '/assets/fonts/';
		if ( ! file_exists( $font_dir ) ) {
			mkdir( $font_dir, 0777, true );
		}
		file_put_contents( $font_dir . 'open-sans-normal-400.ttf', 'dummy content' );
		file_put_contents( $font_dir . 'deactivated-font.ttf', 'dummy content' );

		// Simulate user deactivating the 'deactivated-font' font
		$user_settings                                        = array();
		$user_settings['typography']['fontFamilies']['theme'] = array(
			array(
				'slug'       => 'open-sans',
				'name'       => 'Open Sans',
				'fontFamily' => 'Open Sans',
			),
		);
		CBT_Theme_JSON_Resolver::write_user_settings( $user_settings );

		$user_data_before         = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_before        = CBT_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$merged_data_before       = CBT_Theme_JSON_Resolver::get_merged_data()->get_settings();
		$theme_file_exists_before = file_exists( $font_dir . 'open-sans-normal-400.ttf' );

		// Call the method to remove deactivated fonts
		CBT_Theme_Fonts::remove_deactivated_fonts_from_theme();

		$user_data_after         = CBT_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_after        = CBT_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$merged_data_after       = CBT_Theme_JSON_Resolver::get_merged_data()->get_settings();
		$theme_file_exists_after = file_exists( $font_dir . 'open-sans-normal-400.ttf' );

		// ensure that the font was added to the theme settings and removed in user settings and therefore missing in merged settings
		$this->assertCount( 2, $theme_data_before['typography']['fontFamilies']['theme'] );
		$this->assertequals( 'open-sans', $theme_data_before['typography']['fontFamilies']['theme'][0]['slug'] );
		$this->assertCount( 1, $user_data_before['typography']['fontFamilies']['theme'] );
		$this->assertCount( 1, $merged_data_before['typography']['fontFamilies']['theme'] );

		// ensure that the font was removed from the user settings and removed from the theme settings and therefore missing in merged settings
		$this->assertCount( 1, $theme_data_after['typography']['fontFamilies']['theme'] );
		$this->assertEquals( 'open-sans', $theme_data_after['typography']['fontFamilies']['theme'][0]['slug'] );
		$this->assertarraynothaskey( 'typography', $user_data_after );
		$this->assertCount( 1, $merged_data_after['typography']['fontFamilies']['theme'] );

		// ensure that the font asset was removed
		$this->assertTrue( $theme_file_exists_before );
		$this->assertFalse( file_exists( $font_dir . 'deactivated-font.ttf' ) );
		$this->assertTrue( $theme_file_exists_after );

		$this->uninstall_theme( $test_theme_slug );

		$theme_file_exists_after_uninstall = file_exists( $font_dir . 'open-sans-normal-400.ttf' );
		// ensure that the font asset was removed after uninstalling the theme
		$this->assertFalse( $theme_file_exists_after_uninstall );
	}

	public function test_get_all_fonts_just_theme() {

		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'open-sans',
				'name'       => 'Open Sans',
				'fontFamily' => 'Open Sans',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Open Sans',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'file:./assets/fonts/open-sans-normal-400.ttf',
					),
				),
			),
			array(
				'slug'       => 'closed-sans',
				'name'       => 'Closed Sans',
				'fontFamily' => 'Closed Sans',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Closed Sans',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'http://example.com/closed-sans-normal-400.ttf',
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		$fonts = CBT_Theme_Fonts::get_all_fonts();

		$this->assertCount( 2, $fonts );
		$this->assertEquals( 'open-sans', $fonts[0]['slug'] );
		$this->assertEquals( 'closed-sans', $fonts[1]['slug'] );
		$this->assertStringNotContainsString( 'file:.', $fonts[0]['fontFace'][0]['src'] );
		$this->assertStringNotContainsString( 'file:.', $fonts[1]['fontFace'][0]['src'] );

		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_get_all_fonts_from_theme_and_variation() {

		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'open-sans',
				'name'       => 'Open Sans',
				'fontFamily' => 'Open Sans',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Open Sans',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'file:./assets/fonts/open-sans-normal-400.ttf',
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		$variation_json = array(
			'version' => '2',
			'title'   => 'Variation',
		);
		$variation_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'closed-sans',
				'name'       => 'Closed Sans',
				'fontFamily' => 'Closed Sans',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Closed Sans',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'http://example.com/closed-sans-normal-400.ttf',
					),
				),
			),
		);

		// Save the variation
		$variation_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;
		$variation_slug = 'variation';
		wp_mkdir_p( $variation_path );
		file_put_contents(
			$variation_path . $variation_slug . '.json',
			wp_json_encode( $variation_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);

		$fonts = CBT_Theme_Fonts::get_all_fonts();

		$this->assertCount( 2, $fonts );
		$this->assertEquals( 'open-sans', $fonts[0]['slug'] );
		$this->assertEquals( 'closed-sans', $fonts[1]['slug'] );
		$this->assertStringNotContainsString( 'file:.', $fonts[0]['fontFace'][0]['src'] );
		$this->assertStringNotContainsString( 'file:.', $fonts[1]['fontFace'][0]['src'] );

		$this->uninstall_theme( $test_theme_slug );

	}

	public function test_non_array_font_src() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		// Create a theme with a non-array font src
		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'single-src-font',
				'name'       => 'Single Src Font',
				'fontFamily' => 'Single Src Font',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Single Src Font',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'file:./assets/fonts/single-src-font.ttf',
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Attempt to get all fonts
		$fonts = CBT_Theme_Fonts::get_all_fonts();

		// Verify that the src is correctly handled
		$this->assertCount( 1, $fonts );
		$this->assertEquals( 'single-src-font', $fonts[0]['slug'] );
		$this->assertEquals( get_stylesheet_directory_uri() . '/assets/fonts/single-src-font.ttf', $fonts[0]['fontFace'][0]['src'] );

		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_array_font_src() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		// Create a theme with an array font src
		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'array-src-font',
				'name'       => 'Array Src Font',
				'fontFamily' => 'Array Src Font',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Array Src Font',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => array(
							'file:./assets/fonts/array-src-font.ttf',
							'file:./assets/fonts/array-src-font-bold.ttf',
						),
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Attempt to get all fonts
		$fonts = CBT_Theme_Fonts::get_all_fonts();

		// Verify that the src is correctly handled
		$this->assertCount( 1, $fonts );
		$this->assertEquals( 'array-src-font', $fonts[0]['slug'] );
		$this->assertIsArray( $fonts[0]['fontFace'][0]['src'] );
		$this->assertCount( 2, $fonts[0]['fontFace'][0]['src'] );
		$this->assertEquals( get_stylesheet_directory_uri() . '/assets/fonts/array-src-font.ttf', $fonts[0]['fontFace'][0]['src'][0] );
		$this->assertEquals( get_stylesheet_directory_uri() . '/assets/fonts/array-src-font-bold.ttf', $fonts[0]['fontFace'][0]['src'][1] );

		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_absolute_url_handling() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		// Create a theme with an absolute URL
		$theme_json = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['typography']['fontFamilies'] = array(
			array(
				'slug'       => 'absolute-url-font',
				'name'       => 'Absolute URL Font',
				'fontFamily' => 'Absolute URL Font',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Absolute URL Font',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => 'http://example.com/fonts/absolute-url-font.ttf',
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Attempt to get all fonts
		$fonts = CBT_Theme_Fonts::get_all_fonts();

		// Verify that the absolute URL remains unchanged
		$this->assertCount( 1, $fonts );
		$this->assertEquals( 'absolute-url-font', $fonts[0]['slug'] );
		$this->assertEquals( 'http://example.com/fonts/absolute-url-font.ttf', $fonts[0]['fontFace'][0]['src'] );

		$this->uninstall_theme( $test_theme_slug );
	}

	private function save_theme() {
		CBT_Theme_Fonts::persist_font_settings();
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
		$request->set_param( 'subfolder', '' );
		$request->set_param( 'recommended_plugins', '' );

		rest_do_request( $request );

		CBT_Theme_JSON_Resolver::clean_cached_data();

		return $test_theme_slug;
	}

	private function uninstall_theme( $theme_slug ) {
		CBT_Theme_JSON_Resolver::write_user_settings( array() );
		delete_theme( $theme_slug );
	}

	private function activate_user_font() {

		$font_dir              = wp_get_font_dir();
		$font_test_url         = $font_dir['url'] . '/open-sans-normal-400.ttf';
		$font_test_source      = __DIR__ . '/data/fonts/OpenSans-Regular.ttf';
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

	private function activate_font_in_theme_and_override_in_user() {

		// Copy the font asset
		$font_dir              = get_stylesheet_directory() . '/assets/fonts/';
		$font_test_source      = __DIR__ . '/data/fonts/OpenSans-Regular.ttf';
		$font_test_destination = $font_dir . '/open-sans-normal-400.ttf';

		if ( ! file_exists( get_stylesheet_directory() . '/assets/' ) ) {
			mkdir( get_stylesheet_directory() . '/assets/' );
		}
		if ( ! file_exists( get_stylesheet_directory() . '/assets/fonts/' ) ) {
			mkdir( get_stylesheet_directory() . '/assets/fonts/' );
		}

		copy( $font_test_source, $font_test_destination );

		// Add the font to the theme
		$theme_json                   = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_original_font_families = $theme_json['settings']['typography']['fontFamilies'];
		$theme_json['settings']['typography']['fontFamilies'][] = array(
			'slug'       => 'open-sans',
			'name'       => 'Open Sans',
			'fontFamily' => 'Open Sans',
			'fontFace'   => array(
				array(
					'fontFamily' => 'Open Sans',
					'fontStyle'  => 'normal',
					'fontWeight' => '400',
					'src'        => array(
						'file:./assets/fonts/open-sans-normal-400.ttf',
						'file:./assets/fonts/open-sans-normal-400.ttf',
					),
				),
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Deactivate the test font in the theme.  To do this the 'theme' collection
		// is overwritten to declare the intention of having it gone.
		// Here we're writing the font family settings as they existed BEFORE we added
		// the test font family to the theme.
		$settings                                        = array();
		$settings['typography']['fontFamilies']['theme'] = $theme_original_font_families;
		CBT_Theme_JSON_Resolver::write_user_settings( $settings );
	}

}

