<?php
/*
* Locale related functionality
*/
class CBT_Theme_Locale {

	public static function escape_string( $string ) {
		// Avoid escaping if the text is not a string.
		if ( ! is_string( $string ) ) {
			return $string;
		}

		// Check if the text is already escaped.
		if ( str_starts_with( $string, '<?php echo' ) ) {
			return $string;
		}

		$string = addcslashes( $string, "'" );
		return "<?php echo __('" . $string . "', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";
	}

	/**
	 * Get a replacement pattern for escaping the text from the html content of a block.
	 *
	 * @param string $block_name The block name.
	 * @return string|array|null The regex pattern to match the content that needs to be escaped.
	 *      Returns null if the block is not supported.
	 *      Returns an array if the block has multiple html elements that need to be escaped.
	 */
	private static function get_text_replacement_patterns_for_html( $block_name ) {
		switch ( $block_name ) {
			case 'core/paragraph':
				return array( '/(<p[^>]*>)(.*?)(<\/p>)/' );
			case 'core/heading':
				return array( '/(<h[^>]*>)(.*?)(<\/h[^>]*>)/' );
			case 'core/list-item':
				return array( '/(<li[^>]*>)(.*?)(<\/li>)/' );
			case 'core/verse':
				return array( '/(<pre[^>]*>)(.*?)(<\/pre>)/' );
			case 'core/button':
				return array( '/(<a[^>]*>)(.*?)(<\/a>)/' );
			case 'core/image':
			case 'core/cover':
			case 'core/media-text':
				return array( '/alt="(.*?)"/' );
			case 'core/quote':
			case 'core/pullquote':
				return array(
					'/(<p[^>]*>)(.*?)(<\/p>)/',
					'/(<cite[^>]*>)(.*?)(<\/cite>)/',
				);
			case 'core/table':
				return array(
					'/(<td[^>]*>)(.*?)(<\/td>)/',
					'/(<th[^>]*>)(.*?)(<\/th>)/',
					'/(<figcaption[^>]*>)(.*?)(<\/figcaption>)/',
				);
			default:
				return null;
		}
	}

	/*
	 * Localize text in text blocks.
	 *
	 * @param array $blocks The blocks to localize.
	 * @return array The localized blocks.
	 */
	public static function escape_text_content_of_blocks( $blocks ) {
		foreach ( $blocks as &$block ) {

			// Recursively escape the inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::escape_text_content_of_blocks( $block['innerBlocks'] );
			}

			/*
			 * Set the pattern based on the block type.
			 * The pattern is used to match the content that needs to be escaped.
			 * Patterns are defined in the get_text_replacement_patterns_for_html method.
			 */
			$patterns = self::get_text_replacement_patterns_for_html( $block['blockName'] );

			// If the block does not have any patterns leave the block as is and continue to the next block.
			if ( ! $patterns ) {
				continue;
			}

			// Replace the content with the new content based on the pattern.
			switch ( $block['blockName'] ) {
				case 'core/paragraph':
				case 'core/heading':
				case 'core/list-item':
				case 'core/verse':
				case 'core/button':
				case 'core/quote':
				case 'core/pullquote':
				case 'core/table':
					// Iterates the html patterns and replace the content with the escaped content.
					foreach ( $patterns as $pattern ) {
						$replace_content_callback = function ( $content ) use ( $pattern ) {
							if ( empty( $content ) ) {
								return;
							}
							return preg_replace_callback(
								$pattern,
								function( $matches ) {
									return $matches[1] . self::escape_string( $matches[2] ) . $matches[3];
								},
								$content
							);
						};

						if ( ! empty( $block['innerContent'] ) ) {
							$block['innerContent'] = is_array( $block['innerContent'] )
							? array_map( $replace_content_callback, $block['innerContent'] )
							: $replace_content_callback( $block['innerContent'] );
						}
					}
					break;
				case 'core/image':
				case 'core/cover':
				case 'core/media-text':
					// Iterates the html patterns and replace the content with the escaped content.
					foreach ( $patterns as $pattern ) {
						$replace_content_callback = function ( $content ) use ( $pattern ) {
							if ( empty( $content ) ) {
								return;
							}
							return preg_replace_callback(
								$pattern,
								function( $matches ) {
									return 'alt="' . self::escape_string( $matches[1] ) . '"';
								},
								$content
							);
						};
						if ( ! empty( $block['innerContent'] ) ) {
							$block['innerContent'] = is_array( $block['innerContent'] )
							? array_map( $replace_content_callback, $block['innerContent'] )
							: $replace_content_callback( $block['innerContent'] );
						}
					}
					break;
			}
		}
		return $blocks;
	}
}
