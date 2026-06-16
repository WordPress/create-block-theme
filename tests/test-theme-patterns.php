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
		// after sanitisation must NOT contain ANY `<?` followed by non-`xml`.
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

	public function test_pattern_from_wp_block_preserves_xml_declaration() {
		// The XML declaration `<` + `?xml version="1.0"` + `?` + `>` is
		// legitimate (appears in SVG content in block markup) and MUST
		// survive sanitisation.
		$safe    = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="3"/></svg>';
		$post    = $this->make_wp_block_post( $safe );
		$pattern = CBT_Theme_Patterns::pattern_from_wp_block( $post );
		$body    = substr( $pattern->content, strpos( $pattern->content, '?>' ) + 2 );

		$this->assertStringContainsString( '<?xml', $body );
	}

	public function test_strip_php_tags_handles_non_string_input() {
		// Defensive guard — non-string input should round-trip without error.
		// We can't call the private helper directly; exercise it via
		// pattern_from_wp_block by constructing a post stub with non-string content.
		$post               = new stdClass();
		$post->ID           = 0;
		$post->post_title   = 'Stub';
		$post->post_content = null;
		// Should not throw — but pattern_from_wp_block also reads other fields,
		// so use a real wp_block post and then null out post_content.
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
		// Use whatever theme is currently active in the test environment.
		// wp-env bootstraps with a working block theme so writes succeed.
		$patterns_dir          = get_stylesheet_directory() . '/patterns';
		$expected_pattern_path = $patterns_dir . '/cbt-pattern-rce-probe.php';

		// Track whether we created the patterns/ directory (for cleanup).
		$created_patterns_dir = ! is_dir( $patterns_dir );

		// Make sure the destination doesn't pre-exist from a prior run.
		if ( file_exists( $expected_pattern_path ) ) {
			unlink( $expected_pattern_path );
		}

		// Create a malicious wp_block post. We bypass KSES the same way the
		// other tests in this class do (via the make_wp_block_post helper) —
		// this simulates an Editor user who has the `unfiltered_html` cap.
		$this->make_wp_block_post(
			'<p>safe</p><?php file_put_contents("/tmp/cbt_should_not_be_written.txt", "pwned"); ?>',
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

		// Cleanup: remove the generated pattern file, and the patterns/ dir if we created it.
		unlink( $expected_pattern_path );
		if ( $created_patterns_dir && is_dir( $patterns_dir ) && count( scandir( $patterns_dir ) ) === 2 ) {
			rmdir( $patterns_dir );
		}

		// Final paranoia: assert the marker file was NOT written (i.e. the
		// stripped PHP never executed).
		$this->assertFileDoesNotExist( '/tmp/cbt_should_not_be_written.txt' );
	}
}
