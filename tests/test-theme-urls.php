<?php
/**
 * Tests for CBT_Theme_URLs, which replaces URLs of the current site in
 * exported templates and patterns with PHP that rebuilds them at runtime.
 *
 * @package Create_Block_Theme
 * @group urls
 */
class Test_Create_Block_Theme_URLs extends WP_UnitTestCase {

	const HOME_URL   = 'http://example.org';
	const THEME_URL  = 'http://example.org/wp-content/themes/parent-theme';
	const CHILD_URL  = 'http://example.org/wp-content/themes/child-theme';
	const HOME_ECHO  = '<?php echo esc_url( home_url( %s ) ); ?>';
	const THEME_ECHO = '<?php echo esc_url( get_template_directory_uri() ); ?>';

	public function set_up() {
		parent::set_up();
		// Hooks added here are removed again by WP_UnitTestCase after each test.
		$this->set_site_urls( self::HOME_URL, self::HOME_URL );
		$this->set_theme_urls( self::THEME_URL, self::THEME_URL );
	}

	private function set_site_urls( $home, $siteurl ) {
		add_filter(
			'pre_option_home',
			function () use ( $home ) {
				return $home;
			}
		);
		add_filter(
			'pre_option_siteurl',
			function () use ( $siteurl ) {
				return $siteurl;
			}
		);
	}

	private function set_theme_urls( $template, $stylesheet ) {
		add_filter(
			'template_directory_uri',
			function () use ( $template ) {
				return $template;
			}
		);
		add_filter(
			'stylesheet_directory_uri',
			function () use ( $stylesheet ) {
				return $stylesheet;
			}
		);
	}

	private function localize( $content ) {
		$template          = new stdClass();
		$template->content = $content;
		return CBT_Theme_URLs::make_template_urls_local( $template )->content;
	}

	private function home_echo( $path ) {
		return sprintf( self::HOME_ECHO, "'" . $path . "'" );
	}

	public function test_site_url_in_html_attribute_uses_home_url() {
		$this->assertSame(
			'<a href="' . $this->home_echo( '/about/' ) . '">About</a>',
			$this->localize( '<a href="http://example.org/about/">About</a>' )
		);
	}

	public function test_site_root_url_uses_home_url_root() {
		$this->assertSame(
			'<a href="' . $this->home_echo( '/' ) . '">Home</a> <a href="' . $this->home_echo( '/' ) . '">Home</a>',
			$this->localize( '<a href="http://example.org">Home</a> <a href="http://example.org/">Home</a>' )
		);
	}

	public function test_query_string_and_fragment_are_kept() {
		$this->assertSame(
			'<a href="' . $this->home_echo( '/shop/?sort=asc#list' ) . '">Shop</a>',
			$this->localize( '<a href="http://example.org/shop/?sort=asc#list">Shop</a>' )
		);
	}

	public function test_url_in_css_url_stops_at_closing_parenthesis() {
		$this->assertSame(
			'<div style="background-image:url(' . self::THEME_ECHO . '/assets/bg.png)"></div>',
			$this->localize( '<div style="background-image:url(' . self::THEME_URL . '/assets/bg.png)"></div>' )
		);
	}

	public function test_json_escaped_url_in_block_attributes() {
		$json = wp_json_encode( array( 'url' => 'http://example.org/contact/' ) );
		$this->assertStringContainsString( '\\/', $json, 'Fixture should use JSON-escaped slashes.' );

		$this->assertSame(
			'<!-- wp:navigation-link {"url":"' . $this->home_echo( '/contact/' ) . '"} /-->',
			$this->localize( '<!-- wp:navigation-link ' . $json . ' /-->' )
		);
	}

	public function test_json_escaped_ampersand_is_decoded_in_home_url_path() {
		$attributes = serialize_block_attributes( array( 'url' => 'http://example.org/shop/?a=1&b=2' ) );

		$this->assertSame(
			'<!-- wp:button {"url":"' . $this->home_echo( '/shop/?a=1&b=2' ) . '"} /-->',
			$this->localize( '<!-- wp:button ' . $attributes . ' /-->' )
		);
	}

	public function test_url_inside_json_encoded_html_stops_at_encoded_quote() {
		$attributes = serialize_block_attributes( array( 'content' => '<a href="http://example.org/x/">Link</a>' ) );
		$markup     = '<!-- wp:test/block ' . $attributes . ' /-->';

		$this->assertSame(
			str_replace( 'http://example.org/x/', $this->home_echo( '/x/' ), $markup ),
			$this->localize( $markup )
		);
	}

	public function test_theme_file_url_uses_template_directory_uri() {
		$this->assertSame(
			'<img src="' . self::THEME_ECHO . '/assets/images/logo.svg"/>',
			$this->localize( '<img src="' . self::THEME_URL . '/assets/images/logo.svg"/>' )
		);
	}

	public function test_theme_file_url_in_block_attributes_keeps_json_escaped_path() {
		$json = wp_json_encode( array( 'url' => self::THEME_URL . '/assets/images/hero.jpg' ) );

		$this->assertSame(
			'<!-- wp:cover {"url":"' . self::THEME_ECHO . '\\/assets\\/images\\/hero.jpg"} /-->',
			$this->localize( '<!-- wp:cover ' . $json . ' /-->' )
		);
	}

	public function test_child_theme_file_url_uses_stylesheet_directory_uri() {
		$this->set_theme_urls( self::THEME_URL, self::CHILD_URL );

		$this->assertSame(
			'<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/child.png"/>'
				. '<img src="' . self::THEME_ECHO . '/assets/parent.png"/>',
			$this->localize(
				'<img src="' . self::CHILD_URL . '/assets/child.png"/>'
				. '<img src="' . self::THEME_URL . '/assets/parent.png"/>'
			)
		);
	}

	public function test_site_url_is_used_when_wordpress_is_in_a_subdirectory() {
		$this->set_site_urls( 'http://example.org', 'http://example.org/wp' );

		$this->assertSame(
			'<a href="' . $this->home_echo( '/about/' ) . '">a</a>'
				. '<a href="<?php echo esc_url( site_url( \'/wp-login.php\' ) ); ?>">b</a>',
			$this->localize(
				'<a href="http://example.org/about/">a</a>'
				. '<a href="http://example.org/wp/wp-login.php">b</a>'
			)
		);
	}

	public function test_home_url_is_used_when_home_is_in_a_subdirectory() {
		$this->set_site_urls( 'http://example.org/blog', 'http://example.org' );

		$this->assertSame(
			'<a href="' . $this->home_echo( '/post/' ) . '">a</a>'
				. '<a href="<?php echo esc_url( site_url( \'/blogger/\' ) ); ?>">b</a>',
			$this->localize(
				'<a href="http://example.org/blog/post/">a</a>'
				. '<a href="http://example.org/blogger/">b</a>'
			)
		);
	}

	public function test_urls_that_only_share_the_base_prefix_are_not_converted() {
		$this->set_site_urls( 'http://example.org/blog', 'http://example.org/blog' );
		$this->set_theme_urls( 'http://cdn.example.net/theme', 'http://cdn.example.net/theme' );

		$content = '<a href="http://example.org/blogger/">a</a>'
			. '<a href="http://example.org/blog.co/x">b</a>'
			. '<a href="http://example.org:8080/blog/x">c</a>';

		$this->assertSame( $content, $this->localize( $content ) );
	}

	public function test_lookalike_host_and_other_port_are_not_converted() {
		$content = '<a href="http://example.org.co/x">a</a><a href="http://example.org:8080/x">b</a>';

		$this->assertSame( $content, $this->localize( $content ) );
	}

	public function test_external_url_is_not_converted() {
		$content = '<a href="https://wordpress.org/plugins/">x</a>';

		$this->assertSame( $content, $this->localize( $content ) );
	}

	public function test_content_without_site_urls_is_unchanged() {
		$content = '<!-- wp:paragraph --><p>No links here.</p><!-- /wp:paragraph -->';

		$this->assertSame( $content, $this->localize( $content ) );
	}

	public function test_url_literal_in_esc_url_is_replaced_with_php_call() {
		$this->assertSame(
			"<a href=\"<?php echo esc_url( home_url( '/faq' ) ); ?>\">FAQ</a>",
			$this->localize( "<a href=\"<?php echo esc_url( 'http://example.org/faq' ); ?>\">FAQ</a>" )
		);
	}

	public function test_theme_url_literal_in_esc_url_appends_path() {
		$this->assertSame(
			"<img src=\"<?php echo esc_url( get_template_directory_uri() . '/assets/a.png' ); ?>\"/>",
			$this->localize( "<img src=\"<?php echo esc_url( '" . self::THEME_URL . "/assets/a.png' ); ?>\"/>" )
		);
	}

	public function test_url_literal_in_localized_link_is_replaced() {
		// Output shape of CBT_Theme_Locale for a paragraph with a link.
		$content = "<p><?php echo sprintf( esc_html__( 'Visit %1\$sour shop%2\$s', 'theme' ), '<a href=\"' . esc_url( 'http://example.org/shop/' ) . '\">', '</a>' ); ?></p>";

		$this->assertSame(
			str_replace( "esc_url( 'http://example.org/shop/' )", "esc_url( home_url( '/shop/' ) )", $content ),
			$this->localize( $content )
		);
	}

	public function test_other_php_strings_are_not_changed() {
		$content = "<p><?php esc_html_e( 'Go to http://example.org/shop', 'theme' ); ?></p>"
			. "<p><?php esc_html_e( 'http://example.org/only', 'theme' ); ?></p>"
			. "<p><?php echo esc_url( 'http://example.org/a b' ); ?></p>";

		$this->assertSame( $content, $this->localize( $content ) );
	}

	public function test_external_url_literal_in_esc_url_is_not_changed() {
		$content = "<a href=\"<?php echo esc_url( 'https://wordpress.org/' ); ?>\">x</a>";

		$this->assertSame( $content, $this->localize( $content ) );
	}

	public function test_template_without_string_content_is_returned_unchanged() {
		$template = new stdClass();
		$this->assertSame( $template, CBT_Theme_URLs::make_template_urls_local( $template ) );

		$template->content = null;
		$this->assertNull( CBT_Theme_URLs::make_template_urls_local( $template )->content );
	}

	public function test_template_export_localizes_urls_by_default() {
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '<!-- wp:paragraph --><p><a href="http://example.org/about/">About</a></p><!-- /wp:paragraph -->';

		$exported = CBT_Theme_Templates::prepare_template_for_export( $template );

		// A template that gains PHP is moved into a pattern.
		$this->assertStringContainsString( 'wp:pattern', $exported->content );
		$this->assertStringContainsString( $this->home_echo( '/about/' ), $exported->pattern );
		$this->assertStringNotContainsString( 'http://example.org/about/', $exported->pattern );
	}

	public function test_template_export_keeps_urls_when_disabled() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph --><p><a href="http://example.org/about/">About</a></p><!-- /wp:paragraph -->';

		$exported = CBT_Theme_Templates::prepare_template_for_export(
			$template,
			null,
			array(
				'localizeImages' => false,
				'localizeUrls'   => false,
			)
		);

		$this->assertStringContainsString( 'http://example.org/about/', $exported->content );
		$this->assertStringNotContainsString( 'home_url', $exported->content );
	}

	public function test_pattern_export_localizes_urls() {
		$pattern          = new stdClass();
		$pattern->content = '<!-- wp:paragraph --><p><a href="http://example.org/about/">About</a></p><!-- /wp:paragraph -->';

		$exported = CBT_Theme_Patterns::prepare_pattern_for_export( $pattern, array( 'localizeImages' => false ) );

		$this->assertStringContainsString( $this->home_echo( '/about/' ), $exported->content );
	}

	public function test_pattern_export_keeps_urls_when_disabled() {
		$pattern          = new stdClass();
		$pattern->content = '<!-- wp:paragraph --><p><a href="http://example.org/about/">About</a></p><!-- /wp:paragraph -->';

		$exported = CBT_Theme_Patterns::prepare_pattern_for_export(
			$pattern,
			array(
				'localizeImages' => false,
				'localizeUrls'   => false,
			)
		);

		$this->assertStringContainsString( 'http://example.org/about/', $exported->content );
	}
}
