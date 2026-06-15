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
}
