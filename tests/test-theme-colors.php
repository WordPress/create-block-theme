<?php
/**
 * Class Test_Create_Block_Theme_Colors
 *
 * @package Create_Block_Theme
 */
require_once __DIR__ . '/class-create-block-theme-test-case.php';

class Test_Create_Block_Theme_Colors extends Create_Block_Theme_Test_Case {

	protected static $admin_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	public function test_custom_color_prefix_is_removed_when_option_is_enabled_for_blank_theme() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		$this->add_user_color_palette(
			array(
				array(
					'slug'  => 'custom-base',
					'name'  => 'Base',
					'color' => '#2B2B2B',
				),
				array(
					'slug'  => 'custom-contrast',
					'name'  => 'Contrast',
					'color' => '#F5F0E8',
				),
			),
			array(
				'color' => array(
					'background' => 'var:preset|color|custom-base',
					'text'       => 'var(--wp--preset--color--custom-contrast)',
				),
			)
		);

		CBT_Theme_JSON::add_theme_json_to_local( 'all', array( 'removeCustomColorPrefix' => true ) );
		CBT_Theme_JSON_Resolver::clean_cached_data();

		$theme_data = CBT_Theme_JSON_Resolver::get_theme_file_contents();

		$this->assertEquals( 'base', $theme_data['settings']['color']['palette'][0]['slug'] );
		$this->assertEquals( 'contrast', $theme_data['settings']['color']['palette'][1]['slug'] );
		$this->assertEquals( 'var(--wp--preset--color--base)', $theme_data['styles']['color']['background'] );
		$this->assertEquals( 'var(--wp--preset--color--contrast)', $theme_data['styles']['color']['text'] );

		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_custom_color_prefix_is_preserved_by_default_for_blank_theme() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		$this->add_user_color_palette(
			array(
				array(
					'slug'  => 'custom-base',
					'name'  => 'Base',
					'color' => '#2B2B2B',
				),
				array(
					'slug'  => 'custom-contrast',
					'name'  => 'Contrast',
					'color' => '#F5F0E8',
				),
			),
			array(
				'color' => array(
					'background' => 'var:preset|color|custom-base',
					'text'       => 'var(--wp--preset--color--custom-contrast)',
				),
			)
		);

		CBT_Theme_JSON::add_theme_json_to_local( 'all' );
		CBT_Theme_JSON_Resolver::clean_cached_data();

		$theme_data = CBT_Theme_JSON_Resolver::get_theme_file_contents();

		$this->assertEquals( 'custom-base', $theme_data['settings']['color']['palette'][0]['slug'] );
		$this->assertEquals( 'custom-contrast', $theme_data['settings']['color']['palette'][1]['slug'] );
		$this->assertEquals( 'var(--wp--preset--color--custom-base)', $theme_data['styles']['color']['background'] );
		$this->assertEquals( 'var(--wp--preset--color--custom-contrast)', $theme_data['styles']['color']['text'] );

		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_custom_color_prefix_is_preserved_when_theme_already_has_palette() {
		wp_set_current_user( self::$admin_id );

		$test_theme_slug = $this->create_blank_theme();

		$theme_json                                 = CBT_Theme_JSON_Resolver::get_theme_file_contents();
		$theme_json['settings']['color']['palette'] = array(
			array(
				'slug'  => 'base',
				'name'  => 'Base',
				'color' => '#2B2B2B',
			),
		);
		CBT_Theme_JSON_Resolver::write_theme_file_contents( $theme_json );

		$this->add_user_color_palette(
			array(
				array(
					'slug'  => 'custom-accent',
					'name'  => 'Accent',
					'color' => '#C8A96E',
				),
			)
		);

		CBT_Theme_JSON::add_theme_json_to_local( 'all', array( 'removeCustomColorPrefix' => true ) );
		CBT_Theme_JSON_Resolver::clean_cached_data();

		$theme_data = CBT_Theme_JSON_Resolver::get_theme_data()->get_settings();

		$this->assertEquals( 'base', $theme_data['color']['palette']['theme'][0]['slug'] );
		$this->assertEquals( 'custom-accent', $theme_data['color']['palette']['theme'][1]['slug'] );

		$this->uninstall_theme( $test_theme_slug );
	}

	private function add_user_color_palette( $palette, $styles = array() ) {
		$settings = array(
			'color' => array(
				'palette' => array(
					'custom' => $palette,
				),
			),
		);

		$global_styles_id = CBT_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$request          = new WP_REST_Request( 'POST', '/wp/v2/global-styles/' . $global_styles_id );
		$request->set_param( 'settings', $settings );
		$request->set_param( 'styles', $styles );
		rest_do_request( $request );

		CBT_Theme_JSON_Resolver::clean_cached_data();
	}
}
