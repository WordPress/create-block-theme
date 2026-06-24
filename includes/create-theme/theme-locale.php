<?php
/*
* Locale related functionality
*/

require_once __DIR__ . '/theme-token-processor.php';

class CBT_Theme_Locale {

	/**
	 * Escape a string that will be embedded in generated PHP single-quoted strings.
	 *
	 * @param string $string The string to escape.
	 * @return string The escaped string.
	 */
	private static function escape_php_single_quoted_string( $string ) {
		return addcslashes( (string) $string, "\\'" );
	}

	/**
	 * Escape a block attribute value for localization.
	 *
	 * @param string $string The string to escape.
	 * @return string The escaped string.
	 */
	private static function escape_block_attribute( $string ) {
		$tokenized   = self::tokenize_block_attribute_for_php_string( $string );
		$text_domain = self::escape_php_single_quoted_string( wp_get_theme()->get( 'TextDomain' ) );

		if ( empty( $tokenized['tokens'] ) ) {
			return "<?php esc_attr_e('" . $tokenized['text'] . "', '$text_domain');?>";
		}

		$translation_call  = "__( '" . $tokenized['text'] . "', '$text_domain' )";
		$token_expressions = implode( ', ', wp_list_pluck( $tokenized['tokens'], 'expression' ) );

		$php_tag  = '<?php ';
		$php_tag .= $tokenized['translators_note'] . ' ';
		$php_tag .= 'echo esc_attr( sprintf( ' . $translation_call . ', ' . $token_expressions . ' ) ); ?>';
		return $php_tag;
	}

	/**
	 * Tokenize characters that would be unsafe inside localized block attribute PHP strings.
	 *
	 * @param string $string The string to tokenize.
	 * @return array Tokenized text, token expressions, and a translators note.
	 */
	private static function tokenize_block_attribute_for_php_string( $string ) {
		$tokens        = array();
		$text          = '';
		$special_chars = array(
			'\\' => array(
				'expression'  => 'chr(92)',
				'description' => 'a backslash character',
			),
			"'"  => array(
				'expression'  => 'chr(39)',
				'description' => 'an apostrophe character',
			),
			'"'  => array(
				'expression'  => 'chr(34)',
				'description' => 'a double quote character',
			),
			"\n" => array(
				'expression'  => 'chr(10)',
				'description' => 'a newline character',
			),
			"\r" => array(
				'expression'  => 'chr(13)',
				'description' => 'a carriage return character',
			),
			"\t" => array(
				'expression'  => 'chr(9)',
				'description' => 'a tab character',
			),
		);

		$string     = (string) $string;
		$length     = strlen( $string );
		$has_tokens = false;

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $string[ $i ];
			if ( isset( $special_chars[ $char ] ) || ord( $char ) < 32 ) {
				$has_tokens = true;
				break;
			}
		}

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $string[ $i ];
			$ord  = ord( $char );

			if ( isset( $special_chars[ $char ] ) || $ord < 32 ) {
				$token_data = isset( $special_chars[ $char ] )
					? $special_chars[ $char ]
					: array(
						'expression'  => 'chr(' . $ord . ')',
						'description' => 'character code ' . $ord,
					);

				$tokens[] = $token_data;
				$text    .= '%' . count( $tokens ) . '$s';
				continue;
			}

			$text .= $has_tokens && '%' === $char ? '%%' : $char;
		}

		$text = self::escape_php_single_quoted_string( $text );

		if ( empty( $tokens ) ) {
			return array(
				'text'             => $text,
				'tokens'           => $tokens,
				'translators_note' => '',
			);
		}

		$descriptions = array();
		foreach ( $tokens as $index => $token ) {
			$descriptions[] = ( $index + 1 ) . '. is ' . $token['description'];
		}

		return array(
			'text'             => $text,
			'tokens'           => $tokens,
			'translators_note' => '/* Translators: ' . implode( ', ', $descriptions ) . '. */',
		);
	}

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

		$string = self::escape_php_single_quoted_string( $string );

		$p = new CBT_Token_Processor( $string );
		$p->process_tokens();
		$text             = $p->get_text();
		$tokens           = $p->get_tokens();
		$translators_note = $p->get_translators_note();
		$text_domain      = self::escape_php_single_quoted_string( wp_get_theme()->get( 'TextDomain' ) );

		if ( ! empty( $tokens ) ) {
			$php_tag  = '<?php ';
			$php_tag .= $translators_note . "\n";
			$php_tag .= "echo sprintf( esc_html__( '$text', '$text_domain' ), " . implode(
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

		return "<?php esc_html_e('" . $string . "', '$text_domain');?>";
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

		$string      = self::escape_php_single_quoted_string( $string );
		$text_domain = self::escape_php_single_quoted_string( wp_get_theme()->get( 'TextDomain' ) );
		return "<?php esc_attr_e('" . $string . "', '$text_domain');?>";
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
			case 'core/file':
				return array(
					// File-name link: <a> without the boolean `download` attribute.
					'/(<a(?:(?!\sdownload[\s>])[^>])*?>)(.*?)(<\/a>)/',
					// Download button: <a ... download ...>.
					'/(<a[^>]*\sdownload[^>]*?>)(.*?)(<\/a>)/',
				);
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
			case 'core/navigation-link':
			case 'core/navigation-submenu':
			case 'core/home-link':
			case 'core/social-link':
			case 'core/categories':
				return array( 'label' );
			case 'core/post-excerpt':
				return array( 'moreText' );
			case 'core/read-more':
				return array( 'content' );
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
				case 'core/file':
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
				$localized_attrs = array();
				$modified        = false;
				foreach ( $localizable_attrs as $attr_name ) {
					if ( isset( $attrs[ $attr_name ] ) && is_string( $attrs[ $attr_name ] ) ) {
						// Skip if already escaped.
						if ( str_starts_with( $attrs[ $attr_name ], '<?php' ) ) {
							continue;
						}

						// Escape the attribute value.
						$localized_attrs[ $attr_name ] = self::escape_block_attribute( $attrs[ $attr_name ] );
						$modified                      = true;
					}
				}

				// If we modified any attributes, re-encode to JSON.
				if ( $modified ) {
					$attr_fragments = array();
					foreach ( $attrs as $attr_name => $attr_value ) {
						$encoded_attr_name = wp_json_encode( (string) $attr_name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

						if ( array_key_exists( $attr_name, $localized_attrs ) ) {
							$attr_fragments[] = $encoded_attr_name . ':' . wp_json_encode( $localized_attrs[ $attr_name ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
							continue;
						}

						$attr_fragments[] = $encoded_attr_name . ':' . wp_json_encode( $attr_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
					}

					$new_attrs_json = '{' . implode( ',', $attr_fragments ) . '}';
					return '<!-- wp:' . $block_name . ' ' . $new_attrs_json . ' ' . $self_closer . '-->';
				}

				// Return original if nothing was modified.
				return $matches[0];
			},
			$content
		);
	}
}
