<?php
/**
 * @package Create_Block_Theme
 * @group templates
 */
class Test_Create_Block_Theme_Templates extends WP_UnitTestCase {

	/**
	 * Ensure that the string in a template is replaced with the appropriate PHP code
	 */
	public function test_paragraphs_are_localized() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph --><p>This is text to localize</p><!-- /wp:paragraph -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<p><?php echo wp_kses_post( __('This is text to localize', '') );?></p>", $new_template->content );
		$this->assertStringNotContainsString( '<p>This is text to localize</p>', $new_template->content );
	}

	/**
	 * Ensure that escape_text_in_template is not called when the localizeText flag is set to false
	 */
	public function test_paragraphs_are_not_localized() {
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '<!-- wp:paragraph --><p>This is text to not localize</p><!-- /wp:paragraph -->';
		$new_template      = CBT_Theme_Templates::prepare_template_for_export( $template, null, array( 'localizeText' => false ) );
		$this->assertStringContainsString( '<!-- wp:paragraph --><p>This is text to not localize</p><!-- /wp:paragraph -->', $new_template->content );
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
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('This is text to localize', '') );?>", $new_template->content );
		$this->assertStringNotContainsString( '<p>This is text to localize</p>', $new_template->content );
	}

	public function test_buttons_are_localized() {
		$template          = new stdClass();
		$template->content = '<!-- wp:button -->
					<div class="wp-block-button">
						<a class="wp-block-button__link wp-element-button">This is text to localize</a>
					</div>
				<!-- /wp:button -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('This is text to localize', '') );?>", $new_template->content );
		$this->assertStringNotContainsString( '<a class="wp-block-button__link wp-element-button">This is text to localize</a>', $new_template->content );
	}

	public function test_headings_are_localized() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:heading -->
			<h2 class="wp-block-heading">This is a heading to localize.</h2>
			<!-- /wp:heading -->
		';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('This is a heading to localize.', '') );?>", $new_template->content );
		$this->assertStringNotContainsString( '<h2 class="wp-block-heading">This is a heading to localize.</h2>', $new_template->content );
	}

	public function test_eliminate_theme_ref_from_template_part() {
		$template          = new stdClass();
		$template->content = '<!-- wp:template-part {"slug":"header","theme":"testtheme"} /-->';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:template-part {"slug":"header"} /-->', $new_template->content );
	}

	public function test_eliminate_nav_block_ref() {
		$template          = new stdClass();
		$template->content = '<!-- wp:navigation {"ref":4} /-->';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:navigation /-->', $new_template->content );
	}

	public function test_eliminate_nav_block_ref_in_nested_block() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group"><!-- wp:navigation {"ref":4} /--></div>
			<!-- /wp:group -->
		';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:navigation /-->', $new_template->content );
	}

	public function test_not_eliminate_nav_block_ref() {
		$template          = new stdClass();
		$template->slug    = 'test-template';
		$template->content = '<!-- wp:navigation {"ref":4} /-->';
		$new_template      = CBT_Theme_Templates::prepare_template_for_export( $template, null, array( 'removeNavRefs' => false ) );
		$this->assertStringContainsString( '<!-- wp:navigation {"ref":4} /-->', $new_template->content );
	}

	public function test_eliminate_id_from_image() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image {"id":635} -->
			<figure class="wp-block-image size-large"><img src="http://example.com/file.jpg" alt="" class="wp-image-635"/></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:image -->', $new_template->content );
		$this->assertStringNotContainsString( '<!-- wp:image {"id":635} -->', $new_template->content );
		$this->assertStringNotContainsString( 'wp-image-635', $new_template->content );
	}

	public function test_eliminate_taxQuery_from_query_loop() {
		$template          = new stdClass();
		$template->content = '
		<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"post_tag":[9]}}} -->
		<div class="wp-block-query">
			<!-- wp:post-template -->
				<!-- wp:post-title {"isLink":true} /-->
				<!-- wp:post-excerpt /-->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
		';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );
		$this->assertStringContainsString( '<!-- wp:query', $new_template->content );
		$this->assertStringNotContainsString( '"taxQuery":{"post_tag":[9]}', $new_template->content );
	}

	public function test_properly_encode_quotes_and_doublequotes() {
		$template          = new stdClass();
		$template->content = '<!-- wp:heading -->
			<h3 class="wp-block-heading">"This" is a ' . "'test'" . '</h3>
		<!-- /wp:heading -->';
		$escaped_template  = CBT_Theme_Templates::escape_text_in_template( $template );

		/* That looks like a mess, but what it should look like for REAL is <?php echo wp_kses_post( __('"This" is a \'test\'', '' ); ?> */
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('\"This\" is a \\'test\\'', '') );?>", $escaped_template->content );
	}

	public function test_properly_encode_lessthan_and_greaterthan() {
		$template          = new stdClass();
		$template->content = '<!-- wp:heading -->
			<h3 class="wp-block-heading">&lt;This> is a &lt;test&gt;</h3>
		<!-- /wp:heading -->';
		$escaped_template  = CBT_Theme_Templates::escape_text_in_template( $template );

		$this->assertStringContainsString( "<?php echo wp_kses_post( __('&lt;This> is a &lt;test&gt;', '') );?>", $escaped_template->content );
	}

	public function test_properly_encode_html_markup() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph -->
			<p><strong>Bold</strong> text has feelings &lt;&gt; TOO</p>
			<!-- /wp:paragraph -->';
		$escaped_template  = CBT_Theme_Templates::escape_text_in_template( $template );

		$this->assertStringContainsString( "<?php echo wp_kses_post( __('<strong>Bold</strong> text has feelings &lt;&gt; TOO', '') );?>", $escaped_template->content );
	}

	public function test_localize_alt_text_from_image() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image -->
			<figure class="wp-block-image"><img src="http://example.com/file.jpg" alt="This is alt text" /></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'alt="<?php echo wp_kses_post( __(\'This is alt text\', \'\') );?>"', $new_template->content );
	}

	public function test_localize_alt_text_from_cover() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:cover {"url":"http://example.com/file.jpg","alt":"This is alt text"} -->
			<div class="wp-block-cover">
			<span aria-hidden="true" class="wp-block-cover__background"></span>
			<img class="wp-block-cover__image-background" alt="<?php echo wp_kses_post( __(\'This is alt text\', \'\') );?>" src="http://example.com/file.jpg" data-object-fit="cover"/>
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph -->
				<p></p>
				<!-- /wp:paragraph -->
			</div>
			</div>
			<!-- /wp:cover -->
		';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		// Check the markup attribute
		$this->assertStringContainsString( 'alt="<?php echo wp_kses_post( __(\'This is alt text\', \'\') );?>"', $new_template->content );
	}

	public function test_localize_quote() {
		$template          = new stdClass();
		$template->content = '<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p>This is my Quote</p>
				<!-- /wp:paragraph -->
				<cite>Citation too</cite>
			</blockquote>
		<!-- /wp:quote -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('This is my Quote', '') );?>", $new_template->content );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Citation too', '') );?>", $new_template->content );
	}

	public function test_localize_pullquote() {
		$template          = new stdClass();
		$template->content = '<!-- wp:pullquote -->
			<figure class="wp-block-pullquote">
				<blockquote>
				<p>This is my Quote</p>
				<cite>Citation too</cite>
				</blockquote>
			</figure>
		<!-- /wp:pullquote -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('This is my Quote', '') );?>", $new_template->content );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Citation too', '') );?>", $new_template->content );
	}

	public function test_localize_list() {
		$template          = new stdClass();
		$template->content = '<!-- wp:list -->
			<ul>
			<!-- wp:list-item -->
			<li>Item One</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>Item Two</li>
			<!-- /wp:list-item -->
			</ul>
		<!-- /wp:list -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<li><?php echo wp_kses_post( __('Item One', '') );?></li>", $new_template->content );
	}

	public function test_localize_verse() {
		$template          = new stdClass();
		$template->content = '<!-- wp:verse -->
			<pre class="wp-block-verse">Here is some <strong>verse</strong> to localize</pre>
		<!-- /wp:verse -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Here is some <strong>verse</strong> to localize', '') );?>", $new_template->content );
	}

	public function test_localize_table() {
		$template          = new stdClass();
		$template->content = '<!-- wp:table -->
			<figure class="wp-block-table">
			<table class="has-fixed-layout">
				<thead><tr>
					<th>Header One</th>
					<th>Header Two</th>
				</tr></thead>
				<tbody>
					<tr>
						<td>Apples</td>
						<td>Oranges</td>
					</tr>
					<tr>
						<td>Pickles</td>
						<td>Bananas</td>
					</tr>
				</tbody>
				<tfoot><tr>
					<td>Footer One</td>
					<td>Footer Two</td>
				</tr></tfoot>
			</table>
			<figcaption class="wp-element-caption">This is my caption</figcaption>
			</figure>
		<!-- /wp:table -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<td><?php echo wp_kses_post( __('Apples', '') );?></td>", $new_template->content );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Header One', '') );?>", $new_template->content );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Footer One', '') );?>", $new_template->content );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('This is my caption', '') );?>", $new_template->content );
	}

	public function test_localize_media_text() {
		$template          = new stdClass();
		$template->content = '<!-- wp:media-text -->
			<div class="wp-block-media-text is-stacked-on-mobile">
			<figure class="wp-block-media-text__media">
				<img src="http://example.com/file.jpg" alt="Alt Text Is Here" />
			</figure>
			<div class="wp-block-media-text__content">
				<!-- wp:paragraph -->
				<p>Content to Localize</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:media-text -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Content to Localize', '') );?>", $new_template->content );
		$this->assertStringContainsString( "<?php echo wp_kses_post( __('Alt Text Is Here', '') );?>", $new_template->content );
	}

	public function test_localize_cover_block_children() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:cover -->
			<div class="wp-block-cover">
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph -->
				<p>This is text to localize</p>
				<!-- /wp:paragraph -->
			</div>
			</div>
			<!-- /wp:cover -->
		';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );

		$this->assertStringContainsString( "<p><?php echo wp_kses_post( __('This is text to localize', '') );?></p>", $new_template->content );
	}

	public function test_localize_nested_cover_block_children() {
		$template          = new stdClass();
		$template->content = '
		<!-- wp:cover -->
		<div class="wp-block-cover">
		<div class="wp-block-cover__inner-container">
			<!-- wp:cover {"url":"http://localhost:4759/wp-content/themes/pub/cover-test/assets/images/cover-inner.png","id":82,"dimRatio":0,"customOverlayColor":"#64554a","isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":1},"minHeight":100,"minHeightUnit":"vh","contentPosition":"top center","style":{"spacing":{"padding":{"top":"150px","right":"0","bottom":"150px","left":"0"}}},"layout":{"type":"default"}} -->
			<div class="wp-block-cover has-custom-content-position is-position-top-center" style="padding-top:150px;padding-right:0;padding-bottom:150px;padding-left:0;min-height:100vh">
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph -->
				<p>This is text to localize</p>
				<!-- /wp:paragraph -->
			</div></div>
			<!-- /wp:cover -->
		</div></div>
		<!-- /wp:cover -->
		';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );

		$this->assertStringContainsString( 'This is text to localize', $new_template->content );
	}

}
