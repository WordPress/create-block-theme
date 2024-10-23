<?php

class CBT_Theme_Patterns {
	public static function pattern_from_template( $template, $new_slug = null ) {
		$theme_slug      = $new_slug ? $new_slug : wp_get_theme()->get( 'TextDomain' );
		$pattern_slug    = $theme_slug . '/' . $template->slug;
		$pattern_content = <<<PHP
		<?php
		/**
		 * Title: {$template->slug}
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
		$pattern->sync_status  = get_post_meta( $pattern->id, 'wp_pattern_sync_status', true );
		$pattern->content      = <<<PHP
		<?php
		/**
		 * Title: {$pattern->title}
		 * Slug: {$pattern->slug}
		 * Categories: {$pattern->categories}
		 */
		?>
		{$pattern_post->post_content}
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

	public static function replace_local_pattern_references( $pattern ) {
		// Find any references to pattern in templates
		$templates_to_update = array();
		$args                = array(
			'post_type'      => array( 'wp_template', 'wp_template_part' ),
			'posts_per_page' => -1,
			's'              => 'wp:block {"ref":' . $pattern->id . '}',
		);
		$find_pattern_refs   = new WP_Query( $args );
		if ( $find_pattern_refs->have_posts() ) {
			foreach ( $find_pattern_refs->posts as $post ) {
				$slug = $post->post_name;
				array_push( $templates_to_update, $slug );
			}
		}
		$templates_to_update = array_unique( $templates_to_update );

		// Only update templates that reference the pattern
		CBT_Theme_Templates::add_templates_to_local( 'all', null, null, $options, $templates_to_update );

		// List all template and pattern files in the theme
		$base_dir       = get_stylesheet_directory();
		$patterns       = glob( $base_dir . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . '*.php' );
		$templates      = glob( $base_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '*.html' );
		$template_parts = glob( $base_dir . DIRECTORY_SEPARATOR . 'template-parts' . DIRECTORY_SEPARATOR . '*.html' );

		// Replace references to the local patterns in the theme
		foreach ( array_merge( $patterns, $templates, $template_parts ) as $file ) {
			$file_content = file_get_contents( $file );
			$file_content = str_replace( 'wp:block {"ref":' . $pattern->id . '}', 'wp:pattern {"slug":"' . $pattern->slug . '"}', $file_content );
			file_put_contents( $file, $file_content );
		}

		CBT_Theme_Templates::clear_user_templates_customizations();
		CBT_Theme_Templates::clear_user_template_parts_customizations();
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
			$pattern = CBT_Theme_Media::make_template_images_local( $pattern );

			// Write the media assets if there are any
			if ( $pattern->media ) {
				CBT_Theme_Media::add_media_to_local( $pattern->media );
			}
		}

		return $pattern;
	}

	/**
	 * Copy the local patterns as well as any media to the theme filesystem.
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

		if ( $pattern_query->have_posts() ) {
			// If there is no patterns folder, create it.
			if ( ! is_dir( $patterns_dir ) ) {
				wp_mkdir_p( $patterns_dir );
			}

			foreach ( $pattern_query->posts as $pattern ) {
				$pattern        = self::pattern_from_wp_block( $pattern );
				$pattern        = self::prepare_pattern_for_export( $pattern, $options );
				$pattern_exists = false;

				// Check pattern is synced before adding to theme.
				if ( 'unsynced' !== $pattern->sync_status ) {
					// Check pattern name doesn't already exist before creating the file.
					$existing_patterns = glob( $patterns_dir . DIRECTORY_SEPARATOR . '*.php' );
					foreach ( $existing_patterns as $existing_pattern ) {
						if ( strpos( $existing_pattern, $pattern->name . '.php' ) !== false ) {
							$pattern_exists = true;
						}
					}

					if ( $pattern_exists ) {
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
					$pattern_file = $patterns_dir . $pattern->name . '.php';
					file_put_contents(
						$patterns_dir . DIRECTORY_SEPARATOR . $pattern->name . '.php',
						$pattern->content
					);

					self::replace_local_pattern_references( $pattern );

					// Remove it from the database to ensure that these patterns are loaded from the theme.
					wp_delete_post( $pattern->id, true );
				}
			}
		}
	}
}
