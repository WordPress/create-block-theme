<?php
/**
 * Handles the theme tags functionality.
 *
 * @package Create Block Theme
 */

class Theme_Tags {
	/**
	 * Build theme tags list for style.css.
	 *
	 * @param array $theme Theme data.
	 * @return string
	 * @since 1.5.2
	 */
	public static function theme_tags_list( $theme ) {
		$checkbox_tags_merged = array_merge(
			$theme['tags-subject'] ?? array(),
			$theme['tags-layout'] ?? array(),
			$theme['tags-features'] ?? array(),
		);
		$custom_tags          = array_map( 'trim', explode( ',', $theme['tags_custom'] ?? '' ) );
		$tags                 = array_unique( array_merge( $checkbox_tags_merged, $custom_tags ) );

		return implode( ', ', $tags );
	}

	/**
	 * Render theme tags form section.
	 *
	 * @since 1.5.2
	 */
	public static function theme_tags_section() {
		echo '<div role="group" aria-labelledby="theme_tags_label">';
		echo '<div id="theme_tags_label">';
		_e( 'Theme Tags:', 'create-block-theme' );
		echo '<br /><small>';
		printf(
			/* Translators: Theme Tags link. */
			esc_html__( 'Add theme tags to help categorize the theme (%s).', 'create-block-theme' ),
			'<a href="' . esc_url( __( 'https://make.wordpress.org/themes/handbook/review/required/theme-tags/', 'create-block-theme' ) ) . '" target="_blank">read more</a>'
		);
		echo '</small><br />';
		echo '</div>';
		echo '<div class="theme-tags">';

		// Generate list of theme tags
		$theme_tags = get_theme_feature_list();

		if ( ! is_array( $theme_tags ) ) {
			return null;
		}

		// Lists default tags
		function list_default_tags() {
			$default_tags = array( 'full-site-editing' );
			return $default_tags;
		}

		// Checks if a tag is a default tag
		function is_default_tag( $tag ) {
			if ( ! is_string( $tag ) ) {
				return null;
			}

			$tag          = strtolower( $tag );
			$default_tags = list_default_tags();

			return in_array( $tag, $default_tags, true );
		}

		// Checks if a tag is included in the active theme or the default tags
		function is_active_theme_tag( $tag ) {
			if ( ! is_string( $tag ) ) {
				return null;
			}

			$tag               = strtolower( $tag );
			$active_theme_tags = wp_get_theme()->get( 'Tags' );
			$default_tags      = list_default_tags();
			$merged_tags       = array_unique( array_merge( $default_tags, $active_theme_tags ) );

			return in_array( $tag, $merged_tags, true );
		}

		// Build checkbox input for given theme tag
		function tag_checkbox_input( $category, $tag, $pretty_tag ) {
			$class   = '';
			$checked = '';

			if ( is_default_tag( $tag ) ) {
				$class = 'default-tag';
			}

			if ( is_active_theme_tag( $tag ) ) {
				$checked = ' checked';
			}

			echo '<input type="checkbox" id="theme-tag-' . $tag . '" name="theme[tags-' . strtolower( $category ) . '][]" value="' . $tag . '" class="' . $class . '" ' . $checked . '>';
			echo '<label for="theme-tag-' . $tag . '">' . $pretty_tag . '</label><br />';
		}

		if ( is_array( $theme_tags ) ) {
			// Sort tags by relevance
			krsort( $theme_tags );

			foreach ( $theme_tags as $category => $tags ) {
				if ( 'Features' !== $category ) {
					echo '<fieldset id="' . strtolower( $category ) . '_tags">';

					if ( 'Subject' === $category ) {
						echo '<legend class="large-text">' . $category . ' ' . __( '(max 3 tags)', 'create-block-theme' ) . ':</legend>';
					} else {
						echo '<legend class="large-text">' . $category . ':</legend>';
					}

					foreach ( $tags as $tag => $pretty_tag ) {
						tag_checkbox_input( $category, $tag, $pretty_tag );
					}

					echo '</fieldset>';
				}
				if ( 'Features' === $category ) {
					// Split features array in half to display in two columns
					$half         = ceil( count( $tags ) / 2 );
					$features_one = array_slice( $tags, 0, $half );
					$features_two = array_slice( $tags, $half );

					echo '<fieldset id="features-tags-1">';
					echo '<legend class="large-text">' . $category . ':</legend>';

					foreach ( $features_one as $tag => $pretty_tag ) {
						tag_checkbox_input( $category, $tag, $pretty_tag );
					}

					echo '</fieldset>';
					echo '<fieldset id="features-tags-2">';

					foreach ( $features_two as $tag => $pretty_tag ) {
						tag_checkbox_input( $category, $tag, $pretty_tag );
					}

					echo '</fieldset>';
				}
			}
		}

		echo '</div>';

		// Custom tags input
		echo '<label>';
		echo '<br /><small>';
		_e( 'Add custom tags (single or hyphenated words, separated by commas):', 'create-block-theme' );
		echo '</small><br />';

		// Regex for pattern attribute ensures only single words or words with hyphens are used, separated by commas
		echo '<input placeholder="' . __( 'custom, tags, custom-tags', 'create-block-theme' ) . '" type="text" name="theme[tags_custom]" class="large-text code" pattern="^[a-zA-Z\-]+(\s*,\s*[a-zA-Z\-]+)*$" />';

		echo '</label>';

		echo '</div>';
	}

}
