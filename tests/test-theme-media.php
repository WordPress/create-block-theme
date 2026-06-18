<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Media extends WP_UnitTestCase {

	public function test_make_images_block_local() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image -->
			<figure class="wp-block-image"><img src="http://example.com/image.jpg" alt="Alternative Text" /></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Media::make_template_images_local( $template );

		// The image should be replaced with a relative URL
		$this->assertStringNotContainsString( 'http://example.com/image.jpg', $new_template->content );
		$this->assertStringContainsString( 'get_template_directory_uri', $new_template->content );
		$this->assertStringContainsString( '/assets/images', $new_template->content );

	}

	public function test_make_cover_block_local() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:cover {"url":"http://example.com/image.jpg"} -->
				<div class="wp-block-cover">
					<img class="wp-block-cover__image-background wp-image-628" alt="" src="http://example.com/image.jpg" data-object-fit="cover"/>
					<div class="wp-block-cover__inner-container">
					</div>
				</div>
			<!-- /wp:cover -->
		';
		$new_template      = CBT_Theme_Media::make_template_images_local( $template );

		// The image should be replaced with a relative URL
		$this->assertStringNotContainsString( 'http://example.com/image.jpg', $new_template->content );
		$this->assertStringContainsString( 'get_template_directory_uri', $new_template->content );
		$this->assertStringContainsString( '/assets/images', $new_template->content );
	}

	public function test_template_with_media_correctly_prepared() {
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '
			<!-- wp:image -->
			<figure class="wp-block-image"><img src="http://example.com/image.jpg" alt="Alternative Text" /></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Templates::prepare_template_for_export( $template );

		// Content should be replaced with a pattern block
		$this->assertStringContainsString( '<!-- wp:pattern', $new_template->content );

		// The media to install should be in the collection
		$this->assertContains( 'http://example.com/image.jpg', $new_template->media );

		// The pattern is correctly encoded
		$this->assertStringContainsString( '<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/image.jpg"', $new_template->pattern );

	}

	public function test_make_group_block_local() {
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '
			<!-- wp:group {"style":{"background":{"backgroundImage":{"url":"http://example.com/image.jpg","id":31,"source":"file","title":"Screenshot 2024-04-18 at 14-08-49 Blog Home ‹ Template ‹ a8c-wp-env ‹ Editor — WordPress"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group"></div>
			<!-- /wp:group -->
		';
		$new_template      = CBT_Theme_Templates::prepare_template_for_export( $template );

		// Content should be replaced with a pattern block
		$this->assertStringContainsString( '<!-- wp:pattern', $new_template->content );

		// The media to install should be in the collection
		$this->assertContains( 'http://example.com/image.jpg', $new_template->media );

		// The pattern is correctly encoded
		$this->assertStringContainsString( '{"backgroundImage":{"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/image.jpg"', $new_template->pattern );

	}

	public function test_is_allowed_media_url_accepts_image_extension() {
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/cat.jpg' ) );
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/path/photo.png' ) );
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'https://example.com/clip.webp' ) );
	}

	public function test_is_allowed_media_url_accepts_video_extension() {
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/movie.mp4' ) );
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/movie.webm' ) );
	}

	public function test_is_allowed_media_url_rejects_php_extension() {
		$this->assertFalse( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/evil.php' ) );
	}

	public function test_is_allowed_media_url_rejects_other_dangerous_extensions() {
		$urls = array(
			'http://example.com/evil.phtml',
			'http://example.com/evil.phar',
			'http://example.com/evil.html',
			'http://example.com/.htaccess',
			'http://example.com/evil.php5',
			'http://example.com/no-extension',
		);
		foreach ( $urls as $url ) {
			$this->assertFalse( CBT_Theme_Media::is_allowed_media_url( $url ), "Should reject: $url" );
		}
	}

	public function test_is_allowed_media_url_is_case_insensitive() {
		$this->assertFalse( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/EVIL.PHP' ) );
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/CAT.JPG' ) );
	}

	public function test_is_allowed_media_url_ignores_query_string() {
		$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/cat.jpg?v=2' ) );
		$this->assertFalse( CBT_Theme_Media::is_allowed_media_url( 'http://example.com/evil.php?disguised=cat.jpg' ) );
	}

	public function test_is_allowed_media_file_accepts_real_png() {
		$tmp = wp_tempnam( 'cbt-test-png' );
		copy( __DIR__ . '/data/tiny.png', $tmp );
		$ok = CBT_Theme_Media::is_allowed_media_file( $tmp, 'http://example.com/cat.png' );
		@unlink( $tmp );
		$this->assertTrue( $ok );
	}

	public function test_is_allowed_media_file_rejects_php_body_with_image_url() {
		$tmp = wp_tempnam( 'cbt-test-php' );
		copy( __DIR__ . '/data/evil.php.txt', $tmp );
		$ok = CBT_Theme_Media::is_allowed_media_file( $tmp, 'http://example.com/evil.jpg' );
		@unlink( $tmp );
		$this->assertFalse( $ok );
	}

	public function test_is_allowed_media_file_rejects_missing_file() {
		$this->assertFalse( CBT_Theme_Media::is_allowed_media_file( '/nonexistent/tmp/file', 'http://example.com/cat.jpg' ) );
	}

	/**
	 * Helper: write magic bytes to a tmp file and assert acceptance.
	 */
	private function assert_media_magic_accepted( $bytes, $url ) {
		$tmp = wp_tempnam( 'cbt-test-magic' );
		file_put_contents( $tmp, $bytes );
		$ok = CBT_Theme_Media::is_allowed_media_file( $tmp, $url );
		@unlink( $tmp );
		$this->assertTrue( $ok, "Should accept magic bytes for $url" );
	}

	public function test_is_allowed_media_file_accepts_jpeg_magic() {
		$this->assert_media_magic_accepted( "\xff\xd8\xff\xe0" . str_repeat( "\x00", 16 ), 'http://example.com/cat.jpg' );
	}

	public function test_is_allowed_media_file_accepts_gif_magic() {
		$this->assert_media_magic_accepted( 'GIF89a' . str_repeat( "\x00", 16 ), 'http://example.com/cat.gif' );
	}

	public function test_is_allowed_media_file_accepts_webp_magic() {
		// RIFF + 4-byte size (any) + 'WEBP' + payload.
		$this->assert_media_magic_accepted( "RIFF\x00\x00\x00\x00WEBP" . str_repeat( "\x00", 16 ), 'http://example.com/cat.webp' );
	}

	public function test_is_allowed_media_file_accepts_avif_magic() {
		// ISO BMFF: 4-byte size + 'ftyp' + 'avif' brand + payload.
		$this->assert_media_magic_accepted( "\x00\x00\x00\x20" . 'ftypavif' . str_repeat( "\x00", 16 ), 'http://example.com/cat.avif' );
	}

	public function test_is_allowed_media_file_accepts_avif_sequence_brand() {
		// AVIF image sequence uses 'avis' brand.
		$this->assert_media_magic_accepted( "\x00\x00\x00\x20" . 'ftypavis' . str_repeat( "\x00", 16 ), 'http://example.com/cat.avif' );
	}

	public function test_is_allowed_media_file_accepts_svg_content() {
		$this->assert_media_magic_accepted( '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="3"/></svg>', 'http://example.com/cat.svg' );
	}

	public function test_is_allowed_media_file_accepts_svg_with_xml_declaration() {
		// Use a `?` ` >` split for the closing tag so PHP does not exit the
		// file's PHP mode inside this string literal.
		$payload = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . '<svg xmlns="http://www.w3.org/2000/svg"/>';
		$this->assert_media_magic_accepted( $payload, 'http://example.com/cat.svg' );
	}

	public function test_is_allowed_media_file_accepts_mp4_ftyp() {
		// ISO BMFF: 4-byte size + 'ftyp' + brand. Covers mp4, m4v, mov, 3gp, 3g2.
		$this->assert_media_magic_accepted( "\x00\x00\x00\x20" . 'ftypisom' . str_repeat( "\x00", 16 ), 'http://example.com/cat.mp4' );
	}

	public function test_is_allowed_media_file_accepts_webm_magic() {
		$this->assert_media_magic_accepted( "\x1a\x45\xdf\xa3" . str_repeat( "\x00", 16 ), 'http://example.com/cat.webm' );
	}

	public function test_is_allowed_media_file_accepts_ogv_magic() {
		$this->assert_media_magic_accepted( 'OggS' . str_repeat( "\x00", 16 ), 'http://example.com/cat.ogv' );
	}

	public function test_is_allowed_media_file_accepts_wmv_asf_magic() {
		$this->assert_media_magic_accepted( "\x30\x26\xb2\x75\x8e\x66\xcf\x11" . str_repeat( "\x00", 16 ), 'http://example.com/cat.wmv' );
	}

	public function test_is_allowed_media_file_accepts_avi_riff() {
		// RIFF + 4-byte size (any) + 'AVI ' + payload.
		$this->assert_media_magic_accepted( "RIFF\x00\x00\x00\x00AVI " . str_repeat( "\x00", 16 ), 'http://example.com/cat.avi' );
	}

	public function test_is_allowed_media_file_accepts_mpeg_magic() {
		$this->assert_media_magic_accepted( "\x00\x00\x01\xb3" . str_repeat( "\x00", 16 ), 'http://example.com/cat.mpg' );
	}

	public function test_is_allowed_media_file_rejects_random_bytes() {
		$tmp = wp_tempnam( 'cbt-test-random' );
		file_put_contents( $tmp, 'this is just random text that does not match any magic' );
		$ok = CBT_Theme_Media::is_allowed_media_file( $tmp, 'http://example.com/cat.jpg' );
		@unlink( $tmp );
		$this->assertFalse( $ok );
	}

	public function test_is_allowed_media_file_rejects_extension_mismatch() {
		$tmp = wp_tempnam( 'cbt-test-svg-as-jpg' );
		file_put_contents( $tmp, '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="3"/></svg>' );
		$ok = CBT_Theme_Media::is_allowed_media_file( $tmp, 'http://example.com/cat.jpg' );
		@unlink( $tmp );
		$this->assertFalse( $ok, 'Downloaded media bytes should match the URL extension before being saved under that filename.' );
	}

	public function test_make_relative_media_url_uses_path_filename_not_query_basename() {
		$relative_url = CBT_Theme_Media::make_relative_media_url( 'http://example.com/cat.jpg?/evil.php' );

		$this->assertStringContainsString( '/assets/images/cat.jpg', $relative_url );
		$this->assertStringNotContainsString( 'evil.php', $relative_url );
	}

	public function test_add_media_to_local_skips_php_url_without_downloading() {
		$theme_assets = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
		$malicious    = $theme_assets . 'evil.php';

		// Make sure the dir exists and the file is NOT pre-existing.
		if ( file_exists( $malicious ) ) {
			unlink( $malicious );
		}

		// Track whether any HTTP request gets attempted; allowlist short-circuits before download_url.
		$attempted = false;
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$tracker = function ( $preempt, $args, $url ) use ( &$attempted ) {
			$attempted = true;
			return new WP_Error( 'cbt_test_intercept', 'blocked by test' );
		};
		add_filter( 'pre_http_request', $tracker, 10, 3 );

		CBT_Theme_Media::add_media_to_local( array( 'http://example.com/evil.php' ) );

		remove_filter( 'pre_http_request', $tracker, 10 );

		$this->assertFalse( $attempted, 'download_url() must NOT be called for a disallowed-extension URL' );
		$this->assertFileDoesNotExist( $malicious );
	}

	public function test_is_allowed_media_url_rejects_multi_extension_polyglots() {
		$urls = array(
			'http://example.com/evil.php.jpg',
			'http://example.com/evil.phtml.png',
			'http://example.com/evil.phar.gif',
			'http://example.com/sneaky.htaccess.jpg',
			'http://example.com/inject.html.png',
			'http://example.com/evil.PHP.jpg', // case-insensitive
		);
		foreach ( $urls as $url ) {
			$this->assertFalse( CBT_Theme_Media::is_allowed_media_url( $url ), "Should reject polyglot: $url" );
		}
	}

	public function test_is_allowed_media_url_accepts_multi_dot_filenames() {
		// Legitimate multi-dot filenames where NO interior segment is dangerous.
		$urls = array(
			'http://example.com/image.full.size.jpg',
			'http://example.com/photo.v2.png',
			'http://example.com/clip.final.mp4',
		);
		foreach ( $urls as $url ) {
			$this->assertTrue( CBT_Theme_Media::is_allowed_media_url( $url ), "Should accept legit multi-dot: $url" );
		}
	}

	public function test_add_media_to_local_writes_legit_png() {
		$theme_assets_dir = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
		$expected_path    = $theme_assets_dir . 'tinytest.png';

		if ( file_exists( $expected_path ) ) {
			unlink( $expected_path );
		}

		$png_bytes         = file_get_contents( __DIR__ . '/data/tiny.png' );
		$captured_tmp_path = null;

		// Intercept download_url and write the real PNG bytes to its tmp file.
		// download_url internally calls wp_safe_remote_get with stream=true and a
		// `filename` arg; we short-circuit by returning a body, which download_url
		// then writes to its tmp path via the WP HTTP API.
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$mock = function ( $preempt, $args, $url ) use ( $png_bytes, &$captured_tmp_path ) {
			$captured_tmp_path = isset( $args['filename'] ) ? $args['filename'] : null;
			if ( $captured_tmp_path ) {
				file_put_contents( $captured_tmp_path, $png_bytes );
			}
			return array(
				'headers'  => array(),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => '',
				'cookies'  => array(),
				'filename' => $captured_tmp_path,
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );

		CBT_Theme_Media::add_media_to_local( array( 'http://example.com/tinytest.png' ) );

		remove_filter( 'pre_http_request', $mock, 10 );

		$this->assertFileExists( $expected_path, 'Legitimate PNG URL should have been written to the theme assets dir' );
		if ( file_exists( $expected_path ) ) {
			$this->assertSame( $png_bytes, file_get_contents( $expected_path ) );
			unlink( $expected_path );
		}
	}
}
