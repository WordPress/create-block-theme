<?php

class CBT_Theme_Patterns {
	/**
	 * Strip PHP execution tags from user-supplied pattern body content.
	 *
	 * Block patterns are HTML/block markup, not PHP. Any `<?php` (or short/
	 * legacy variants) in user content is treated as malicious and removed
	 * before the body is interpolated into the exported `.php` pattern file.
	 *
	 * This helper is `public static` because it is invoked from two pipelines:
	 *  1. `pattern_from_wp_block()` in this class (wp_block patterns), where
	 *    sanitisation happens BEFORE `prepare_pattern_for_export()` injects
	 *    trusted `<?php esc_*_e(...);?>` markers.
	 *  2. `CBT_Theme_Templates::prepare_template_for_export()` (templates and
	 *    template parts), where sanitisation must happen at the very start —
	 *    BEFORE `escape_text_in_template()` injects the same trusted markers.
	 *
	 * In both cases the rule is: sanitise first, inject trusted PHP second,
	 * build the heredoc third. Calling this AFTER the trusted-PHP injection
	 * would strip the plugin's own localization helpers and break the
	 * "Make text translation-ready" feature.
	 *
	 * @param mixed $content User-supplied body content.
	 * @return mixed Same content with PHP open tags removed (when input is a non-empty string).
	 */
	public static function strip_php_tags( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		// Repeat until stable because removing one sequence can bring adjacent
		// fragments together and expose another sequence for the next pass.
		do {
			$previous_content = $content;

			// Strip ANY `<?` open tag. On hosts with `short_open_tag=1`, PHP parses
			// `<?` followed by `$`, `(`, `"`, `//`, `/*`, `;`, or `xml` as an open
			// tag. Block patterns are HTML/block markup, so there's no legitimate
			// `<?xml` content to preserve.
			$content = preg_replace( '/<\?/', '', $content );

			// Strip legacy `<script language="php">…</script>` blocks. Match the
			// entire block, including its inner content.
			$content = preg_replace( '#<script\s+language\s*=\s*["\']?php["\']?[^>]*>.*?</script>#is', '', $content );
		} while ( $content !== $previous_content );

		return $content;
	}

	/**
	 * Build a pattern .php file from a template stdClass.
	 *
	 * IMPORTANT: this function expects `$template->content` to be already
	 * sanitised by the caller. The pipeline entry point is
	 * `CBT_Theme_Templates::prepare_template_for_export`, which strips PHP
	 * tags from `$template->content` BEFORE the trusted-PHP injection done
	 * by `escape_text_in_template`. Calling `pattern_from_template` with
	 * un-sanitised user content would re-introduce the PHP injection bug.
	 */
	public static function pattern_from_template( $template, $new_slug = null ) {
		$theme_slug      = $new_slug ? $new_slug : wp_get_theme()->get( 'TextDomain' );
		$template_slug   = str_replace( '*/', '*&#47;', $template->slug );
		$pattern_slug    = $theme_slug . '/' . $template_slug;
		$pattern_content = <<<PHP
		<?php
		/**
		 * Title: {$template_slug}
		 * Slug: {$pattern_slug}
		 * Inserter: no
		 */
		?>
		{$template->content}
		PHP;

		return array(
			'slug'    => $pattern_slug,
			'content' => $pattern_content,
		);
	}

	public static function pattern_from_wp_block( $pattern_post ) {
		$pattern               = new stdClass();
		$pattern->id           = $pattern_post->ID;
		$pattern->title        = $pattern_post->post_title;
		$pattern->name         = sanitize_title_with_dashes( $pattern_post->post_title );
		$pattern->slug         = wp_get_theme()->get( 'TextDomain' ) . '/' . $pattern->name;
		$pattern_category_list = get_the_terms( $pattern->id, 'wp_pattern_category' );
		$pattern->categories   = ! empty( $pattern_category_list ) ? join( ', ', wp_list_pluck( $pattern_category_list, 'name' ) ) : '';
		$pattern_title         = str_replace( '*/', '*&#47;', $pattern->title );
		$pattern_categories    = str_replace( '*/', '*&#47;', $pattern->categories );
		$safe_body             = self::strip_php_tags( $pattern_post->post_content );
		$pattern->content      = <<<PHP
		<?php
		/**
		 * Title: {$pattern_title}
		 * Slug: {$pattern->slug}
		 * Categories: {$pattern_categories}
		 */
		?>
		{$safe_body}
		PHP;

		return $pattern;
	}

	public static function escape_alt_for_pattern( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}
		$html = new WP_HTML_Tag_Processor( $html );
		while ( $html->next_tag( 'img' ) ) {
			$alt_attribute = $html->get_attribute( 'alt' );
			if ( ! empty( $alt_attribute ) ) {
				$html->set_attribute( 'alt', self::escape_text_for_pattern( $alt_attribute ) );
			}
		}
		return $html->__toString();
	}

	public static function escape_text_for_pattern( $text ) {
		if ( $text && trim( $text ) !== '' ) {
			$escaped_text = addslashes( $text );
			return "<?php esc_attr_e('" . $escaped_text . "', '" . wp_get_theme()->get( 'Name' ) . "');?>";
		}
	}

	public static function create_pattern_link( $attributes ) {
		$block_attributes = array_filter( $attributes );
		$attributes_json  = json_encode( $block_attributes, JSON_UNESCAPED_SLASHES );
		return '<!-- wp:pattern ' . $attributes_json . ' /-->';
	}

	/**
	 * Replace references to a pattern stored in the database with references
	 * to the equivalent local pattern file.
	 *
	 * Templates and template parts that reference the pattern are written to
	 * the theme filesystem. Clearing their user customizations is left to
	 * clear_saved_pattern_template_customizations() so that it can happen once
	 * every pattern has been processed.
	 *
	 * @param object     $pattern                   The pattern being moved to the theme.
	 * @param array|null $options                   Export options.
	 * @param array      $templates_already_written Slugs already written during
	 *                                              this save, keyed by post type.
	 * @return array Slugs newly written to disk, keyed by post type.
	 */
	public static function replace_local_pattern_references( $pattern, $options = null, $templates_already_written = array() ) {
		// Find any references to pattern in templates.
		$templates_to_update = array(
			'wp_template'      => array(),
			'wp_template_part' => array(),
		);
		$args                = array(
			'post_type'      => array( 'wp_template', 'wp_template_part' ),
			'posts_per_page' => -1,
			's'              => 'wp:block {"ref":' . $pattern->id . '}',
		);
		$find_pattern_refs   = new WP_Query( $args );
		if ( $find_pattern_refs->have_posts() ) {
			foreach ( $find_pattern_refs->posts as $post ) {
				$post_type                           = 'wp_template_part' === $post->post_type ? 'wp_template_part' : 'wp_template';
				$templates_to_update[ $post_type ][] = $post->post_name;
			}
		}

		$already_written = array_merge(
			array(
				'wp_template'      => array(),
				'wp_template_part' => array(),
			),
			$templates_already_written
		);

		$base_dir         = get_stylesheet_directory();
		$template_folders = get_block_theme_folders();
		$folder_by_type   = array(
			'wp_template'      => $template_folders['wp_template'],
			'wp_template_part' => $template_folders['wp_template_part'],
		);

		// A template that references several patterns is written on the first
		// iteration. Do not write it again from the database on later
		// iterations: that would restore the numeric references that were
		// already replaced with local pattern references.
		foreach ( $templates_to_update as $post_type => $slugs ) {
			$slugs = array_values( array_unique( $slugs ) );
			$slugs = array_values( array_diff( $slugs, $already_written[ $post_type ] ) );

			// Only update templates that reference the pattern.
			// Passing an empty list to add_templates_to_local() would make it
			// fall back to exporting every template, so skip it when there is
			// nothing to update.
			if ( ! empty( $slugs ) ) {
				CBT_Theme_Templates::add_templates_to_local( 'all', null, null, $options, $slugs, $post_type );
			}

			// Record only the files that exist. A failed write must not lead to
			// the database customization being cleared.
			$written_slugs = array();
			foreach ( $slugs as $slug ) {
				if ( file_exists( $base_dir . DIRECTORY_SEPARATOR . $folder_by_type[ $post_type ] . DIRECTORY_SEPARATOR . $slug . '.html' ) ) {
					$written_slugs[] = $slug;
				}
			}
			$templates_to_update[ $post_type ] = $written_slugs;
		}

		// List all template and pattern files in the theme.
		$patterns       = glob( $base_dir . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . '*.php' );
		$templates      = glob( $base_dir . DIRECTORY_SEPARATOR . $template_folders['wp_template'] . DIRECTORY_SEPARATOR . '*.html' );
		$template_parts = glob( $base_dir . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] . DIRECTORY_SEPARATOR . '*.html' );

		// Replace references to the local patterns in the theme.
		// This runs on every iteration, even when no new file was written, so
		// files written for an earlier pattern also lose this pattern's numeric
		// reference.
		$files = array_merge(
			$patterns ? $patterns : array(),
			$templates ? $templates : array(),
			$template_parts ? $template_parts : array()
		);
		foreach ( $files as $file ) {
			$file_content = file_get_contents( $file );
			$file_content = str_replace( 'wp:block {"ref":' . $pattern->id . '}', 'wp:pattern {"slug":"' . $pattern->slug . '"}', $file_content );
			file_put_contents( $file, $file_content );
		}

		return $templates_to_update;
	}

	/**
	 * Clear the user customizations for the templates and template parts that
	 * were written to disk while saving patterns.
	 *
	 * Only items that were written to disk are cleared. Other user
	 * customizations are left untouched so they are not lost when saving
	 * patterns without saving templates.
	 *
	 * @param array $templates_written Slugs written to disk, keyed by post type.
	 */
	private static function clear_saved_pattern_template_customizations( $templates_written ) {
		$template_slugs      = isset( $templates_written['wp_template'] ) ? array_unique( $templates_written['wp_template'] ) : array();
		$template_part_slugs = isset( $templates_written['wp_template_part'] ) ? array_unique( $templates_written['wp_template_part'] ) : array();

		if ( ! empty( $template_slugs ) ) {
			CBT_Theme_Templates::clear_user_templates_customizations( $template_slugs );
		}

		if ( ! empty( $template_part_slugs ) ) {
			CBT_Theme_Templates::clear_user_template_parts_customizations( $template_part_slugs );
		}
	}

	public static function prepare_pattern_for_export( $pattern, $options = null ) {
		if ( ! $options ) {
			$options = array(
				'localizeText'   => false,
				'removeNavRefs'  => true,
				'localizeImages' => true,
			);
		}

		$pattern = CBT_Theme_Templates::eliminate_environment_specific_content( $pattern, $options );

		if ( array_key_exists( 'localizeText', $options ) && $options['localizeText'] ) {
			$pattern = CBT_Theme_Templates::escape_text_in_template( $pattern );
		}

		if ( array_key_exists( 'localizeImages', $options ) && $options['localizeImages'] ) {
			$pattern->media  = CBT_Theme_Media::get_media_absolute_urls_from_template( $pattern );
			$validated_media = ! empty( $pattern->media ) ? CBT_Theme_Media::add_media_to_local( $pattern->media ) : array();
			$pattern         = CBT_Theme_Media::make_template_images_local( $pattern, $validated_media );
		}

		return $pattern;
	}

	/**
	 * Copy the local patterns as well as any media to the theme filesystem.
	 *
	 * @param array|null $options Export options.
	 * @return WP_Error|void WP_Error when a pattern file with the same name already exists.
	 */
	public static function add_patterns_to_theme( $options = null ) {
		$base_dir     = get_stylesheet_directory();
		$patterns_dir = $base_dir . DIRECTORY_SEPARATOR . 'patterns';

		$pattern_query = new WP_Query(
			array(
				'post_type'      => 'wp_block',
				'posts_per_page' => -1,
			)
		);

		$templates_written = array(
			'wp_template'      => array(),
			'wp_template_part' => array(),
		);

		if ( $pattern_query->have_posts() ) {
			// If there is no patterns folder, create it.
			if ( ! is_dir( $patterns_dir ) ) {
				wp_mkdir_p( $patterns_dir );
			}

			foreach ( $pattern_query->posts as $pattern ) {
				$pattern        = self::pattern_from_wp_block( $pattern );
				$pattern        = self::prepare_pattern_for_export( $pattern, $options );
				$pattern_exists = false;

				// Check pattern name doesn't already exist before creating the file.
				$existing_patterns = glob( $patterns_dir . DIRECTORY_SEPARATOR . '*.php' );
				foreach ( $existing_patterns ? $existing_patterns : array() as $existing_pattern ) {
					if ( basename( $existing_pattern ) === $pattern->name . '.php' ) {
						$pattern_exists = true;
					}
				}

				if ( $pattern_exists ) {
					// Templates written for earlier patterns still have database
					// customizations that reference patterns already removed
					// from the database. Clear only those before bailing out.
					self::clear_saved_pattern_template_customizations( $templates_written );

					return new WP_Error(
						'pattern_already_exists',
						sprintf(
							/* Translators: Pattern name. */
							__(
								'A pattern with this name already exists: "%s".',
								'create-block-theme'
							),
							$pattern->name
						)
					);
				}

				// Create the pattern file.
				file_put_contents(
					$patterns_dir . DIRECTORY_SEPARATOR . $pattern->name . '.php',
					$pattern->content
				);

				$written_now = self::replace_local_pattern_references( $pattern, $options, $templates_written );

				$templates_written['wp_template']      = array_merge( $templates_written['wp_template'], $written_now['wp_template'] );
				$templates_written['wp_template_part'] = array_merge( $templates_written['wp_template_part'], $written_now['wp_template_part'] );

				// Remove it from the database to ensure that these patterns are loaded from the theme.
				wp_delete_post( $pattern->id, true );
			}
		}

		self::clear_saved_pattern_template_customizations( $templates_written );
	}
}
