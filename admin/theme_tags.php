<?php
/**
 * Handles the theme tags functionality.
 *
 * @package Create Block Theme
 */

/**
 * Build theme tags list for readme.txt
 *
 * @param array $theme Theme data.
 * @return string
 * @since 1.5.2
 */
function theme_tags_list( $theme ) {
	$checkbox_tags_merged = array_merge( $theme['tags-subject'] ?? array(), $theme['tags-layout'] ?? array(), $theme['tags-features'] ?? array() );
	$checkbox_tags        = $checkbox_tags_merged ? ', ' . implode( ', ', $checkbox_tags_merged ) : '';
	$custom_tags          = $theme['tags-custom'] ? ', ' . $theme['tags-custom'] : '';
	$tags                 = $checkbox_tags . $custom_tags;

	if ( substr( $tags, 0, 2 ) === ', ' ) {
		$tags = substr( $tags, 2 );
	}

	return $tags;
}

/**
 * Render theme tags form section
 *
 * @since 1.5.2
 */
function theme_tags_section() {
	_e( 'Theme Tags:', 'create-block-theme' );

	echo '<br /><small>';

	printf(
		/* Translators: Theme Tags link. */
		esc_html__( 'Add theme tags to help categorize the theme (%s).', 'create-block-theme' ),
		'<a href="' . esc_url( __( 'https://make.wordpress.org/themes/handbook/review/required/theme-tags/', 'create-block-theme' ) ) . '">read more</a>'
	);

	echo '</small><br />';

	echo '<div class="theme-tags">';

	// Generate list of theme tags
	$theme_tags = get_theme_feature_list();

	if ( ! is_array( $theme_tags ) ) {
		return null;
	}

	// Lists default tags
	function listDefaultTags() {
		$default_tags = array( 'full-site-editing' );
		return $default_tags;
	}

	// Checks if a tag is a default tag
	function isDefaultTag( $tag ) {
		if ( ! is_string( $tag ) ) {
			return null;
		}

		$tag          = strtolower( $tag );
		$default_tags = listDefaultTags();

		return in_array( $tag, $default_tags );
	}

	// Checks if a tag is included in the active theme or the default tags
	function isActiveThemeTag( $tag ) {
		if ( ! is_string( $tag ) ) {
			return null;
		}

		$tag               = strtolower( $tag );
		$active_theme_tags = wp_get_theme()->get( 'Tags' );
		$default_tags      = listDefaultTags();
		$merged_tags       = array_unique( array_merge( $default_tags, $active_theme_tags ) );

		return in_array( $tag, $merged_tags );
	}

	if ( is_array( $theme_tags ) ) {
		// Sort tags by relevance
		krsort( $theme_tags );

		foreach ( $theme_tags as $key => $value ) {
			if ( 'Features' !== $key ) {
				?>
					<fieldset id="<?php echo strtolower( $key ); ?>_tags">
						<legend class="large-text">
						<?php
						if ( 'Subject' === $key ) {
							echo $key . ' ' . __( '(max 3 tags)', 'create-block-theme' ) . ':';
						} else {
							echo $key . ':'; }
						?>
						</legend>
				<?php
				foreach ( $value as $tag => $pretty_tag ) {
					?>
							<input
								type="checkbox"
								id="theme-tag-<?php echo $tag; ?>"
								name="theme[tags-<?php echo strtolower( $key ); ?>][]"
								value="<?php echo $tag; ?>"
								class="
								<?php
								if ( isDefaultTag( $tag ) ) {
									echo 'default-tag';
								}
								?>
								"
								<?php
								if ( isActiveThemeTag( $tag ) ) {
									echo ' checked';
								}
								?>
							>
							<label for="theme-tag-<?php echo $tag; ?>"><?php echo $pretty_tag; ?></label>
							<br />
						<?php
				}
				?>
					</fieldset>
					<?php
			}
			if ( 'Features' === $key ) {
				// Split array in half to display in two columns
				$half         = ceil( count( $value ) / 2 );
				$features_one = array_slice( $value, 0, $half );
				$features_two = array_slice( $value, $half );
				?>
					<fieldset id="features_tags_1">
						<legend class="large-text"><?php echo $key . ':'; ?></legend>
				<?php
				foreach ( $features_one as $tag => $pretty_tag ) {
					?>
							<input
								type="checkbox"
								id="theme-tag-<?php echo $tag; ?>"
								name="theme[tags-<?php echo strtolower( $key ); ?>][]"
								value="<?php echo $tag; ?>"
								class="
								<?php
								if ( isDefaultTag( $tag ) ) {
									echo 'default-tag';
								}
								?>
								"
								<?php
								if ( isActiveThemeTag( $tag ) ) {
									echo ' checked';
								}
								?>
							>
							<label for="theme-tag-<?php echo $tag; ?>"><?php echo $pretty_tag; ?></label>
							<br />
						<?php
				}
				?>
					</fieldset>
					<fieldset id="features_tags_2">
				<?php
				foreach ( $features_two as $tag => $pretty_tag ) {
					?>
							<input
								type="checkbox"
								id="theme-tag-<?php echo $tag; ?>"
								name="theme[tags-<?php echo strtolower( $key ); ?>][]"
								value="<?php echo $tag; ?>"
								class="
								<?php
								if ( isDefaultTag( $tag ) ) {
									echo 'default-tag';
								}
								?>
								"
								<?php
								if ( isActiveThemeTag( $tag ) ) {
									echo ' checked';
								}
								?>
							>
							<label for="theme-tag-<?php echo $tag; ?>"><?php echo $pretty_tag; ?></label>
							<br />
						<?php
				}
				?>
					</fieldset>
				<?php
			}
		}
	}

	echo '</div>';

	?>

		<br />
		<small><?php _e( 'Add custom tags (single or hyphenated words, separated by commas):', 'create-block-theme' ); ?></small><br />
		<input placeholder="<?php _e( 'custom, tags, custom-tags', 'create-block-theme' ); ?>" type="text" name="theme[tags-custom]" class="large-text code" pattern="^[a-zA-Z\-]+(\s*,\s*[a-zA-Z]+)*$" />

	<?php

}
