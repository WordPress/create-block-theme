<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Styles extends WP_UnitTestCase {
	private $original_theme;

	public function set_up() {
		parent::set_up();

		$this->original_theme = get_stylesheet();
		register_theme_directory( DIR_TESTDATA . '/themes/' );
		switch_theme( 'test-theme-readme' );
	}

	public function tear_down() {
		switch_theme( $this->original_theme );

		parent::tear_down();
	}

	private function get_theme_data() {
		return array(
			'name'                => 'Test Theme',
			'description'         => 'A test theme.',
			'uri'                 => 'https://example.com/theme',
			'author'              => 'Theme Author',
			'author_uri'          => 'https://example.com',
			'requires_wp'         => '',
			'version'             => '1.0',
			'slug'                => 'test-theme',
			'tags_custom'         => '',
			'recommended_plugins' => '',
		);
	}

	public function test_build_style_css_disables_wordpress_org_updates() {
		$style_css = CBT_Theme_Styles::build_style_css( $this->get_theme_data() );

		$this->assertStringContainsString( "\nUpdate URI: false\n", $style_css );
	}

	public function test_update_style_css_disables_updates_for_generated_theme() {
		$style_css = "/*\nTheme Name: Source Theme\nUpdate URI: https://updates.example.com/theme\n*/\n";
		$style_css = CBT_Theme_Styles::update_style_css( $style_css, $this->get_theme_data(), true );

		$this->assertStringContainsString( "\nUpdate URI: false\n", $style_css );
		$this->assertStringNotContainsString( 'https://updates.example.com/theme', $style_css );
	}

	public function test_update_style_css_preserves_existing_update_uri() {
		$style_css = "/*\nTheme Name: Source Theme\nUpdate URI: https://updates.example.com/theme\n*/\n";
		$style_css = CBT_Theme_Styles::update_style_css( $style_css, $this->get_theme_data() );

		$this->assertStringContainsString(
			"\nUpdate URI: https://updates.example.com/theme\n",
			$style_css
		);
	}

	public function test_update_style_css_does_not_disable_updates_for_existing_theme() {
		$style_css = "/*\nTheme Name: Source Theme\n*/\n";
		$style_css = CBT_Theme_Styles::update_style_css( $style_css, $this->get_theme_data() );

		$this->assertStringNotContainsString( 'Update URI:', $style_css );
	}
}
