<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Zip extends WP_UnitTestCase {

	/**
	 * Create a CBT_Zip_Archive backed by a temporary file. Returns array(
	 * $zip, $tmp_path ) so the caller can clean up.
	 */
	private function make_temp_zip( $slug = 'cbt-test' ) {
		$tmp_path = wp_tempnam( $slug . '.zip' );
		// wp_tempnam() creates the file; ZipArchive::OVERWRITE will replace it.
		$zip = CBT_Theme_Zip::create_zip( $tmp_path, $slug );
		if ( is_wp_error( $zip ) ) {
			@unlink( $tmp_path );
			$this->markTestSkipped( $zip->get_error_message() );
		}
		return array( $zip, $tmp_path );
	}

	public function test_add_media_to_zip_skips_php_url_without_downloading() {
		list( $zip, $tmp_path ) = $this->make_temp_zip( 'cbt-test' );

		$attempted = false;
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$tracker = function ( $preempt, $args, $url ) use ( &$attempted ) {
			$attempted = true;
			return new WP_Error( 'cbt_test_intercept', 'blocked by test' );
		};
		add_filter( 'pre_http_request', $tracker, 10, 3 );

		CBT_Theme_Zip::add_media_to_zip( $zip, array( 'http://example.com/evil.php' ) );

		remove_filter( 'pre_http_request', $tracker, 10 );
		$zip->close();
		@unlink( $tmp_path );

		$this->assertFalse( $attempted, 'download_url() must NOT be called for a disallowed-extension URL' );
	}

	public function test_add_media_to_zip_preserves_downloaded_file_until_close() {
		list( $zip, $tmp_path ) = $this->make_temp_zip( 'cbt-test' );

		$png_bytes = file_get_contents( __DIR__ . '/data/tiny.png' );

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$mock = function ( $preempt, $args, $url ) use ( $png_bytes ) {
			$tmp = isset( $args['filename'] ) ? $args['filename'] : null;
			if ( $tmp ) {
				file_put_contents( $tmp, $png_bytes );
			}
			return array(
				'headers'  => array(),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => '',
				'cookies'  => array(),
				'filename' => $tmp,
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );

		$added_media = CBT_Theme_Zip::add_media_to_zip( $zip, array( 'http://example.com/tinyzip.png' ) );

		remove_filter( 'pre_http_request', $mock, 10 );

		$this->assertSame( array( 'http://example.com/tinyzip.png' ), $added_media );

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- ZipArchive::close() may emit a warning if the archive ended up empty; the test asserts the return value instead.
		$closed = @$zip->close();
		$reader = null;
		try {
			$this->assertTrue( $closed, 'ZIP should close successfully after downloaded media is added.' );

			$reader = new ZipArchive();
			$this->assertTrue( $reader->open( $tmp_path ), 'ZIP should be readable after close.' );
			$this->assertSame( $png_bytes, $reader->getFromName( 'cbt-test/assets/images/tinyzip.png' ) );
		} finally {
			if ( $reader instanceof ZipArchive ) {
				$reader->close();
			}
			@unlink( $tmp_path );
		}
	}

	public function test_add_media_to_zip_does_not_return_mime_mismatch_url() {
		list( $zip, $tmp_path ) = $this->make_temp_zip( 'cbt-test' );

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$mock = function ( $preempt, $args, $url ) {
			$tmp = isset( $args['filename'] ) ? $args['filename'] : null;
			if ( $tmp ) {
				file_put_contents( $tmp, "<?php echo 'pwned'; ?>" );
			}
			return array(
				'headers'  => array(),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => '',
				'cookies'  => array(),
				'filename' => $tmp,
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );

		$added_media = CBT_Theme_Zip::add_media_to_zip( $zip, array( 'http://example.com/disguised.png' ) );

		remove_filter( 'pre_http_request', $mock, 10 );
		$zip->close();
		@unlink( $tmp_path );

		$this->assertSame( array(), $added_media, 'MIME mismatch URLs should not be reported as added to the ZIP.' );
	}

	/**
	 * Integration-level test for the font sink would require seeding the
	 * user global-styles post and getting WP_Theme_JSON_Resolver to surface
	 * the family via get_user_activated_fonts(); the resolver caches per
	 * request, so the test setup is too fragile to assert anything
	 * meaningful without further plumbing. The font URL validator itself is
	 * covered by tests in test-theme-fonts.php; the wiring in
	 * CBT_Theme_Zip::add_activated_fonts_to_zip() mirrors the already-tested
	 * wiring in CBT_Theme_Fonts::copy_font_assets_to_theme().
	 */
}
