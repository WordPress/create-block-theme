<?php
/**
 * Class WP_Create_Block_Theme_Admin
 *
 * @package Create_Block_Theme
 */
class Create_Block_Theme_Fonts extends WP_UnitTestCase {

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

		$user_data_begin = MY_Theme_JSON_Resolver::get_user_data()->get_settings();

		$test_theme_slug = $this->create_blank_theme();

		$this->activate_user_font();

		$user_data_before  = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_before = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		$this->save_theme();

		$user_data_after  = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_after = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		// ensure that the font was added and then removed from user space
		$this->assertarraynothaskey( 'typography', $user_data_begin );
		$this->assertequals( 'open-sans', $user_data_before['typography']['fontFamilies']['custom'][0]['slug'] );
		$this->assertarraynothaskey( 'typography', $user_data_after );

		// Ensure that the font was added to the theme
		$this->assertCount( 1, $theme_data_before['typography']['fontFamilies']['theme'] );
		$this->assertCount( 2, $theme_data_after['typography']['fontFamilies']['theme'] );
		$this->assertEquals( 'open-sans', $theme_data_after['typography']['fontFamilies']['theme'][1]['slug'] );

		// Ensure that the URL was changed to a local file and that it was copied to where it should be
		$this->assertEquals( 'file:./assets/fonts/open-sans-normal-400.ttf', $theme_data_after['typography']['fontFamilies']['theme'][1]['fontFace'][0]['src'] );
		$this->assertTrue( file_exists( get_stylesheet_directory() . '/assets/fonts/open-sans-normal-400.ttf' ) );

		$this->uninstall_theme( $test_theme_slug );

	}

	public function test_remove_deactivated_fonts_from_theme() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		$this->activate_font_in_theme_and_override_in_user();

		$user_data_before         = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_before        = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$merged_data_before       = MY_Theme_JSON_Resolver::get_merged_data()->get_settings();
		$theme_file_exists_before = file_exists( get_stylesheet_directory() . '/assets/fonts/open-sans-normal-400.ttf' );

		$this->save_theme();

		$user_data_after         = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_after        = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$merged_data_after       = MY_Theme_JSON_Resolver::get_merged_data()->get_settings();
		$theme_file_exists_after = file_exists( get_stylesheet_directory() . '/assets/fonts/open-sans-normal-400.ttf' );

		// ensure that the font was added to the theme settings and removed in user settings and therefore missing in merged settings
		$this->assertCount( 2, $theme_data_before['typography']['fontFamilies']['theme'] );
		$this->assertequals( 'open-sans', $theme_data_before['typography']['fontFamilies']['theme'][1]['slug'] );
		$this->assertCount( 1, $user_data_before['typography']['fontFamilies']['theme'] );
		$this->assertnotequals( 'open-sans', $user_data_before['typography']['fontFamilies']['theme'][0]['slug'] );
		$this->assertCount( 1, $merged_data_before['typography']['fontFamilies']['theme'] );
		$this->assertnotequals( 'open-sans', $merged_data_before['typography']['fontFamilies']['theme'][0]['slug'] );

		// ensure that the font was removed from the user settings and removed from the theme settings and therefore missing in merged settings
		$this->assertCount( 1, $theme_data_after['typography']['fontFamilies']['theme'] );
		$this->assertnotequals( 'open-sans', $theme_data_after['typography']['fontFamilies']['theme'][0]['slug'] );
		$this->assertarraynothaskey( 'typography', $user_data_after );
		$this->assertnotequals( 'open-sans', $theme_data_after['typography']['fontFamilies']['theme'][0]['slug'] );
		$this->assertCount( 1, $merged_data_after['typography']['fontFamilies']['theme'] );
		$this->assertnotequals( 'open-sans', $merged_data_after['typography']['fontFamilies']['theme'][0]['slug'] );

		// ensure that the file resource was removed
		$this->assertTrue( $theme_file_exists_before );
		$this->assertFalse( $theme_file_exists_after );

		$this->uninstall_theme( $test_theme_slug );
	}

	private function save_theme() {
		Theme_Fonts::persist_font_settings();
		// Theme_Templates::add_templates_to_local( 'all' );
		// Theme_Json::add_theme_json_to_local( 'all' );
		// Theme_Styles::clear_user_styles_customizations();
		// Theme_Templates::clear_user_templates_customizations();
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

		rest_do_request( $request );

		MY_Theme_JSON_Resolver::clean_cached_data();

		return $test_theme_slug;
	}

	private function uninstall_theme( $theme_slug ) {
		MY_Theme_JSON_Resolver::write_user_settings( array() );
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

		MY_Theme_JSON_Resolver::write_user_settings( $settings );
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
		$theme_json                   = MY_Theme_JSON_Resolver::get_theme_file_contents();
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
					'src'        => 'file:./assets/fonts/open-sans-normal-400.ttf',
				),
			),
		);
		MY_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		// Deactivate the test font in the theme.  To do this the 'theme' collection
		// is overwritten to declare the intention of having it gone.
		// Here we're writing the font family settings as they existed BEFORE we added
		// the test font family to the theme.
		$settings                                        = array();
		$settings['typography']['fontFamilies']['theme'] = $theme_original_font_families;
		MY_Theme_JSON_Resolver::write_user_settings( $settings );
	}


}
