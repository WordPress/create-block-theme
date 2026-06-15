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
