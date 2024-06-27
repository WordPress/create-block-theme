<?php

class CBT_Theme_Patterns {
	public static function pattern_from_template( $template, $new_slug = null ) {
		$theme_slug      = $new_slug ? $new_slug : wp_get_theme()->get( 'TextDomain' );
		$pattern_slug    = $theme_slug . '/' . $template->slug;
		$pattern_content = (
		'<?php
/**
 * Title: ' . $template->slug . '
 * Slug: ' . $pattern_slug . '
 * Categories: hidden
 * Inserter: no
 */
?>
' . $template->content
		);
		return array(
			'slug'    => $pattern_slug,
			'content' => $pattern_content,
		);
	}

	public static function pattern_from_wp_block( $pattern_post ) {
		$pattern          = new stdClass();
		$pattern->name    = sanitize_title_with_dashes( $pattern_post->post_title );
		$theme_slug       = wp_get_theme()->get( 'TextDomain' );
		$pattern->slug    = $theme_slug . '/' . $pattern->name;
		$pattern->content = (
		'<?php
/**
 * Title: ' . $pattern_post->post_title . '
 * Slug: ' . $pattern->slug . '
 * Categories: hidden
 * Inserter: no
 */
?>
' . $pattern_post->post_content
		);

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
			return "<?php echo esc_attr_e( '" . $escaped_text . "', '" . wp_get_theme()->get( 'Name' ) . "' ); ?>";
		}
	}

	public static function create_pattern_link( $attributes ) {
		$block_attributes = array_filter( $attributes );
		$attributes_json  = json_encode( $block_attributes, JSON_UNESCAPED_SLASHES );
		return '<!-- wp:pattern ' . $attributes_json . ' /-->';
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
				$pattern      = self::pattern_from_wp_block( $pattern );
				$pattern      = self::prepare_pattern_for_export( $pattern, $options );
				$pattern_file = $patterns_dir . 'pattern-' . $pattern->name . '.php';
				file_put_contents(
					$patterns_dir . DIRECTORY_SEPARATOR . 'pattern-' . $pattern->name . '.php',
					$pattern->content
				);
			}
		}

		// TODO:
		// Replace any references to the custom patterns with the new theme patterns.
	}
}
