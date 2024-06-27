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

	public static function pattern_from_wp_block( $post ) {
		$pattern_name    = sanitize_title_with_dashes( $post->post_title );
		$theme_slug      = $new_slug ? $new_slug : wp_get_theme()->get( 'TextDomain' );
		$pattern_slug    = $theme_slug . '/' . $pattern_name;
		$pattern_content = (
		'<?php
/**
 * Title: ' . $post->post_title . '
 * Slug: ' . $pattern_slug . '
 * Categories: hidden
 * Inserter: no
 */
?>
' . $post->post_content
		);
		return array(
			'name'    => $pattern_name,
			'slug'    => $pattern_slug,
			'content' => $pattern_content,
		);
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

	/**
	 * Copy the local patterns as well as any media to the theme filesystem.
	 */
	public static function add_patterns_to_theme() {
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
				$pattern_file = $patterns_dir . 'pattern-' . $pattern['name'] . '.php';
				file_put_contents(
					$patterns_dir . DIRECTORY_SEPARATOR . 'pattern-' . $pattern['name'] . '.php',
					$pattern['content']
				);
			}
		}

		// TODO:
		// Copy media to the theme filesystem and replace media URLs.
		// Replace any references to the custom patterns with the new theme patterns.
	}
}
