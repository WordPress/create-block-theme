<?php
/*
* Locale related functionality
*/

require_once __DIR__ . '/theme-token-processor.php';

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

		$p = new CBT_Token_Processor( $string );
		$p->process_tokens();
		$text             = $p->get_text();
		$tokens           = $p->get_tokens();
		$translators_note = $p->get_translators_note();

		if ( ! empty( $tokens ) ) {
			$php_tag  = '<?php ';
			$php_tag .= $translators_note . "\n";
			$php_tag .= "echo sprintf( esc_html__( '$text', '" . wp_get_theme()->get( 'TextDomain' ) . "' ), " . implode(
				', ',
				array_map(
					function( $token ) {
						return "'$token'";
					},
					$tokens
				)
			) . ' ); ?>';
			return $php_tag;
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
			case 'core/video':
				return array( '/(<figcaption[^>]*>)(.*?)(<\/figcaption>)/' );
			case 'core/image':
				return array(
					'/(<figcaption[^>]*>)(.*?)(<\/figcaption>)/',
					'/(alt=")(.*?)(")/',
				);
			case 'core/cover':
			case 'core/media-text':
				return array( '/(alt=")(.*?)(")/' );
			case 'core/details':
				return array( '/(<summary[^>]*>)(.*?)(<\/summary>)/' );
			default:
				return null;
		}
	}

	/**
	 * Get the list of block attributes that should be localized.
	 *
	 * @param string $block_name The block name.
	 * @return array|null The array of attribute names to localize.
	 *      Returns null if the block does not have localizable attributes.
	 */
	private static function get_localizable_block_attributes( $block_name ) {
		switch ( $block_name ) {
			case 'core/search':
				return array( 'label', 'placeholder', 'buttonText' );
			case 'core/query-pagination-previous':
			case 'core/query-pagination-next':
			case 'core/comments-pagination-previous':
			case 'core/comments-pagination-next':
			case 'core/post-navigation-link':
				return array( 'label' );
			case 'core/post-excerpt':
				return array( 'moreText' );
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
				case 'core/video':
				case 'core/image':
				case 'core/cover':
				case 'core/media-text':
				case 'core/details':
					$replace_content_callback = function ( $content, $pattern ) {
						if ( empty( $content ) ) {
							return;
						}
						return preg_replace_callback(
							$pattern,
							function( $matches ) {
								// If the pattern is for attribute like alt="".
								if ( str_ends_with( $matches[1], '="' ) ) {
									return $matches[1] . self::escape_attribute( $matches[2] ) . $matches[3];
								}
								return $matches[1] . self::escape_text_content( $matches[2] ) . $matches[3];
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

	/**
	 * Escape block attribute strings for localization in serialized block markup.
	 *
	 * This method processes the serialized block markup string to add localization
	 * to attribute values. It must be called AFTER serialize_blocks() because
	 * PHP tags in attributes would be JSON-encoded during serialization.
	 *
	 * @param string $content The serialized block markup string.
	 * @return string The content with localized attribute values.
	 */
	public static function escape_block_attribute_strings( $content ) {
		// Pattern to match block comments with JSON attributes.
		// This captures: <!-- wp:block/name {...attributes...} --> or <!-- wp:block/name {...attributes...} /-->
		// Using .*? to lazily match everything until we hit the closing -->
		$pattern = '/<!--\s+wp:([a-z0-9\/-]+)\s+(\{.*?\})\s*(\/)?-->/s';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) {
				$block_name  = $matches[1];
				$attrs_json  = $matches[2];
				$self_closer = isset( $matches[3] ) ? $matches[3] : '';

				// Get localizable attributes for this block.
				$localizable_attrs = self::get_localizable_block_attributes( 'core/' . $block_name );

				// If no localizable attributes for this block, return unchanged.
				if ( ! $localizable_attrs ) {
					return $matches[0];
				}

				// Decode the JSON attributes.
				$attrs = json_decode( $attrs_json, true );

				// If JSON decode failed, return unchanged.
				if ( ! is_array( $attrs ) ) {
					return $matches[0];
				}

				// Process each localizable attribute.
				$modified = false;
				foreach ( $localizable_attrs as $attr_name ) {
					if ( isset( $attrs[ $attr_name ] ) && is_string( $attrs[ $attr_name ] ) ) {
						// Skip if already escaped.
						if ( str_starts_with( $attrs[ $attr_name ], '<?php' ) ) {
							continue;
						}

						// Escape the attribute value.
						$attrs[ $attr_name ] = self::escape_attribute( $attrs[ $attr_name ] );
						$modified            = true;
					}
				}

				// If we modified any attributes, re-encode to JSON.
				if ( $modified ) {
					$new_attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
					return '<!-- wp:' . $block_name . ' ' . $new_attrs_json . ' ' . $self_closer . '-->';
				}

				// Return original if nothing was modified.
				return $matches[0];
			},
			$content
		);
	}
}
