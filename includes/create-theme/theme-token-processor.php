<?php
/**
 * Fine grained token processing class.
 */
class CBT_Token_Processor {
	private $p;
	private $tokens           = array();
	private $text             = '';
	private $translators_note = '/* Translators: ';
	private $increment        = 0;

	/**
	 * Constructor.
	 *
	 * @param string $string The string to process.
	 */
	public function __construct( $string ) {
		$this->p = new WP_HTML_Tag_Processor( $string );
	}

	/**
	 * Processes the HTML tags in the string and updates tokens, text, and translators' note.
	 *
	 * @param $p The string to process.
	 * @return void
	 */
	public function process_tokens() {
		while ( $this->p->next_token() ) {
			$token_type      = $this->p->get_token_type();
			$token_name      = strtolower( $this->p->get_token_name() );
			$is_tag_closer   = $this->p->is_tag_closer();
			$has_self_closer = $this->p->has_self_closing_flag();

			if ( '#tag' === $token_type ) {
				$this->increment++;
				$this->text .= '%' . $this->increment . '$s';
				$token_label = $this->increment . '.';

				if ( 1 !== $this->increment ) {
					$this->translators_note .= ', ';
				}

				if ( $is_tag_closer ) {
					$this->tokens[]          = "</{$token_name}>";
					$this->translators_note .= $token_label . " is the end of a '" . $token_name . "' HTML element";
				} else {
					$token      = '<' . $token_name;
					$attributes = $this->p->get_attribute_names_with_prefix( '' );

					foreach ( $attributes as $attr_name ) {
						$attr_value = $this->p->get_attribute( $attr_name );
						$token     .= $this->process_attribute( $attr_name, $attr_value );
					}

					$token         .= '>';
					$this->tokens[] = $token;

					if ( $has_self_closer || 'br' === $token_name ) {
						$this->translators_note .= $token_label . " is a '" . $token_name . "' HTML element";
					} else {
						$this->translators_note .= $token_label . " is the start of a '" . $token_name . "' HTML element";
					}
				}
			} else {
				// Escape text content.
				$temp_text = $this->p->get_modifiable_text();

				// If the text contains a %, we need to escape it.
				if ( false !== strpos( $temp_text, '%' ) ) {
					$temp_text = str_replace( '%', '%%', $temp_text );
				}

				$this->text .= $temp_text;
			}
		}

		if ( ! empty( $this->tokens ) ) {
			$this->translators_note .= ' */ ';
		}
	}

	/**
	 * Processes individual tag attributes and escapes where necessary.
	 *
	 * @param string $attr_name The name of the attribute.
	 * @param string $attr_value The value of the attribute.
	 * @return string The processed attribute.
	 */
	private function process_attribute( $attr_name, $attr_value ) {
		$token_part = '';
		if ( empty( $attr_value ) ) {
			$token_part .= ' ' . $attr_name;
		} elseif ( 'src' === $attr_name ) {
			CBT_Theme_Media::add_media_to_local( array( $attr_value ) );
			$relative_src = CBT_Theme_Media::get_media_folder_path_from_url( $attr_value ) . basename( $attr_value );
			$attr_value   = "' . esc_url( get_stylesheet_directory_uri() ) . '{$relative_src}";
			$token_part  .= ' ' . $attr_name . '="' . $attr_value . '"';
		} elseif ( 'href' === $attr_name ) {
			$attr_value  = "' . esc_url( '$attr_value' ) . '";
			$token_part .= ' ' . $attr_name . '="' . $attr_value . '"';
		} else {
			$token_part .= ' ' . $attr_name . '="' . $attr_value . '"';
		}

		return $token_part;
	}

	/**
	 * Gets the processed text.
	 *
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}

	/**
	 * Gets the processed tokens.
	 *
	 * @return array
	 */
	public function get_tokens() {
		return $this->tokens;
	}

	/**
	 * Gets the generated translators' note.
	 *
	 * @return string
	 */
	public function get_translators_note() {
		return $this->translators_note;
	}
}
