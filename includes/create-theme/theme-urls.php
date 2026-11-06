<?php

require_once __DIR__ . '/theme-utils.php';

/**
 * Makes URLs in exported templates and patterns portable.
 *
 * Content saved in the Site Editor contains absolute URLs of the site it was
 * edited on (e.g. https://mysite.local/about/). Those URLs break once the theme
 * is installed on another domain, so on export they are replaced with PHP that
 * builds the URL at runtime:
 *
 * - URLs of files inside the theme directory become
 *   `get_template_directory_uri()` (or `get_stylesheet_directory_uri()` for a
 *   child theme's own files) expressions, as CBT_Theme_Media does for images.
 * - Any other URL of the current site becomes a `home_url()` or `site_url()`
 *   expression, matching the base the URL was found under.
 *
 * The replacement runs over the whole markup, so it covers HTML attributes and
 * block comment JSON alike. It never targets a specific block or attribute.
 */
class CBT_Theme_URLs {

	/**
	 * Regex fragment matching the rest of a URL after a known base.
	 *
	 * In block comment JSON, WordPress writes `"`, `<` and `>` as `\u0022`,
	 * `\u003c` and `\u003e`; other escapes such as `\/` and `\u0026` (`&`)
	 * are part of the URL.
	 *
	 * @var string
	 */
	private const URL_REMAINDER_PATTERN =
		'(?:'
		// Any character except whitespace, quotes, angle brackets, a backslash,
		// or `)` (the end of a CSS `url()`).
		. '[^\s"\'<>)\\\\]'
		// Or a backslash, unless it starts an escaped `"`, `'`, `<` or `>`
		// (\u0022, \u0027, \u003c, \u003e), which ends the URL.
		. '|\\\\(?!u00(?:22|27|3[ceCE]))'
		. ')*';

	/**
	 * Replace site URLs in a template or pattern with portable PHP expressions.
	 *
	 * Named after CBT_Theme_Media::make_template_images_local(), which makes the
	 * template's images part of the theme in the same export step.
	 *
	 * Must run after text localization (escape_text_in_template()): that step
	 * moves text, links included, into PHP strings, and would break any PHP
	 * added here before it.
	 *
	 * @param object $template Template or pattern object with a `content` property.
	 * @return object The same object, with its content updated.
	 */
	public static function make_template_urls_local( $template ) {
		if ( ! isset( $template->content ) || ! is_string( $template->content ) ) {
			return $template;
		}

		$bases    = self::get_url_bases();
		$output   = '';
		$previous = array();

		foreach ( token_get_all( $template->content ) as $token ) {
			$type = is_array( $token ) ? $token[0] : null;
			$text = is_array( $token ) ? $token[1] : $token;

			if ( T_INLINE_HTML === $type ) {
				$text     = self::replace_urls_in_html( $text, $bases );
				$previous = array();
			} elseif ( T_CONSTANT_ENCAPSED_STRING === $type && array( 'esc_url', '(' ) === $previous ) {
				// Text localization writes link targets as esc_url( '...' ).
				// Other PHP strings are left alone: most are translatable
				// text, which must stay a plain string literal.
				$text = self::replace_url_literal( $text, $bases );
			}

			if ( T_INLINE_HTML !== $type && ! in_array( $type, array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				$previous = array( end( $previous ), strtolower( $text ) );
			}

			$output .= $text;
		}

		$template->content = $output;

		return $template;
	}

	/**
	 * Get the URLs a portable URL can be built from, each with the function
	 * that returns it at runtime.
	 *
	 * When two functions return the same URL, the first one listed is kept:
	 * get_template_directory_uri() for a theme without a parent, matching
	 * CBT_Theme_Media. The list is sorted longest first, so the most specific
	 * base wins: a theme file URL starts with the site URL too.
	 *
	 * @return array[] List of bases {
	 *     @type string $url      Base URL, without trailing slash.
	 *     @type string $function Name of the PHP function that returns $url.
	 * }
	 */
	private static function get_url_bases() {
		$bases = array();

		foreach ( array( 'get_template_directory_uri', 'get_stylesheet_directory_uri', 'home_url', 'site_url' ) as $url_function ) {
			$url = untrailingslashit( call_user_func( $url_function ) );
			if ( '' !== $url && ! isset( $bases[ $url ] ) ) {
				$bases[ $url ] = array(
					'url'      => $url,
					'function' => $url_function,
				);
			}
		}

		usort(
			$bases,
			function ( $a, $b ) {
				return strlen( $b['url'] ) - strlen( $a['url'] );
			}
		);

		return $bases;
	}

	/**
	 * Replace every URL under one of the bases in inline HTML (markup and
	 * block comment JSON) with an echoed PHP expression.
	 *
	 * @param string  $html  Inline HTML.
	 * @param array[] $bases Result of get_url_bases().
	 * @return string
	 */
	private static function replace_urls_in_html( $html, $bases ) {
		foreach ( $bases as $base ) {
			// Block comment JSON stores the same URL with escaped slashes.
			$json_escaped_url = str_replace( '/', '\\/', $base['url'] );

			foreach ( array_unique( array( $base['url'], $json_escaped_url ) ) as $prefix ) {
				$html = preg_replace_callback(
					'~' . preg_quote( $prefix, '~' ) . '(' . self::URL_REMAINDER_PATTERN . ')~',
					function ( $matches ) use ( $base ) {
						$rest = $matches[1];
						$path = self::decode_json_escapes( $rest );
						if ( ! self::is_url_boundary( $path ) ) {
							return $matches[0];
						}

						// Theme files keep their path as written, in the same
						// output format as CBT_Theme_Media.
						if ( in_array( $base['function'], array( 'get_template_directory_uri', 'get_stylesheet_directory_uri' ), true ) ) {
							return '<?php echo esc_url( ' . $base['function'] . '() ); ?>' . $rest;
						}

						return '<?php echo esc_url( ' . self::build_url_call( $base['function'], $path ) . ' ); ?>';
					},
					$html
				);
			}
		}

		return $html;
	}

	/**
	 * Replace a PHP string literal holding exactly one URL under a base with
	 * the PHP expression that builds it, e.g. `'https://example.com/faq'`
	 * becomes `home_url( '/faq' )`.
	 *
	 * @param string  $literal String literal token, including its quotes.
	 * @param array[] $bases   Result of get_url_bases().
	 * @return string The PHP expression, or $literal unchanged.
	 */
	private static function replace_url_literal( $literal, $bases ) {
		$url = substr( $literal, 1, -1 );

		// A value with whitespace, quotes, a backslash or `$` is not a plain
		// URL, or would need unescaping first.
		if ( '' === $url || false !== strpbrk( $url, " \t\r\n\"'\\$" ) ) {
			return $literal;
		}

		foreach ( $bases as $base ) {
			if ( ! str_starts_with( $url, $base['url'] ) ) {
				continue;
			}

			$path = substr( $url, strlen( $base['url'] ) );
			if ( ! self::is_url_boundary( $path ) ) {
				continue;
			}

			return self::build_url_call( $base['function'], $path );
		}

		return $literal;
	}

	/**
	 * Check that a URL really continues after its base, rather than only
	 * sharing its first characters: the rest must start with `/`, `?` or `#`,
	 * or be empty. This rejects example.com.co and example.com:8080 for the
	 * base example.com, and /blogger for /blog.
	 *
	 * @param string $rest The part of the URL after the base.
	 * @return bool
	 */
	private static function is_url_boundary( $rest ) {
		return '' === $rest || false !== strpos( '/?#', $rest[0] );
	}

	/**
	 * Build the PHP call that returns base + path at runtime.
	 *
	 * @param string $url_function Function returning the base URL.
	 * @param string $path         Rest of the URL after the base, e.g. '/about/?a=1#top'.
	 * @return string E.g. `home_url( '/about/?a=1#top' )` or
	 *                `get_template_directory_uri() . '/assets/logo.svg'`.
	 */
	private static function build_url_call( $url_function, $path ) {
		// Theme directory URLs take no path argument; the path is appended.
		if ( in_array( $url_function, array( 'get_template_directory_uri', 'get_stylesheet_directory_uri' ), true ) ) {
			return '' === $path ? $url_function . '()' : $url_function . "() . '" . CBT_Theme_Utils::escape_php_single_quoted_string( $path ) . "'";
		}

		$path = '/' . ltrim( $path, '/' );

		return $url_function . "( '" . CBT_Theme_Utils::escape_php_single_quoted_string( $path ) . "' )";
	}

	/**
	 * Decode JSON string escapes (`\/`, `\u0026`) in a URL taken from block
	 * comment JSON, so the URL can be passed to a PHP function. Text without
	 * valid JSON escapes is returned unchanged.
	 *
	 * @param string $value URL or part of a URL.
	 * @return string
	 */
	private static function decode_json_escapes( $value ) {
		if ( false === strpos( $value, '\\' ) ) {
			return $value;
		}

		$decoded = json_decode( '"' . $value . '"' );

		return is_string( $decoded ) ? $decoded : $value;
	}
}
