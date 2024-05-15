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
		return "<?php echo __( '" . $string . "', '" . wp_get_theme()->get( 'TextDomain' ) . "' ); ?>";
	}

	/*
	 * Localize text in text blocks.
	 *
	 * @param array $blocks The blocks to localize.
	 * @return array The localized blocks.
	 */
	public static function escape_text_content_of_blocks( $blocks ) {
		foreach ( $blocks as &$block ) {

			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::escape_text_content_of_blocks( $block['innerBlocks'] );
			} else {
				/*
				 * Set the pattern based on the block type.
				 * The pattern is used to match the content that needs to be escaped.
				 * Pattern is a string for most blocks, but an array for blocks with multiple html elements that need to be escaped.
				 */
				$pattern = '';
				switch ( $block['blockName'] ) {
					case 'core/paragraph':
						$pattern = '/(<p[^>]*>)(.*?)(<\/p>)/';
						break;
					case 'core/heading':
						$pattern = '/(<h[^>]*>)(.*?)(<\/h[^>]*>)/';
						break;
					case 'core/list-item':
						$pattern = '/(<li[^>]*>)(.*?)(<\/li>)/';
						break;
					case 'core/verse':
						$pattern = '/(<pre[^>]*>)(.*?)(<\/pre>)/';
						break;
					case 'core/button':
						$pattern = '/(<a[^>]*>)(.*?)(<\/a>)/';
						break;
					case 'core/image':
					case 'core/cover':
					case 'core/media-text':
						$pattern = '/alt="(.*?)"/';
						break;
					case 'core/quote':
					case 'core/pullquote':
						$pattern = array(
							'/(<p[^>]*>)(.*?)(<\/p>)/',
							'/(<cite[^>]*>)(.*?)(<\/cite>)/',
						);
						break;
					case 'core/table':
						$pattern = array(
							'/(<td[^>]*>)(.*?)(<\/td>)/',
							'/(<th[^>]*>)(.*?)(<\/th>)/',
							'/(<figcaption[^>]*>)(.*?)(<\/figcaption>)/',
						);
						break;
				}

				// Replace the content with the new content based on the pattern.
				switch ( $block['blockName'] ) {

					case 'core/paragraph':
					case 'core/heading':
					case 'core/list-item':
					case 'core/verse':
					case 'core/button':
						$replace_content_callback = function ( $content ) use ( $pattern ) {
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
						break;

					case 'core/image':
					case 'core/cover':
					case 'core/media-text':
						$replace_content_callback = function ( $content ) use ( $pattern ) {
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
						if ( ! empty( $block['attrs']['alt'] ) ) {
							$block['attrs']['alt'] = self::escape_string( $block['attrs']['alt'] );
						}
						break;

					case 'core/quote':
					case 'core/pullquote':
						// Iterates the html patterns and replace the content with the escaped content.
						foreach ( $pattern as $element_pattern ) {
							$replace_content_callback = function ( $content ) use ( $element_pattern ) {
								return preg_replace_callback(
									$element_pattern,
									function( $matches ) {
										return $matches[1] . self::escape_string( $matches[2] ) . $matches[3];
									},
									$content
								);
							};

							if ( ! empty( $block['innerContent'] ) ) {
								$block['innerContent'] = $replace_content_callback( $block['innerContent'] );
							}
						}
						break;

					case 'core/table':
						// Iterates the html patterns and replace the content with the escaped content.
						foreach ( $pattern as $element_pattern ) {
							$replace_content_callback = function ( $content ) use ( $element_pattern ) {
								return preg_replace_callback(
									$element_pattern,
									function( $matches ) {
										return $matches[1] . self::escape_string( $matches[2] ) . $matches[3];
									},
									$content
								);
							};

							if ( ! empty( $block['innerContent'] ) ) {
								$block['innerContent'] = $replace_content_callback( $block['innerContent'] );
							}
						}
						break;
				}
			}
		}

		return $blocks;
	}
}
