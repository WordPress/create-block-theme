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

		$this->activate_font();

		$user_data_before  = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_before = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		Theme_Fonts::copy_activated_fonts_to_theme();

		$user_data_after  = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		$theme_data_after = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		// Ensure that the font was added and then removed from user space
		$this->assertArrayNotHasKey( 'typography', $user_data_begin );
		$this->assertEquals( 'open-sans', $user_data_before['typography']['fontFamilies']['custom'][0]['slug'] );
		$this->assertArrayNotHasKey( 'typography', $user_data_after );

		// Ensure that the font was added to the theme
		$this->assertCount( 1, $theme_data_before['typography']['fontFamilies']['theme'] );
		$this->assertCount( 2, $theme_data_after['typography']['fontFamilies']['theme'] );
		$this->assertEquals( 'open-sans', $theme_data_after['typography']['fontFamilies']['theme'][1]['slug'] );

		// Ensure that the URL was changed to a local file and that it was copied to where it should be
		$this->assertEquals( 'file:./assets/fonts/open-sans-normal-400.ttf', $theme_data_after['typography']['fontFamilies']['theme'][1]['fontFace'][0]['src'] );
		$this->assertTrue( file_exists( get_stylesheet_directory() . '/assets/fonts/open-sans-normal-400.ttf' ) );

		delete_theme( $test_theme_slug );
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

		return $test_theme_slug;
	}

	private function activate_font() {

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

}
