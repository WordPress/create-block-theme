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

		$user_data_before = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		// $theme_data_before = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		Theme_Fonts::copy_activated_fonts_to_theme();

		$user_data_after = MY_Theme_JSON_Resolver::get_user_data()->get_settings();
		// $theme_data_after = MY_Theme_JSON_Resolver::get_theme_data()->get_settings();

		$this->assertArrayNotHasKey( 'typography', $user_data_begin );
		$this->assertEquals( 'open-sans', $user_data_before['typography']['fontFamilies']['custom'][0]['slug'] );
		$this->assertArrayNotHasKey( 'typography', $user_data_after );

		$this->remove_test_theme( $test_theme_slug);
	}

	private function copy_activated_fonts_to_theme () {

		$global_styles_id = MY_Theme_JSON_Resolver::get_user_global_styles_post_id();

		$request = new WP_REST_Request( 'POST', '/wp/v2/global-styles/' . $global_styles_id );
		$request->set_param( 'settings', array() );

		rest_do_request( $request );
	}

	private function remove_test_theme( $theme_slug ) {
		//TODO: Activate the default theme
		//Delete the test theme
	}

	private function create_blank_theme() {

		$test_theme_slug = 'create-block-theme-test-blank-theme-' . rand();
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

		$user_custom_post_type_id = MY_Theme_JSON_Resolver::get_user_global_styles_post_id();

		$font_dir       = wp_get_font_dir();
		$font_test_url = $font_dir['url'] . 'open-sans-normal-400.ttf';
		$font_test_source = __DIR__ . '/data/fonts/OpenSans-Regular.ttf';
		$font_test_destination = $font_dir['path'] . 'open-sans-normal-400.ttf';

		copy( $font_test_source, $font_test_destination );

		$settings = array();
		$settings['typography']['fontFamilies']['custom'] = array(array(
			'slug' => 'open-sans',
			'name' => 'Open Sans',
			'fontFamily' => 'Open Sans',
			'fontFace' => array(
				array (
					'fontFamily' => 'Open Sans',
					'fontStyle' => 'normal',
					'fontWeight' => '400',
					'src' => $font_test_url,
				),
			)
		));

		$update_request = new WP_REST_Request( 'POST', '/wp/v2/global-styles/' . $user_custom_post_type_id );
		$update_request->set_param( 'settings', $settings );

		rest_do_request( $update_request );
	}

}
