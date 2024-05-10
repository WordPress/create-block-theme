<?php

require_once( __DIR__ . '/theme-tags.php' );

class CBT_Theme_Form {
	public static function create_admin_form_page() {
		if ( ! wp_is_block_theme() ) {
			?>
<div class="wrap">
	<h2><?php _ex( 'Create Block Theme', 'UI String', 'create-block-theme' ); ?></h2>
	<p><?php _e( 'Activate a block theme to use this tool.', 'create-block-theme' ); ?></p>
</div>
			<?php
			return;
		}
		?>
<div class="wrap">
	<div class="notice notice-warning">
		<p>
			<?php
				printf(
					/* translators: %s: editor link. */
					esc_html__( 'This page is deprecated and will be removed in the next release. Please try exporting from the Create Block Theme menu of the %s instead.', 'create-block-theme' ),
					' <a href="' . esc_url( admin_url( 'site-editor.php?canvas=edit' ) ) . '">' . __( 'editor', 'create-block-theme' ) . '</a>'
				);
			?>
		</p>
	</div>
	<h1><?php _ex( 'Create Block Theme', 'UI String', 'create-block-theme' ); ?></h1>
	<p>
		<?php
		/* translators: %1$s: Theme Name. */
		printf( esc_html__( 'Export your current block theme (%1$s) with changes you made to Templates, Template Parts and Global Styles.', 'create-block-theme' ), esc_html( wp_get_theme()->get( 'Name' ) ) );
		?>
	</p>
	<h2><?php _e( 'Choose what to export', 'create-block-theme' ); ?></h2>
	<form enctype="multipart/form-data" method="POST">
		<div id="col-container">
			<div id="col-left">
				<div class="col-wrap">
					<p>
						<label>
							<input checked value="export" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="export-description">
							<?php
								printf(
									/* translators: %s: Theme Name. */
									__( 'Export %s', 'create-block-theme' ),
									wp_get_theme()->get( 'Name' )
								);
							?>
						</label>
					</p>
					<p id="export-description" class="description">
						<?php _e( 'Export the activated theme with user changes', 'create-block-theme' ); ?>
					</p>

					<?php if ( is_child_theme() ) : ?>

						<p>
							<label>
								<input value="sibling" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="sibling-description">
								<?php
								printf(
									/* translators: %s: Theme Name. */
									__( 'Create sibling of %s', 'create-block-theme' ),
									wp_get_theme()->get( 'Name' )
								);
								?>
							</label>
						</p>
						<p id="sibling-description" class="description">
							<?php _e( 'Create a new theme cloning the activated child theme. The parent theme will be the same as the parent of the currently activated theme. The resulting theme will have all of the assets of the activated theme, none of the assets provided by the parent theme, as well as user changes.', 'create-block-theme' ); ?>
						</p>

					<?php else : ?>

						<p>
							<label>
								<input value="child" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="child-description">
								<?php
								printf(
									/* translators: %s: Theme Name. */
									__( 'Create child of %s', 'create-block-theme' ),
									wp_get_theme()->get( 'Name' )
								);
								?>
							</label>
						</p>
						<p id="child-description" class="description">
							<?php _e( 'Create a new child theme. The currently activated theme will be the parent theme.', 'create-block-theme' ); ?>
						</p>

						<p>
							<label>
								<input value="clone" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="clone-description">
								<?php
									printf(
										/* translators: %s: Theme Name. */
										__( 'Clone %s', 'create-block-theme' ),
										wp_get_theme()->get( 'Name' )
									);
								?>
							</label>
						</p>
						<p id="clone-description" class="description">
							<?php _e( 'Create a new theme cloning the activated theme. The resulting theme will have all of the assets of the activated theme as well as user changes.', 'create-block-theme' ); ?>
						</p>

					<?php endif; ?>

					<p>
						<label>
							<input value="save" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="save-description">
							<?php
								printf(
									/* translators: %s: Theme Name. */
									__( 'Overwrite %s', 'create-block-theme' ),
									wp_get_theme()->get( 'Name' )
								);
							?>
						</label>
					</p>
					<p id="save-description" class="description">
						<?php _e( 'Save USER changes as THEME changes and delete the USER changes.  Your changes will be saved in the theme on the folder.', 'create-block-theme' ); ?>
					</p>

					<p>
						<label>
							<input value="blank" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="blank-description">
							<?php _e( 'Create blank theme', 'create-block-theme' ); ?>
						</label>
					</p>
					<p id="blank-description" class="description">
						<?php _e( 'Generate a boilerplate "empty" theme inside of this site\'s themes directory.', 'create-block-theme' ); ?>
					</p>

					<p>
						<label>
							<input value="variation" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" aria-describedby="variation-description">
							<?php _e( 'Create a style variation', 'create-block-theme' ); ?>
						</label>
					</p>
					<p id="variation-description" class="description">
						<?php
						printf(
							// translators: %1$s: Theme name
							esc_html__( 'Save user changes as a style variation of %1$s.', 'create-block-theme' ),
							esc_html( wp_get_theme()->get( 'Name' ) )
						);
						?>
					</p>
				</div>
			</div>

			<div id="col-right">
				<div class="col-wrap">
					<div hidden id="new_variation_metadata_form" class="theme-form">
						<p><em><?php _e( 'Items indicated with (*) are required.', 'create-block-theme' ); ?></em></p>
						<p>
							<label for="variation-name">
								<?php _e( 'Variation Name (*)', 'create-block-theme' ); ?>
							</label>
							<input id="variation-name" placeholder="<?php _e( 'Variation Name', 'create-block-theme' ); ?>" type="text" name="theme[variation]" class="large-text">
						</p>
					</div>

					<div hidden id="new_theme_metadata_form" class="theme-form">
						<p><em><?php _e( 'Items indicated with (*) are required.', 'create-block-theme' ); ?></em></p>

						<p>
							<label for="theme-name">
								<?php _e( 'Theme Name (*)', 'create-block-theme' ); ?>
							</label>
							<input id="theme-name" placeholder="<?php _e( 'Theme Name', 'create-block-theme' ); ?>" type="text" name="theme[name]" class="large-text" autocomplete="off">
						</p>

						<p>
							<label for="theme-description">
								<?php _e( 'Theme Description', 'create-block-theme' ); ?>
							</label>
							<textarea id="theme-description" placeholder="<?php _e( 'Theme Description', 'create-block-theme' ); ?>" rows="4" cols="50" name="theme[description]" class="large-text" aria-describedby="theme-description-description"></textarea>
						</p>
						<p id="theme-description-description" class="description">
							<?php _e( 'A short description of the theme.', 'create-block-theme' ); ?>
						</p>

						<p>
							<label for="theme-uri">
								<?php _e( 'Theme URI', 'create-block-theme' ); ?>
							</label>
							<input id="theme-uri" placeholder="<?php echo esc_attr( 'https://github.com/wordpress/twentytwentythree/' ); ?>" type="text" name="theme[uri]" class="large-text code" aria-describedby="theme-uri-description">
						</p>
						<p id="theme-uri-description" class="description">
							<?php _e( 'The URL of a public web page where users can find more information about the theme.', 'create-block-theme' ); ?>
						</p>

						<p>
							<label for="theme-author">
								<?php _e( 'Author', 'create-block-theme' ); ?>
							</label>
							<input id="theme-author" placeholder="<?php _e( 'the WordPress team', 'create-block-theme' ); ?>" type="text" name="theme[author]" class="large-text" aria-describedby="theme-author-description">
						</p>
						<p id="theme-author-description" class="description" >
							<?php _e( 'The name of the individual or organization who developed the theme.', 'create-block-theme' ); ?>
						</p>

						<p>
							<label for="theme-author-uri">
								<?php _e( 'Author URI', 'create-block-theme' ); ?>
							</label>
							<input id="theme-author-uri" placeholder="<?php echo esc_attr( 'https://wordpress.org/' ); ?>" type="text" name="theme[author_uri]" class="large-text code" aria-describedby="theme-author-uri-description">
						</p>
						<p id="theme-author-uri-description" class="description">
							<?php _e( 'The URL of the authoring individual or organization.', 'create-block-theme' ); ?>
						</p>

						<p>
							<label for="screenshot">
								<?php _e( 'Screenshot', 'create-block-theme' ); ?>
							</label>
							<input type="file" accept=".png"  name="screenshot" id="screenshot" class="upload" aria-describedby="screenshot-description">
						</p>
						<p id="screenshot-description" class="description">
							<?php _e( 'Upload a new theme screenshot (2mb max | .png only | 1200x900 recommended)', 'create-block-theme' ); ?>
						</p>

						<p class="hide-on-blank-theme">
							<label for="image-credits">
								<?php _e( 'Image Credits', 'create-block-theme' ); ?>
							</label>
							<?php
							/* translators: Image credits placeholder. */
							$image_credits_placeholder  = __( 'Image Title', 'create-block-theme' );
							$image_credits_placeholder .= "\n" . __( 'License Type', 'create-block-theme' );
							$image_credits_placeholder .= "\n" . __( 'Source: https://example.com/source-url', 'create-block-theme' );
							?>
							<textarea id="image-credits" placeholder="<?php echo $image_credits_placeholder; ?>" rows="4" cols="50" name="theme[image_credits]" class="large-text" aria-describedby="image-credits-description"></textarea>
						</p>
						<p id="image-credits-description" class="description">
							<?php _e( 'List the credits for each image you have included in the theme. Include the image name, license type, and source URL.', 'create-block-theme' ); ?><br />
							<?php
								printf(
									/* Translators: Bundled resources licenses link. */
									esc_html__( 'All bundled resources must have GPL-compatible licenses (%s).', 'create-block-theme' ),
									'<a href="' . esc_url( 'https://make.wordpress.org/themes/handbook/review/resources/#licenses-bundled-resources' ) . '" target="_blank">' . __( 'read more', 'create-block-theme' ) . '</a>'
								);
							?>
						</p>

						<p class="hide-on-blank-theme">
							<label for="recommended-plugins">
								<?php _e( 'Recommended Plugins', 'create-block-theme' ); ?>
							</label>
							<?php
							/* translators: Recommended plugins placeholder. */
							$recommended_plugins_placeholder  = __( 'Plugin Name', 'create-block-theme' );
							$recommended_plugins_placeholder .= "\nhttps://wordpress.org/plugins/plugin-name/";
							$recommended_plugins_placeholder .= "\n" . __( 'Plugin Description', 'create-block-theme' );
							?>
							<textarea id="recommended-plugins" placeholder="<?php echo $recommended_plugins_placeholder; ?>" rows="4" cols="50" name="theme[recommended_plugins]" class="large-text" aria-describedby="recommended-plugins-description"></textarea>
						</p>
						<p id="recommended-plugins-description" class="description">
							<?php
							printf(
								/* Translators: Recommended plugins link. */
								esc_html__( 'List the recommended plugins for this theme. e.g. contact forms, social media. Plugins must be from the WordPress.org plugin repository (%s).', 'create-block-theme' ),
								'<a href="' . esc_url( 'https://make.wordpress.org/themes/handbook/review/required/#6-plugins' ) . '" target="_blank">' . __( 'read more', 'create-block-theme' ) . '</a>'
							);
							?>
						</p>

						<div>
							<?php CBT_Theme_Tags::theme_tags_section(); ?>
						</div>
					</div>
					<input type="hidden" name="page" value="create-block-theme">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>">
				</div>
			</div>
		</div>
		<p class="submit">
			<input type="submit" value="<?php _e( 'Generate', 'create-block-theme' ); ?>" class="button button-primary">
		</p>
	</form>
</div>
		<?php
	}

	public static function form_script() {
		if ( ! empty( $_GET['page'] ) && 'create-block-theme' === $_GET['page'] ) {
			wp_enqueue_script( 'form-script', plugin_dir_url( dirname( __FILE__ ) ) . 'js/form-script.js' );
			wp_enqueue_style( 'form-style', plugin_dir_url( dirname( __FILE__ ) ) . 'css/form.css' );

			// Enable localization in the form.
			wp_set_script_translations( 'form-script', 'create-block-theme' );
		}
	}
}
