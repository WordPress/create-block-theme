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
		$this->assertStringContainsString( "<p><?php esc_html_e('This is text to localize', '');?></p>", $new_template->content );
		$this->assertStringNotContainsString( '<p>This is text to localize</p>', $new_template->content );
	}

	public function test_empty_paragraphs_are_not_localized() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( '<p></p>', $new_template->content );
		$this->assertStringNotContainsString( 'esc_html_e', $new_template->content );
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
		$this->assertStringContainsString( "<?php esc_html_e('This is text to localize', '');?>", $new_template->content );
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
		$this->assertStringContainsString( "<?php esc_html_e('This is text to localize', '');?>", $new_template->content );
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
		$this->assertStringContainsString( "<?php esc_html_e('This is a heading to localize.', '');?>", $new_template->content );
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

	public function test_eliminate_image_id_only_removes_matching_class_token() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image {"id":635} -->
			<figure class="wp-block-image"><img class="custom-wp-image-635 wp-image-635" alt=""/><figcaption>wp-image-635?template data</figcaption></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );

		$this->assertStringContainsString( 'class="custom-wp-image-635"', $new_template->content );
		$this->assertStringContainsString( '<figcaption>wp-image-635?template data</figcaption>', $new_template->content );
		$this->assertStringNotContainsString( ' wp-image-635"', $new_template->content );
	}

	public function test_eliminate_image_id_from_cover_with_fixed_background() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:cover {"url":"http://example.com/file.jpg","id":635,"hasParallax":true} -->
			<div class="wp-block-cover has-parallax"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div role="img" class="wp-block-cover__image-background wp-image-635 has-parallax" style="background-position:50% 50%;background-image:url(http://example.com/file.jpg)"></div><div class="wp-block-cover__inner-container"></div></div>
			<!-- /wp:cover -->
		';
		$new_template      = CBT_Theme_Templates::eliminate_environment_specific_content( $template );

		$this->assertStringNotContainsString( '"id":635', $new_template->content );
		$this->assertStringNotContainsString( 'wp-image-635', $new_template->content );
		$this->assertStringContainsString( 'class="wp-block-cover__image-background has-parallax"', $new_template->content );
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

		/* That looks like a mess, but what it should look like for REAL is <?php esc_html_e('"This" is a \'test\'', '');?> */
		$this->assertStringContainsString( "<?php esc_html_e('\"This\" is a \\'test\\'', '');?>", $escaped_template->content );
	}

	public function test_properly_encode_lessthan_and_greaterthan() {
		$template          = new stdClass();
		$template->content = '<!-- wp:heading -->
			<h3 class="wp-block-heading">&lt;This> is a &lt;test&gt;</h3>
		<!-- /wp:heading -->';
		$escaped_template  = CBT_Theme_Templates::escape_text_in_template( $template );

		$this->assertStringContainsString( "<?php esc_html_e('&lt;This> is a &lt;test&gt;', '');?>", $escaped_template->content );
	}

	public function test_properly_encode_html_markup() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph --><p><strong>Bold</strong> text has feelings &lt;&gt; TOO</p><!-- /wp:paragraph -->';
		$escaped_template  = CBT_Theme_Templates::escape_text_in_template( $template );

		$expected_output = '<!-- wp:paragraph --><p><?php /* Translators: 1. is the start of a \'strong\' HTML element, 2. is the end of a \'strong\' HTML element */ ' . "\n" . 'echo sprintf( esc_html__( \'%1$sBold%2$s text has feelings <> TOO\', \'\' ), \'<strong>\', \'</strong>\' ); ?></p><!-- /wp:paragraph -->';

		$this->assertStringContainsString( $expected_output, $escaped_template->content );
	}

	public function test_empty_alt_text_is_not_localized() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image -->
			<figure class="wp-block-image"><img src="http://example.com/file.jpg" alt="" /></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'alt=""', $new_template->content );
	}

	public function test_localize_alt_text_from_image() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:image -->
			<figure class="wp-block-image"><img src="http://example.com/file.jpg" alt="This is alt text" /></figure>
			<!-- /wp:image -->
		';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( 'alt="<?php esc_attr_e(\'This is alt text\', \'\');?>"', $new_template->content );
	}

	public function test_localize_alt_text_from_cover() {
		$template          = new stdClass();
		$template->content = '
			<!-- wp:cover {"url":"http://example.com/file.jpg","alt":"This is alt text"} -->
			<div class="wp-block-cover">
			<span aria-hidden="true" class="wp-block-cover__background"></span>
			<img class="wp-block-cover__image-background" alt="This is alt text" src="http://example.com/file.jpg" data-object-fit="cover"/>
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
		$this->assertStringContainsString( 'alt="<?php esc_attr_e(\'This is alt text\', \'\');?>"', $new_template->content );
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
		$this->assertStringContainsString( "<?php esc_html_e('This is my Quote', '');?>", $new_template->content );
		$this->assertStringContainsString( "<?php esc_html_e('Citation too', '');?>", $new_template->content );
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
		$this->assertStringContainsString( "<?php esc_html_e('This is my Quote', '');?>", $new_template->content );
		$this->assertStringContainsString( "<?php esc_html_e('Citation too', '');?>", $new_template->content );
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
		$this->assertStringContainsString( "<li><?php esc_html_e('Item One', '');?></li>", $new_template->content );
	}

	public function test_localize_verse() {
		$template          = new stdClass();
		$template->content = '<!-- wp:verse -->
			<pre class="wp-block-verse">Here is some <strong>verse</strong> to localize</pre>
		<!-- /wp:verse -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );

		$expected_output = '<!-- wp:verse -->
			<pre class="wp-block-verse"><?php /* Translators: 1. is the start of a \'strong\' HTML element, 2. is the end of a \'strong\' HTML element */ ' . "\n" . 'echo sprintf( esc_html__( \'Here is some %1$sverse%2$s to localize\', \'\' ), \'<strong>\', \'</strong>\' ); ?></pre>
		<!-- /wp:verse -->';

		$this->assertStringContainsString( $expected_output, $new_template->content );
	}

	public function test_localize_text_with_placeholders() {
		$template          = new stdClass();
		$template->content = '<!-- wp:paragraph -->
			<p>This is <strong>bold text</strong> with a %s placeholder</p>
		<!-- /wp:paragraph -->';
		$new_template      = CBT_Theme_Templates::escape_text_in_template( $template );
		$this->assertStringContainsString( '<?php /* Translators: 1. is the start of a \'strong\' HTML element, 2. is the end of a \'strong\' HTML element */ ' . "\n" . 'echo sprintf( esc_html__( \'This is %1$sbold text%2$s with a %%s placeholder\', \'\' ), \'<strong>\', \'</strong>\' ); ?>', $new_template->content );
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
		$this->assertStringContainsString( "<td><?php esc_html_e('Apples', '');?></td>", $new_template->content );
		$this->assertStringContainsString( "<?php esc_html_e('Header One', '');?>", $new_template->content );
		$this->assertStringContainsString( "<?php esc_html_e('Footer One', '');?>", $new_template->content );
		$this->assertStringContainsString( "<?php esc_html_e('This is my caption', '');?>", $new_template->content );
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
		$this->assertStringContainsString( "<?php esc_html_e('Content to Localize', '');?>", $new_template->content );
		$this->assertStringContainsString( "<?php esc_attr_e('Alt Text Is Here', '');?>", $new_template->content );
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

		$this->assertStringContainsString( "<p><?php esc_html_e('This is text to localize', '');?></p>", $new_template->content );
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

	public function test_localize_cover_repeated_background_via_attrs() {
		$template          = new stdClass();
		$template->content = '<!-- wp:cover {"style":{"background":{"backgroundImage":{"url":"http://example.com/bg.png","repeat":"repeat"}}}} -->\n'
			. '<div class="wp-block-cover"><div class="wp-block-cover__inner-container"></div></div><!-- /wp:cover -->';
		$new_template      = CBT_Theme_Media::make_template_images_local( $template );
		$this->assertStringContainsString( '<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/bg.png', $new_template->content );
	}

	public function test_localize_cover_repeated_background_inline_style() {
		$template          = new stdClass();
		$template->content = '<!-- wp:cover -->\n'
			. '<div class="wp-block-cover" style="background-image:url(\'http://example.com/pattern.webp\');background-repeat:repeat"></div><!-- /wp:cover -->';
		$new_template      = CBT_Theme_Media::make_template_images_local( $template );
		$this->assertStringContainsString( '<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern.webp', $new_template->content );
	}

	public function test_unedited_theme_pattern_is_replaced_with_pattern_reference() {
		$theme_slug      = wp_get_theme()->get_stylesheet();
		$pattern_slug    = $theme_slug . '/test-hero';
		$pattern_content = '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2>Hero Title</h2><!-- /wp:heading --></div><!-- /wp:group -->';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Test Hero',
				'content' => $pattern_content,
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:group {"metadata":{"patternName":"' . $pattern_slug . '","name":"Test Hero"},"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2>Hero Title</h2><!-- /wp:heading --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
		$this->assertStringNotContainsString( '<h2>Hero Title</h2>', $new_template->content );
	}

	public function test_edited_theme_pattern_is_kept_inline() {
		$theme_slug      = wp_get_theme()->get_stylesheet();
		$pattern_slug    = $theme_slug . '/test-hero-edited';
		$pattern_content = '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2>Original Title</h2><!-- /wp:heading --></div><!-- /wp:group -->';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Test Hero Edited',
				'content' => $pattern_content,
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:group {"metadata":{"patternName":"' . $pattern_slug . '"},"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading --><h2>Modified Title</h2><!-- /wp:heading --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringNotContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
		$this->assertStringContainsString( '<h2>Modified Title</h2>', $new_template->content );
	}

	public function test_unedited_nested_pattern_is_replaced() {
		$theme_slug      = wp_get_theme()->get_stylesheet();
		$pattern_slug    = $theme_slug . '/nested-pattern';
		$pattern_content = '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Nested Text</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Nested Pattern',
				'content' => $pattern_content,
			)
		);

		$template          = new stdClass();
		$template->slug    = 'page';
		$template->content = '<!-- wp:group {"align":"full"} --><div class="wp-block-group alignfull"><!-- wp:group {"metadata":{"patternName":"' . $pattern_slug . '"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>Nested Text</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
		$this->assertStringContainsString( 'wp:group {"align":"full"}', $new_template->content );
		$this->assertStringNotContainsString( '<p>Nested Text</p>', $new_template->content );
	}

	public function test_external_pattern_is_not_replaced() {
		$pattern_slug    = 'external-plugin/some-pattern';
		$pattern_content = '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>External Content</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'External Pattern',
				'content' => $pattern_content,
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:group {"metadata":{"patternName":"' . $pattern_slug . '"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>External Content</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringNotContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
		$this->assertStringContainsString( '<p>External Content</p>', $new_template->content );
	}

	public function test_pattern_references_disabled_via_options() {
		$theme_slug      = wp_get_theme()->get_stylesheet();
		$pattern_slug    = $theme_slug . '/disabled-option-pattern';
		$pattern_content = '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Disabled Option Pattern',
				'content' => $pattern_content,
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:group {"metadata":{"patternName":"' . $pattern_slug . '"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template, null, array( 'patternReferences' => false ) );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringNotContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
		$this->assertStringContainsString( '<p>Content</p>', $new_template->content );
	}

	public function test_preserve_pattern_slugs_keeps_wp_pattern_references_on_namespace_replace() {
		$previous_stylesheet = get_stylesheet();
		switch_theme( 'twentytwentyfive' );
		$old_slug = wp_get_theme()->get( 'TextDomain' );

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:pattern {"slug":"' . $old_slug . '/hero"} /--><!-- wp:paragraph --><p>' . $old_slug . '</p><!-- /wp:paragraph -->';

		$result = CBT_Theme_Templates::replace_template_namespace( $template, 'child-theme', true );

		switch_theme( $previous_stylesheet );

		$this->assertStringContainsString( '<!-- wp:pattern {"slug":"' . $old_slug . '/hero"} /-->', $result->content );
		$this->assertStringContainsString( '<p>child-theme</p>', $result->content );
	}

	public function test_namespace_replace_rewrites_pattern_references_by_default() {
		$previous_stylesheet = get_stylesheet();
		switch_theme( 'twentytwentyfive' );
		$old_slug = wp_get_theme()->get( 'TextDomain' );

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:pattern {"slug":"' . $old_slug . '/hero"} /-->';

		$result = CBT_Theme_Templates::replace_template_namespace( $template, 'clone-theme' );

		switch_theme( $previous_stylesheet );

		$this->assertStringContainsString( '{"slug":"clone-theme/hero"}', $result->content );
	}

	public function test_pattern_with_file_path_outside_active_theme_is_not_an_active_theme_pattern() {
		$theme_slug   = wp_get_theme()->get_stylesheet();
		$pattern_slug = $theme_slug . '/sibling-theme-pattern';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'    => 'Sibling',
				'content'  => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->',
				'filePath' => untrailingslashit( get_stylesheet_directory() ) . '-two/patterns/x.php',
			)
		);

		$result = CBT_Theme_Templates::is_active_theme_pattern( $pattern_slug );

		unregister_block_pattern( $pattern_slug );

		$this->assertFalse( $result );
	}

	public function test_pattern_containing_nested_pattern_reference_is_replaced() {
		$theme_slug = wp_get_theme()->get_stylesheet();
		$inner_slug = $theme_slug . '/nested-inner';
		$outer_slug = $theme_slug . '/nested-outer';

		register_block_pattern(
			$inner_slug,
			array(
				'title'   => 'Inner',
				'content' => '<!-- wp:paragraph --><p>Inner Text</p><!-- /wp:paragraph -->',
			)
		);
		register_block_pattern(
			$outer_slug,
			array(
				'title'   => 'Outer',
				'content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:pattern {"slug":"' . $inner_slug . '"} /--></div><!-- /wp:group -->',
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:group {"metadata":{"patternName":"' . $outer_slug . '"}} --><div class="wp-block-group"><!-- wp:paragraph {"metadata":{"patternName":"' . $inner_slug . '"}} --><p>Inner Text</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $inner_slug );
		unregister_block_pattern( $outer_slug );

		$this->assertStringContainsString( '<!-- wp:pattern {"slug":"' . $outer_slug . '"} /-->', $new_template->content );
		$this->assertStringNotContainsString( 'Inner Text', $new_template->content );
	}

	public function test_pattern_reference_replacement_can_be_filtered_off() {
		$theme_slug   = wp_get_theme()->get_stylesheet();
		$pattern_slug = $theme_slug . '/filtered-pattern';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Filtered',
				'content' => '<!-- wp:paragraph --><p>Filtered Text</p><!-- /wp:paragraph -->',
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:paragraph {"metadata":{"patternName":"' . $pattern_slug . '"}} --><p>Filtered Text</p><!-- /wp:paragraph -->';

		add_filter( 'cbt_replace_with_pattern_reference', '__return_false' );
		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );
		remove_filter( 'cbt_replace_with_pattern_reference', '__return_false' );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringContainsString( 'Filtered Text', $new_template->content );
		$this->assertStringNotContainsString( 'wp:pattern', $new_template->content );
	}

	public function test_gutenberg_injected_categories_and_migrated_attrs_still_match_pattern() {
		$theme_slug   = wp_get_theme()->get_stylesheet();
		$pattern_slug = $theme_slug . '/gutenberg-migrated';

		$pattern_content = "<!-- wp:group {\"layout\":{\"type\":\"constrained\"}} -->\n"
			. "<div class=\"wp-block-group\">\n"
			. "\t<!-- wp:heading {\"textAlign\":\"center\"} -->\n"
			. "\t<h2 class=\"wp-block-heading has-text-align-center\">Title</h2>\n"
			. "\t<!-- /wp:heading -->\n"
			. "\t<!-- wp:paragraph {\"align\":\"center\"} -->\n"
			. "\t<p class=\"has-text-align-center\">Body</p>\n"
			. "\t<!-- /wp:paragraph -->\n"
			. "</div>\n"
			. '<!-- /wp:group -->';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'      => 'Migrated Pattern',
				'categories' => array( 'call-to-action' ),
				'content'    => $pattern_content,
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:group {"metadata":{"categories":["call-to-action"],"patternName":"' . $pattern_slug . '","name":"Migrated Pattern"},"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}},"className":"wp-block-heading"} --><h2 class="wp-block-heading has-text-align-center">Title</h2><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}}} --><p class="has-text-align-center">Body</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
		$this->assertStringNotContainsString( 'Title', $new_template->content );
	}

	public function test_changed_alignment_after_migration_stays_inline() {
		$theme_slug   = wp_get_theme()->get_stylesheet();
		$pattern_slug = $theme_slug . '/gutenberg-alignment-edited';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Alignment Edited',
				'content' => '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Body</p><!-- /wp:paragraph -->',
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:paragraph {"metadata":{"patternName":"' . $pattern_slug . '"},"style":{"typography":{"textAlign":"right"}}} --><p class="has-text-align-right">Body</p><!-- /wp:paragraph -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringNotContainsString( 'wp:pattern', $new_template->content );
		$this->assertStringContainsString( 'has-text-align-right', $new_template->content );
	}

	public function test_serialization_only_html_differences_still_match_pattern() {
		$theme_slug   = wp_get_theme()->get_stylesheet();
		$pattern_slug = $theme_slug . '/html-normalization';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Html Normalization',
				'content' => "<!-- wp:paragraph -->\n<p class=\"b a\" style=\"color:red\">We&#039;re here</p>\n<!-- /wp:paragraph -->",
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:paragraph {"metadata":{"patternName":"' . $pattern_slug . '"}} --><p class="a b" style="color:red;">We\'re here</p><!-- /wp:paragraph -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringContainsString( '<!-- wp:pattern {"slug":"' . $pattern_slug . '"} /-->', $new_template->content );
	}

	public function test_added_inline_space_after_normalization_stays_inline() {
		$theme_slug   = wp_get_theme()->get_stylesheet();
		$pattern_slug = $theme_slug . '/inline-space-edit';

		register_block_pattern(
			$pattern_slug,
			array(
				'title'   => 'Inline Space',
				'content' => '<!-- wp:paragraph --><p>Hello<strong>World</strong></p><!-- /wp:paragraph -->',
			)
		);

		$template          = new stdClass();
		$template->slug    = 'home';
		$template->content = '<!-- wp:paragraph {"metadata":{"patternName":"' . $pattern_slug . '"}} --><p>Hello <strong>World</strong></p><!-- /wp:paragraph -->';

		$new_template = CBT_Theme_Templates::prepare_template_for_export( $template );

		unregister_block_pattern( $pattern_slug );

		$this->assertStringNotContainsString( 'wp:pattern', $new_template->content );
		$this->assertStringContainsString( 'Hello <strong>World</strong>', $new_template->content );
	}
}
