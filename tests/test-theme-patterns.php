<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Patterns extends WP_UnitTestCase {

	/**
	 * Helper: build a wp_block post with the given content, return it as the
	 * stdClass `pattern_from_wp_block` expects (post object from the loop).
	 *
	 * KSES filters are temporarily removed during insertion so the raw payload
	 * survives storage. This simulates an Editor with `unfiltered_html`
	 * (default capability on single-site WordPress), which is the threat model
	 * the strip_php_tags() helper defends against.
	 */
	private function make_wp_block_post( $content, $title = 'Test Pattern' ) {
		kses_remove_filters();
		$post_id = $this->factory->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $content,
			)
		);
		kses_init_filters();
		return get_post( $post_id );
	}

	/**
	 * Assert that generated PHP source does not contain a callable function token.
	 *
	 * @param string $function_name The function name that must not be callable.
	 * @param string $php_code      The generated PHP source to inspect.
	 */
	private function assert_php_code_does_not_call_function( $function_name, $php_code ) {
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

	public function test_pattern_from_wp_block_strips_php_open_tag() {
		$post    = $this->make_wp_block_post( '<p>safe</p><?php phpinfo(); ?>' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );

		// The metadata docblock at the top legitimately contains "<?php".
		// Strip that header before checking the body.
		$body = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?php', $body );
		$this->assertStringContainsString( '<p>safe</p>', $body );
	}

	public function test_pattern_from_wp_block_strips_short_open_tag() {
		$post    = $this->make_wp_block_post( '<p>safe</p><?= "rce" ?>' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?=', $body );
		$this->assertStringNotContainsString( '<?', $body, 'No PHP open tag of any kind should remain in the body' );
	}

	public function test_pattern_from_wp_block_strips_bare_short_open_tag() {
		$post    = $this->make_wp_block_post( '<p>safe</p><? echo 1; ?>' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<? ', $body );
	}

	public function test_pattern_from_wp_block_strips_uppercase_php_tag() {
		$post    = $this->make_wp_block_post( '<p>safe</p><?PHP phpinfo(); ?>' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?PHP', $body );
		$this->assertStringNotContainsString( '<?php', $body );
	}

	public function test_pattern_from_wp_block_preserves_legitimate_block_markup() {
		$safe    = '<!-- wp:paragraph --><p>hello world</p><!-- /wp:paragraph -->';
		$post    = $this->make_wp_block_post( $safe );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );

		$this->assertStringContainsString( $safe, $pattern->content );
	}

	public function test_pattern_from_wp_block_preserves_metadata_php_block() {
		// The wrapper PHP docblock MUST survive; only the body is stripped.
		$post    = $this->make_wp_block_post( '<p>safe</p>' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );

		$this->assertStringContainsString( '<?php', $pattern->content );
		$this->assertStringContainsString( 'Title:', $pattern->content );
	}

	public function test_pattern_from_wp_block_keeps_title_escape_intact() {
		$post    = $this->make_wp_block_post( '<p>safe</p>', 'evil */ break' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );

		// PR #817 escapes `*/` in the title to `*&#47;`. Confirm still in effect.
		$this->assertStringContainsString( '*&#47;', $pattern->content );
		$this->assertStringNotContainsString( 'evil */ break', $pattern->content );
	}

	public function test_pattern_from_wp_block_strips_short_tag_followed_by_non_letter() {
		// Short-tag bypasses recognised by PHP when short_open_tag=1. The body
		// after sanitisation must NOT contain ANY `<?` open tag.
		$payloads = array(
			'<p>safe</p><?$x = phpinfo(); ?>',
			'<p>safe</p><?(phpinfo()); ?>',
			'<p>safe</p><?"" . phpinfo(); ?>',
			'<p>safe</p><?//comment' . "\n" . 'phpinfo(); ?>',
			'<p>safe</p><?/*comment*/ phpinfo(); ?>',
			'<p>safe</p><?;phpinfo(); ?>',
		);
		foreach ( $payloads as $payload ) {
			$post    = $this->make_wp_block_post( $payload );
			$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
			$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

			// The security property is that the `<?` open tag is removed —
			// once neutralised, any residual `phpinfo` text is inert HTML.
			$this->assertStringNotContainsString( '<?', $body, "Short-tag bypass survived: $payload" );
		}
	}

	public function test_pattern_from_wp_block_strips_xml_declaration() {
		// XML declarations are parsed as a short PHP open tag on hosts with
		// short_open_tag=1, producing a fatal parse error when the exported
		// pattern is loaded. Block patterns do not legitimately contain XML
		// declarations, so they are stripped along with PHP tags.
		$payload = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="3"/></svg>';
		$post    = $this->make_wp_block_post( $payload );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?xml', $body );
		// The surrounding SVG markup survives — only the `<?` open tag is removed.
		$this->assertStringContainsString( '<svg', $body );
	}

	public function test_strip_php_tags_handles_non_string_input() {
		// Defensive guard — non-string input should not cause errors.
		// Build a real wp_block post (so pattern_from_wp_block can read its other
		// fields) and then null out post_content to exercise the strip_php_tags
		// non-string path.
		$real_post               = $this->make_wp_block_post( '<p>safe</p>' );
		$real_post->post_content = null;
		$pattern                 = CBT_Theme_Patterns::pattern_from_wp_block( $real_post );
		$this->assertNotNull( $pattern );
	}

	public function test_pattern_from_wp_block_strips_script_language_php() {
		$payloads = array(
			'<p>safe</p><script language="php">phpinfo();</script>',
			'<p>safe</p><script language=\'php\'>phpinfo();</script>',
			'<p>safe</p><script language=php>phpinfo();</script>',
			'<p>safe</p><script LANGUAGE="PHP">phpinfo();</script>',
		);
		foreach ( $payloads as $payload ) {
			$post    = $this->make_wp_block_post( $payload );
			$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
			$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

			$this->assertStringNotContainsString( 'phpinfo', $body, "Inner PHP should be stripped: $payload" );
			$this->assertStringNotContainsString( '<script', $body, "Opening <script tag should be stripped: $payload" );
		}
	}

	public function test_pattern_from_wp_block_stabilizes_overlapping_open_tags() {
		$post    = $this->make_wp_block_post( '<p>safe</p><<??template data' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?', $body );
		$this->assertStringContainsString( '<p>safe</p>', $body );
	}

	public function test_pattern_from_wp_block_stabilizes_after_legacy_script_removal() {
		$post    = $this->make_wp_block_post( '<p>safe</p><<script language="php">bridge</script>?template data' );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?', $body );
		$this->assertStringContainsString( '<p>safe</p>', $body );
	}

	public function test_pattern_from_wp_block_preserves_unrelated_script_tags() {
		// `<script type="application/json">` and similar non-PHP scripts are legitimate
		// in block markup and MUST NOT be stripped.
		$safe    = '<script type="application/json">{"a":1}</script>';
		$post    = $this->make_wp_block_post( '<p>safe</p>' . $safe );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringContainsString( $safe, $body );
	}

	public function test_pattern_from_template_strips_php_open_tag() {
		// Sanitisation now lives in prepare_template_for_export (the public
		// entry point), not in pattern_from_template itself. Exercise the
		// pipeline that the export code actually uses.
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '<p>safe</p><?php phpinfo(); ?>';

		$options = array(
			'localizeText'   => false,
			'localizeImages' => false,
			'removeNavRefs'  => false,
		);

		$result = CBT_Theme_Templates::prepare_template_for_export( $template, null, $options );

		$this->assertStringNotContainsString( '<?php', $result->content );
		$this->assertStringContainsString( '<p>safe</p>', $result->content );
	}

	public function test_pattern_from_template_strips_script_language_php() {
		// Sanitisation now lives in prepare_template_for_export (the public
		// entry point), not in pattern_from_template itself.
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '<p>safe</p><script language="php">phpinfo();</script>';

		$options = array(
			'localizeText'   => false,
			'localizeImages' => false,
			'removeNavRefs'  => false,
		);

		$result = CBT_Theme_Templates::prepare_template_for_export( $template, null, $options );

		$this->assertStringNotContainsString( '<script', $result->content );
	}

	public function test_pattern_from_template_preserves_legitimate_block_markup() {
		$safe              = '<!-- wp:paragraph --><p>hello</p><!-- /wp:paragraph -->';
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = $safe;
		$result            = CBT_Theme_Patterns::pattern_from_template( $template );

		$this->assertStringContainsString( $safe, $result['content'] );
	}

	public function test_pattern_from_template_keeps_slug_escape_intact() {
		$template          = new stdClass();
		$template->slug    = 'evil */ break';
		$template->content = '<p>safe</p>';
		$result            = CBT_Theme_Patterns::pattern_from_template( $template );

		// PR #817 escapes `*/` in the slug to `*&#47;`. Confirm still in effect.
		$this->assertStringContainsString( '*&#47;', $result['content'] );
		$this->assertStringNotContainsString( 'evil */ break', $result['content'] );
	}

	public function test_prepare_template_for_export_preserves_trusted_localize_markers() {
		// When localizeText=true is enabled, CBT_Theme_Templates::escape_text_in_template
		// injects trusted PHP esc_html_e(...) markers into the template body.
		// Those trusted markers MUST survive the pattern export — only attacker-injected
		// PHP should be stripped, not the plugin's own translation helpers.
		$template          = new stdClass();
		$template->slug    = 'test-localize';
		$template->content = '<!-- wp:paragraph --><p>Hello world</p><!-- /wp:paragraph -->';

		$options = array(
			'localizeText'   => true,
			'localizeImages' => false,
			'removeNavRefs'  => false,
		);

		$result = CBT_Theme_Templates::prepare_template_for_export( $template, null, $options );

		// After export with localizeText=true, the pattern body should contain
		// a trusted PHP esc_html_e marker INCLUDING its opening tag. If the
		// strip is wrongly applied, the opening tag is gone, leaving a broken
		// fragment in the HTML body.
		$this->assertTrue( isset( $result->pattern ) && '' !== $result->pattern, 'paternize_template should populate ->pattern when trusted PHP is injected' );
		$this->assertStringContainsString( "<?php esc_html_e('Hello world'", $result->pattern, 'Trusted localization marker (with PHP open tag) must survive sanitisation' );
	}

	public function test_prepare_template_for_export_still_strips_attacker_php() {
		// Even with localizeText off, attacker PHP in template content MUST be stripped
		// before paternize / heredoc construction.
		$template          = new stdClass();
		$template->slug    = 'test-attacker';
		$template->content = '<!-- wp:paragraph --><p>safe</p><!-- /wp:paragraph --><?php phpinfo(); ?>';

		$options = array(
			'localizeText'   => false,
			'localizeImages' => false,
			'removeNavRefs'  => false,
		);

		$result = CBT_Theme_Templates::prepare_template_for_export( $template, null, $options );

		// paternize_template only runs when content contains a PHP open tag.
		// The strip should have removed it BEFORE paternize ran, so ->pattern
		// should be unset and ->content should be the original block markup
		// with the open tag removed. Note: residual `phpinfo` text remains in
		// the body but is inert HTML once the open tag is gone.
		$this->assertStringNotContainsString( '<?php', $result->content, 'Attacker PHP open tag must be stripped' );
		$this->assertFalse( isset( $result->pattern ), 'paternize_template should not run once the attacker open tag is stripped' );
	}

	public function test_add_patterns_to_theme_writes_sanitised_body_to_disk() {
		// Run inside a fresh test theme so the side effects of
		// add_patterns_to_theme — including replace_local_pattern_references()
		// rewriting existing template/pattern files and clear_user_*_customizations
		// deleting user-template posts — are scoped to a throwaway directory
		// and don't pollute subsequent tests in the suite.
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$test_theme_slug = $this->create_blank_theme();

		$expected_pattern_path = get_stylesheet_directory() . '/patterns/cbt-pattern-rce-probe.php';
		$marker                = '/tmp/cbt_should_not_be_written.txt';

		// The marker file lives outside the theme directory, so uninstall_theme
		// can't reach it — pre-delete defensively in case a prior failed run
		// left it behind.
		if ( file_exists( $marker ) ) {
			unlink( $marker );
		}

		// Create a malicious wp_block post. We bypass KSES the same way the
		// other tests in this class do (via the make_wp_block_post helper) —
		// this simulates an Editor user who has the `unfiltered_html` cap.
		$this->make_wp_block_post(
			'<p>safe</p><?php file_put_contents("' . $marker . '", "pwned"); ?>',
			'CBT Pattern RCE Probe'
		);

		// Run the export. NOTE: add_patterns_to_theme calls wp_delete_post on the
		// source wp_block at the end — that's intentional plugin behaviour; we
		// don't need to clean up the post ourselves.
		CBT_Theme_Patterns::add_patterns_to_theme();

		// Assert the file was written.
		$this->assertFileExists( $expected_pattern_path, 'Pattern file should have been written to the active theme' );

		// Assert the body of the file does NOT contain executable PHP outside
		// the metadata docblock.
		$contents = file_get_contents( $expected_pattern_path );
		$body     = substr( $contents, strpos( $contents, '?>' ) + 2 );

		$this->assertStringNotContainsString( '<?php', $body, 'Body of generated pattern file must not contain <?php' );
		$this->assertStringContainsString( '<p>safe</p>', $body, 'Legitimate markup must survive sanitisation' );

		// Final paranoia: assert the marker file was NOT written (i.e. the
		// stripped PHP never executed).
		$this->assertFileDoesNotExist( $marker );

		// Cleanup — uninstall_theme removes the entire test theme directory,
		// taking patterns/, templates/, parts/, etc. with it.
		$this->uninstall_theme( $test_theme_slug );
	}

	public function test_add_patterns_to_theme_localizes_backslash_quote_text() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$test_theme_slug = $this->create_blank_theme();

		$expected_pattern_path = get_stylesheet_directory() . '/patterns/cbt-localize-text-export-probe.php';
		$marker                = '/tmp/cbt_localize_text_export_marker.txt';

		if ( file_exists( $marker ) ) {
			unlink( $marker );
		}

		$payload = chr( 92 ) . '\'); file_put_contents("' . $marker . '", "unexpected"); //';

		$pattern_post = $this->make_wp_block_post(
			wp_slash( '<!-- wp:paragraph --><p>' . $payload . '</p><!-- /wp:paragraph -->' ),
			'CBT Localize Text Export Probe'
		);
		$this->assertStringContainsString( $payload, $pattern_post->post_content );

		CBT_Theme_Patterns::add_patterns_to_theme(
			array(
				'localizeText'   => true,
				'localizeImages' => false,
				'removeNavRefs'  => false,
			)
		);

		$this->assertFileExists( $expected_pattern_path, 'Pattern file should have been written to the active theme' );

		$contents = file_get_contents( $expected_pattern_path );

		$this->assertStringContainsString( 'esc_html_e', $contents, 'Pattern text should be localized' );
		$this->assertStringContainsString( addcslashes( $payload, "\\'" ), $contents, 'Pattern text should keep the escaped backslash and quote sequence' );
		$this->assert_php_code_does_not_call_function( 'file_put_contents', $contents );

		ob_start();
		include $expected_pattern_path;
		ob_end_clean();

		$this->assertFileDoesNotExist( $marker );

		$this->uninstall_theme( $test_theme_slug );
	}

	/**
	 * Create a fresh test theme via the plugin's REST endpoint and activate it.
	 *
	 * Mirrors the helper in Test_Create_Block_Theme_Fonts so this test class
	 * can isolate side effects of theme-modifying operations.
	 */
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

	/**
	 * Tear down the test theme created by create_blank_theme().
	 */
	private function uninstall_theme( $theme_slug ) {
		CBT_Theme_JSON_Resolver::write_user_settings( array() );
		delete_theme( $theme_slug );
	}
}
