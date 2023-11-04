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

		<?php $theme_tags = get_theme_feature_list(); // Generate a list of theme tags ?>

		<?php if ( is_array( $theme_tags ) ) : ?>
			<?php krsort( $theme_tags ); // Sort tags by relevance ?>
			<?php foreach ( $theme_tags as $category => $tags ) : ?>
				<?php if ( 'Features' !== $category ) : ?>
			<fieldset id="<?php echo esc_attr( strtolower( $category ) ); ?>_tags">
					<?php if ( 'Subject' === $category ) : ?>
			<legend class="large-text"><?php echo esc_html( $category ); ?>&nbsp;<?php _e( '(max 3 tags)', 'create-block-theme' ); ?></legend>
				<?php else : ?>
			<legend class="large-text"><?php echo esc_html( $category ); ?></legend>
				<?php endif; ?>

					<?php foreach ( $tags as $tag => $pretty_tag ) : ?>
						<?php self::tag_checkbox_input( $category, $tag, $pretty_tag ); ?>
				<?php endforeach; ?>

			</fieldset>
			<?php endif; ?>
				<?php if ( 'Features' === $category ) : ?>
					<?php
					// Split features array in half to display in two columns
					$half         = ceil( count( $tags ) / 2 );
					$features_one = array_slice( $tags, 0, $half );
					$features_two = array_slice( $tags, $half );
					?>

			<fieldset id="features-tags-1">
				<legend class="large-text"><?php echo esc_html( $category ); ?></legend>
					<?php
					foreach ( $features_one as $tag => $pretty_tag ) {
						self::tag_checkbox_input( $category, $tag, $pretty_tag );
					}
					?>
			</fieldset>

			<fieldset id="features-tags-2">
					<?php
					foreach ( $features_two as $tag => $pretty_tag ) {
						self::tag_checkbox_input( $category, $tag, $pretty_tag );
					}
					?>
			</fieldset>

			<?php endif; ?>
		<?php endforeach; ?>
		<?php endif; ?>
		</div>

		<label>
			<?php // Regex for pattern attribute ensures only single words or words with hyphens are used, separated by commas ?>
			<input placeholder="' . __( 'custom, tags, custom-tags', 'create-block-theme' ) . '" type="text" name="theme[tags_custom]" class="large-text code" pattern="^[a-zA-Z\-]+(\s*,\s*[a-zA-Z\-]+)*$" aria-describedby="custom-tags-description" />
		</label>
		<p id="custom-tags-description" class="description" >
			<?php _e( 'Add custom tags (single or hyphenated words, separated by commas)', 'create-block-theme' ); ?>
		</p>

	</fieldset>
</div>
		<?php
	}

	/**
	 * Lists default tags.
	 *
	 * @return array
	 */
	public static function list_default_tags() {
		$default_tags = array( 'full-site-editing' );
		return $default_tags;
	}

	/**
	 * Checks if a tag is a default tag.
	 *
	 * @param string $tag
	 * @return boolean
	 */
	public static function is_default_tag( $tag ) {
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
	public static function is_active_theme_tag( $tag ) {
		if ( ! is_string( $tag ) ) {
			return null;
		}

		$tag               = strtolower( $tag );
		$active_theme_tags = wp_get_theme()->get( 'Tags' );
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
	public static function tag_checkbox_input( $category, $tag, $pretty_tag ) {
		$class   = '';
		$checked = '';

		if ( self::is_default_tag( $tag ) ) {
			$class = 'default-tag';
		}

		if ( self::is_active_theme_tag( $tag ) ) {
			$checked = ' checked';
		}
		?>
<p>
	<input type="checkbox" id="theme-tag-<?php echo esc_attr( $tag ); ?>" name="theme[tags-<?php echo esc_attr( strtolower( $category ) ); ?>][]" value="<?php echo esc_attr( $tag ); ?>" class="<?php echo esc_attr( $class ); ?>" <?php echo esc_html( $checked ); ?>>
	<label for="theme-tag-<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( $pretty_tag ); ?></label>
</p>
		<?php
	}

}
