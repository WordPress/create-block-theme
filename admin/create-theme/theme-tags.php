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
	 * Lists default tags.
	 *
	 * @return array
	 */
	protected static function list_default_tags() {
		$default_tags = array( 'full-site-editing' );
		return $default_tags;
	}

	/**
	 * Checks if a tag is a default tag.
	 *
	 * @param string $tag
	 * @return boolean
	 */
	protected static function is_default_tag( $tag ) {
		if ( ! is_string( $tag ) ) {
			return null;
		}

		$tag          = strtolower( $tag );
		$default_tags = self::list_default_tags();

		return in_array( $tag, $default_tags, true );
	}

	/**
	 * Checks if a tag is included in the active theme or the default tags.
	 *
	 * @param string $tag
	 * @return boolean
	 */
	protected static function is_active_theme_tag( $tag ) {
		if ( ! is_string( $tag ) ) {
			return null;
		}

		$tag               = strtolower( $tag );
		$active_theme_tags = wp_get_theme()->get( 'Tags' );
		$default_tags      = self::list_default_tags();
		$merged_tags       = array_unique( array_merge( $default_tags, $active_theme_tags ) );

		return in_array( $tag, $merged_tags, true );
	}
}
