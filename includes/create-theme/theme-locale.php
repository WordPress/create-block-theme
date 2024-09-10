<?php
/*
* Locale related functionality
*/
class CBT_Theme_Locale {

	/**
	 * Escape text for localization.
	 *
	 * @param string $string The string to escape.
	 * @return string The escaped string.
	 */
	private static function escape_text_content( $string ) {
		// Avoid escaping if the text is not a string.
		if ( ! is_string( $string ) ) {
			return $string;
		}

		// Check if string is empty.
		if ( '' === $string ) {
			return $string;
		}

		// Check if the text is already escaped.
		if ( str_starts_with( $string, '<?php' ) ) {
			return $string;
		}

		$string = addcslashes( $string, "'" );

		// Process the string to avoid escaping inner HTML markup.
		$p = new WP_HTML_Tag_Processor( $string );

		$text   = '';
		$tokens = array();
		while ( $p->next_token() ) {
			$token_type    = $p->get_token_type();
			$token_name    = strtolower( $p->get_token_name() );
			$is_tag_closer = $p->is_tag_closer();

			if ( '#tag' === $token_type ) {
				// Add a placeholder for the token.
				$text .= '%s';
				if ( $is_tag_closer ) {
					$tokens[] = "</{$token_name}>";
				} else {
					// Depending on the HTML tag, we may need to process attributes so they are correctly added to the placeholder.
					switch ( $token_name ) {
						// Handle links.
						case 'a':
							$href     = esc_url( $p->get_attribute( 'href' ) );
							$target   = empty( esc_attr( $p->get_attribute( 'target' ) ) ) ? '' : ' target="_blank"';
							$rel      = empty( esc_attr( $p->get_attribute( 'rel' ) ) ) ? '' : ' rel="nofollow noopener noreferrer"';
							$tokens[] = "<a href=\"{$href}\"{$target}{$rel}>";
							break;
						// Handle inline images.
						case 'img':
							$src   = esc_url( $p->get_attribute( 'src' ) );
							$style = esc_attr( $p->get_attribute( 'style' ) );
							$alt   = esc_attr( $p->get_attribute( 'alt' ) );

							CBT_Theme_Media::add_media_to_local( array( $src ) );
							$relative_src = CBT_Theme_Media::get_media_folder_path_from_url( $src ) . basename( $src );
							$tokens[]     = "<img style=\"{$style}\" src=\"' . esc_url( get_stylesheet_directory_uri() ) . '{$relative_src}\" alt=\"{$alt}\">";
							break;
						// Handle highlights.
						case 'mark':
							$style    = esc_attr( $p->get_attribute( 'style' ) );
							$class    = esc_attr( $p->get_attribute( 'class' ) );
							$tokens[] = "<mark style=\"{$style}\" class=\"{$class}\">";
							break;
						// Otherwise, just add the tag opener.
						default:
							$tokens[] = "<{$token_name}>";
							break;
					}
				}
			} else {
				// If it's not a tag, just add the text content.
				$text .= esc_html( $p->get_modifiable_text() );
			}
		}
		// If tokens is not empty, format the string using sprintf.
		if ( ! empty( $tokens ) ) {
			// Format the string, replacing the placeholders with the formatted tokens.
			return "<?php /* Translators: %s are html tags */ echo sprintf( esc_html__( '$text', '" . wp_get_theme()->get( 'TextDomain' ) . "' ), " . implode(
				', ',
				array_map(
					function( $token ) {
						return "'$token'";
					},
					$tokens
				)
			) . ' ); ?>';
		}

		return "<?php esc_html_e('" . $string . "', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";

	}

	/**
	 * Escape an html element attribute for localization.
	 *
	 * @param string $string The string to escape.
	 * @return string The escaped string.
	 */
	private static function escape_attribute( $string ) {
		// Avoid escaping if the text is not a string.
		if ( ! is_string( $string ) ) {
			return $string;
		}

		// Check if string is empty.
		if ( '' === $string ) {
			return $string;
		}

		// Check if the text is already escaped.
		if ( str_starts_with( $string, '<?php' ) ) {
			return $string;
		}

		$string = addcslashes( $string, "'" );
		return "<?php esc_attr_e('" . $string . "', '" . wp_get_theme()->get( 'TextDomain' ) . "');?>";
	}

	/**
	 * Get a replacement pattern for escaping the text from the html content of a block.
	 *
	 * @param string $block_name The block name.
	 * @return array|null The regex patterns to match the content that needs to be escaped.
	 *      Returns null if the block is not supported.
	 *      Returns an array of regex patterns if the block has html elements that need to be escaped.
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

			// Builds the replacement callback function based on the block type.
			switch ( $block['blockName'] ) {
				case 'core/paragraph':
				case 'core/heading':
				case 'core/list-item':
				case 'core/verse':
				case 'core/button':
				case 'core/quote':
				case 'core/pullquote':
				case 'core/table':
					$replace_content_callback = function ( $content, $pattern ) {
						if ( empty( $content ) ) {
							return;
						}
						return preg_replace_callback(
							$pattern,
							function( $matches ) {
								return $matches[1] . self::escape_text_content( $matches[2] ) . $matches[3];
							},
							$content
						);
					};
					break;
				case 'core/image':
				case 'core/cover':
				case 'core/media-text':
					$replace_content_callback = function ( $content, $pattern ) {
						if ( empty( $content ) ) {
							return;
						}
						return preg_replace_callback(
							$pattern,
							function( $matches ) {
								return 'alt="' . self::escape_attribute( $matches[1] ) . '"';
							},
							$content
						);
					};
					break;
				default:
					$replace_content_callback = null;
					break;
			}

			// Apply the replacement patterns to the block content.
			foreach ( $patterns as $pattern ) {
				if (
					! empty( $block['innerContent'] ) &&
					is_callable( $replace_content_callback )
				) {
					$block['innerContent'] = is_array( $block['innerContent'] )
					? array_map(
						function( $content ) use ( $replace_content_callback, $pattern ) {
							return $replace_content_callback( $content, $pattern );
						},
						$block['innerContent']
					)
					: $replace_content_callback( $block['innerContent'], $pattern );
				}
			}
		}
		return $blocks;
	}
}
