<?php
/**
 * @package Create_Block_Theme
 */
class Create_Block_Theme_Templates extends WP_UnitTestCase {

	public function test_paragraphs_are_localized() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph --><p>This is text to localize</p><!-- /wp:paragraph -->';
		$new_template      = Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'This is text to localize', $new_template->content );
		$this->assertStringNotContainsString( '<p>This is text to localize</p>', $new_template->content );

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

	// public function test_buttons_are_localized() {
	// 	$template = new stdClass();
	// 	$template->content = '
	// 		<!-- wp:buttons --><div class="wp-block-buttons">
	// 			<!-- wp:button -->
	// 				<div class="wp-block-button">
	// 					<a class="wp-block-button__link wp-element-button">This is text to localize </a>
	// 				</div>
	// 			<!-- /wp:button -->
	// 		</div>
	// 	<!-- /wp:buttons -->';
	// 	$new_template = Theme_Templates::escape_text_in_template( $template );
	// 	$this->assertStringContainsString( 'This is text to localize', $new_template->content );
	// 	$this->assertStringNotContainsString( '<a class="wp-block-button__link wp-element-button">This is text to localize </a>', $new_template->content );

	// }
}
