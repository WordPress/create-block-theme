<?php

class Theme_Patterns {
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

	public static function escape_alt_for_pattern( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		// Use WP_HTML_Tag_Processor if available
		// see: https://github.com/WordPress/gutenberg/pull/42485
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$html = new WP_HTML_Tag_Processor( $html );
			while ( $html->next_tag( 'img' ) ) {
				$alt_attribute = $html->get_attribute( 'alt' );
				if ( ! empty( $alt_attribute ) ) {
					$html->set_attribute( 'alt', self::escape_text_for_pattern( $alt_attribute ) );
				}
			}
			return $html->__toString();
		}

		// Fallback to regex
		// TODO: When WP_HTML_Tag_Processor is availabe in core (6.2) we can remove this implementation entirely.
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			preg_match( '@alt="([^"]+)"@', $html, $match );
			if ( isset( $match[0] ) ) {
				$alt_attribute = $match[0];
				$alt_value     = $match[1];
				$html          = str_replace(
					$alt_attribute,
					'alt="' . self::escape_text_for_pattern( $alt_value ) . '"',
					$html
				);
			}
			return $html;
		}
	}

	static function escape_text_for_pattern( $text ) {
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
}
