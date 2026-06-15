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
}
