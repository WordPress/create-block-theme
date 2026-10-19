<?php
/**
 * Handles the theme tags functionality.
 *
 * @package Create Block Theme
 */

class CBT_Theme_Tags {
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
		$custom_tags          = explode( ',', $theme['tags_custom'] ?? '' );
		$tags                 = self::normalize_tags( array_merge( $checkbox_tags_merged, $custom_tags ) );

		return implode( ', ', $tags );
	}

	/**
	 * Get mapping of lowercase feature labels and slugs to canonical slugs.
	 *
	 * @return array<string, string> Map of lowercase labels and slugs to canonical slugs.
	 */
	protected static function get_label_to_slug_map() {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}

		$map = array();
		if ( ! function_exists( 'get_theme_feature_list' ) ) {
			return $map;
		}

		$feature_list = get_theme_feature_list( false );
		if ( ! is_array( $feature_list ) ) {
			return $map;
		}

		foreach ( $feature_list as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			foreach ( $group as $slug => $label ) {
				$map[ strtolower( $label ) ] = $slug;
			}
		}

		return $map;
	}

	/**
	 * Normalize an array or comma-separated list of tags to valid, lowercase WordPress theme slugs.
	 *
	 * @param array|string $tags Tags to normalize.
	 * @return array Normalized list of tag slugs.
	 */
	public static function normalize_tags( $tags ) {
		if ( is_string( $tags ) ) {
			$tags = explode( ',', $tags );
		}

		if ( ! is_array( $tags ) ) {
			return array();
		}

		$label_to_slug = self::get_label_to_slug_map();
		$normalized    = array();

		foreach ( $tags as $tag ) {
			if ( ! is_string( $tag ) ) {
				continue;
			}

			$tag = trim( $tag );
			if ( '' === $tag ) {
				continue;
			}

			$lower_tag    = strtolower( $tag );
			$normalized[] = $label_to_slug[ $lower_tag ] ?? sanitize_title( $lower_tag );
		}

		return array_values( array_unique( array_filter( $normalized ) ) );
	}

	/**
	 * Render theme tags form section.
	 *
	 * @since 1.5.2
	 */
	public static function theme_tags_section() {
		?>
<div role="group" aria-labelledby="theme_tags_label">
	<fieldset id="theme_tags_label">
		<legend><?php _e( 'Theme Tags', 'create-block-theme' ); ?></legend>

		<p class="description">
			<?php
			printf(
				/* Translators: Theme Tags link. */
				esc_html__(
					'Add theme tags to help categorize the theme (%s).',
					'create-block-theme'
				),
				'<a href="' . esc_url( __( 'https://make.wordpress.org/themes/handbook/review/required/theme-tags/', 'create-block-theme' ) ) . '" target="_blank">' . __( 'read more', 'create-block-theme' ) . '</a>'
			);
			?>
		</p>

		<div class="theme-tags">
			<?php
			// Generate a list of theme tags
			$theme_tags = get_theme_feature_list();
			if ( is_array( $theme_tags ) ) {
				// Sort tags by relevance
				krsort( $theme_tags );
				foreach ( $theme_tags as $category => $tags ) {
					self::theme_tags_category( $category, $tags );
				}
			}
			?>
		</div>

		<p>
			<label for="theme-custom-tags"><?php _e( 'Custom Tags', 'create-block-theme' ); ?></label>
			<?php // Regex for pattern attribute ensures only single words or words with hyphens are used, separated by commas ?>
			<input id="theme-custom-tags" placeholder="<?php _e( 'custom, tags, custom-tags', 'create-block-theme' ); ?>" type="text" name="theme[tags_custom]" class="large-text code" pattern="^[a-zA-Z\-]+(\s*,\s*[a-zA-Z\-]+)*$" aria-describedby="custom-tags-description">
		</p>
		<p id="custom-tags-description" class="description" >
			<?php _e( 'Add custom tags (single or hyphenated words, separated by commas)', 'create-block-theme' ); ?>
		</p>

	</fieldset>
</div>
		<?php
	}

	/**
	 * Output theme tags fieldset.
	 */
	protected static function theme_tags_category( $category, $tags ) {
		?>
		<fieldset id="<?php echo esc_attr( strtolower( $category ) ); ?>-tags">
			<?php if ( 'Subject' === $category ) : ?>
				<legend class="large-text"><?php echo esc_html( $category ); ?>&nbsp;<?php _e( '(max 3 tags)', 'create-block-theme' ); ?></legend>
			<?php else : ?>
				<legend class="large-text"><?php echo esc_html( $category ); ?></legend>
			<?php endif; ?>

			<?php foreach ( $tags as $tag => $pretty_tag ) : ?>
				<?php self::tag_checkbox_input( $category, $tag, $pretty_tag ); ?>
			<?php endforeach; ?>
		</fieldset>
		<?php
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
		$active_theme_tags = is_array( $active_theme_tags ) ? self::normalize_tags( $active_theme_tags ) : array();
		$default_tags      = self::list_default_tags();
		$merged_tags       = array_unique( array_merge( $default_tags, $active_theme_tags ) );

		return in_array( $tag, $merged_tags, true );
	}

	/**
	 * Build checkbox input for given theme tag.
	 *
	 * @param string $category
	 * @param string $tag
	 * @param string $pretty_tag
	 * @return void
	 */
	protected static function tag_checkbox_input( $category, $tag, $pretty_tag ) {
		$class   = '';
		$checked = '';

		if ( self::is_default_tag( $tag ) ) {
			$class = 'default-tag';
		}

		if ( self::is_active_theme_tag( $tag ) ) {
			$checked = ' checked';
		}
		?>
<div class="theme-tag-form-control">
	<input type="checkbox" id="theme-tag-<?php echo esc_attr( $tag ); ?>" name="theme[tags-<?php echo esc_attr( strtolower( $category ) ); ?>][]" value="<?php echo esc_attr( $tag ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_html( $checked ); ?>>
	<label for="theme-tag-<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $pretty_tag ); ?></label>
</div>
		<?php
	}
}
