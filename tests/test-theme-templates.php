<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Templates extends WP_UnitTestCase {

	public function test_paragraphs_are_localized() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph --><p>This is text to localize</p><!-- /wp:paragraph -->';
		$new_template      = Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'This is text to localize', $new_template->content );
		$this->assertStringNotContainsString( '<p>This is text to localize</p>', $new_template->content );

	}

	public function test_avoid_escaping_php_syntax() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:cover {"url":"<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/80D07FF0-3F59-4E68-9E30-FCFA0EF12F63-scaled.jpg","id":7,"dimRatio":50,"customOverlayColor":"#85847b","isDark":false,"layout":{"type":"constrained"}} -->
				<div class="wp-block-cover is-light"><span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#85847b"></span><img class="wp-block-cover__image-background wp-image-7" alt="" src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/80D07FF0-3F59-4E68-9E30-FCFA0EF12F63-scaled.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
				<p class="has-text-align-center has-large-font-size">This is the "title"</p>
				<!-- /wp:paragraph --></div></div>
			<!-- /wp:cover -->
		';
		$new_template      = Theme_Templates::escape_text_in_template( $template );

		$this->assertStringContainsString( '<?php', $new_template->content );
		$this->assertStringContainsString( '?>', $new_template->content );
		$this->assertStringNotContainsString( '\u003c?', $new_template->content );
		$this->assertStringNotContainsString( '?\u003e', $new_template->content );
	}

	public function test_paragraphs_in_groups_are_localized() {
		$template          = new stdClass();
		$template->content = '<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph -->
				<p>This is text to localize</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->';
		$new_template      = Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'This is text to localize', $new_template->content );
		$this->assertStringNotContainsString( '<p>This is text to localize</p>', $new_template->content );
	}

	public function test_buttons_are_localized() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:buttons --><div class="wp-block-buttons">
				<!-- wp:button -->
					<div class="wp-block-button">
						<a class="wp-block-button__link wp-element-button">This is text to localize</a>
					</div>
				<!-- /wp:button -->
			</div>
		<!-- /wp:buttons -->';
		$new_template      = Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'This is text to localize', $new_template->content );
		$this->assertStringNotContainsString( '<a class="wp-block-button__link wp-element-button">This is text to localize</a>', $new_template->content );
	}

	public function test_headings_are_localized() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:heading -->
			<h2 class="wp-block-heading">This is a heading to localize.</h2>
			<!-- /wp:heading -->
		';
		$new_template      = Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'This is a heading to localize.', $new_template->content );
		$this->assertStringNotContainsString( '<h2 class="wp-block-heading">This is a heading to localize.</h2>', $new_template->content );
	}

	public function test_eliminate_theme_ref_from_template_part() {
		$template          = new stdClass();
		$template->content = '<!-- wp:template-part {"slug":"header","theme":"testtheme"} /-->';
		$new_template      = Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:template-part {"slug":"header"} /-->', $new_template->content );
	}

	public function test_eliminate_nav_block_ref() {
		$template          = new stdClass();
		$template->content = '<!-- wp:navigation {"ref":4} /-->';
		$new_template      = Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:navigation /-->', $new_template->content );
	}

	public function test_eliminate_nav_block_ref_in_nested_block() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group"><!-- wp:navigation {"ref":4} /--></div>
			<!-- /wp:group -->
		';
		$new_template      = Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:navigation /-->', $new_template->content );
	}

	public function test_eliminate_id_from_image() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image {"id":635} -->
			<figure class="wp-block-image size-large"><img src="http://example.com/file.jpg" alt="" class="wp-image-635"/></figure>
			<!-- /wp:image -->
		';
		$new_template      = Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:image -->', $new_template->content );
		$this->assertStringNotContainsString( '<!-- wp:image {"id":635} -->', $new_template->content );
		$this->assertStringNotContainsString( 'wp-image-635', $new_template->content );
	}

	// TODO: I'm not sure of the proper way to format this property for testing or now to cause it to be
	// added via the Global Styles Panel.
	// public function test_eliminate_taxQuery_from_query_loop() {
	// 	$template          = new stdClass();
	// 	$template->content = '
	// 	<!-- wp:query {"queryId":43,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"metadata":{"categories":["posts"]}} -->
	// 	<div class="wp-block-query">
	// 		<!-- wp:post-template -->
	// 			<!-- wp:post-title {"isLink":true} /-->
	// 			<!-- wp:post-excerpt /-->
	// 		<!-- /wp:post-template -->
	// 	</div>
	// 	<!-- /wp:query -->
	// 	';
	// 	$new_template      = Theme_Templates::eliminate_environment_specific_content( $template );
	// 	$this->assertStringContainsString( '<!-- wp:query', $new_template->content );
	// 	$this->assertStringNotContainsString( '"queryId":43', $new_template->content );
	// }
}
