<?php

require_once( __DIR__ . '/theme-tags.php' );

class Theme_Form {
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
			<h2><?php _ex( 'Create Block Theme', 'UI String', 'create-block-theme' ); ?></h2>
			<form enctype="multipart/form-data" method="POST">
				<div id="col-container">
					<div id="col-left">
						<div class="col-wrap">
							<p>
							<?php
							/* translators: %1$s: Theme Name. */
							printf( esc_html__( 'Export your current block theme (%1$s) with changes you made to Templates, Template Parts and Global Styles.', 'create-block-theme' ), esc_html( wp_get_theme()->get( 'Name' ) ) );
							?>
							</p>

							<label>
								<input checked value="export" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" />
								<?php
									printf(
										/* translators: %s: Theme Name. */
										__( 'Export %s', 'create-block-theme' ),
										wp_get_theme()->get( 'Name' )
									);
								?>
								<br />
								<?php _e( '[Export the activated theme with user changes]', 'create-block-theme' ); ?>
							</label>
							<br /><br />
							<?php if ( is_child_theme() ) : ?>
								<label>
									<input value="sibling" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );"/>
									<?php
									printf(
										/* translators: %s: Theme Name. */
										__( 'Create sibling of %s', 'create-block-theme' ),
										wp_get_theme()->get( 'Name' )
									);
									?>
								</label>
								<br />
								<?php _e( '[Create a new theme cloning the activated child theme.  The parent theme will be the same as the parent of the currently activated theme. The resulting theme will have all of the assets of the activated theme, none of the assets provided by the parent theme, as well as user changes.]', 'create-block-theme' ); ?>
								<br /><br />
							<?php else : ?>
								<label>
									<input value="child" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );"/>
									<?php
									printf(
										/* translators: %s: Theme Name. */
										__( 'Create child of %s', 'create-block-theme' ),
										wp_get_theme()->get( 'Name' )
									);
									?>
								</label>
								<br />
								<?php _e( '[Create a new child theme. The currently activated theme will be the parent theme.]', 'create-block-theme' ); ?>
								<br /><br />
								<label>
									<input value="clone" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );"/>
									<?php
										printf(
											/* translators: %s: Theme Name. */
											__( 'Clone %s', 'create-block-theme' ),
											wp_get_theme()->get( 'Name' )
										);
									?>
									<br />
									<?php _e( '[Create a new theme cloning the activated theme. The resulting theme will have all of the assets of the activated theme as well as user changes.]', 'create-block-theme' ); ?>
								</label>
								<br /><br />
							<?php endif; ?>
							<label>
								<input value="save" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" />
								<?php
									printf(
										/* translators: %s: Theme Name. */
										__( 'Overwrite %s', 'create-block-theme' ),
										wp_get_theme()->get( 'Name' )
									);
								?>
								<br />
								<?php _e( '[Save USER changes as THEME changes and delete the USER changes.  Your changes will be saved in the theme on the folder.]', 'create-block-theme' ); ?>
							</label>
							<br /><br />
							<label>
								<input value="blank" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" />
								<?php _e( 'Create blank theme', 'create-block-theme' ); ?><br />
								<?php _e( '[Generate a boilerplate "empty" theme inside of this site\'s themes directory.]', 'create-block-theme' ); ?>
							</label>
							<br /><br />
							<label>
								<input value="variation" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( this );" />
								<?php _e( 'Create a style variation', 'create-block-theme' ); ?><br />
								<?php
								printf(
									// translators: %1$s: Theme name
									esc_html__( '[Save user changes as a style variation of %1$s.]', 'create-block-theme' ),
									esc_html( wp_get_theme()->get( 'Name' ) )
								);
								?>
							</label>
							<br /><br />

							<input type="submit" value="<?php _e( 'Generate', 'create-block-theme' ); ?>" class="button button-primary" />

						</div>
					</div>
					<div id="col-right">
						<div class="col-wrap">
							<div hidden id="new_variation_metadata_form" class="theme-form">
								<p><em><?php _e( 'Items indicated with (*) are required.', 'create-block-theme' ); ?></em></p>
								<label>
									<?php _e( 'Variation Name (*):', 'create-block-theme' ); ?><br />
									<input placeholder="<?php _e( 'Variation Name', 'create-block-theme' ); ?>" type="text" name="theme[variation]" class="large-text" />
								</label>
							</div>
							<div hidden id="new_theme_metadata_form" class="theme-form">
								<p><em><?php _e( 'Items indicated with (*) are required.', 'create-block-theme' ); ?></em></p>
								<label>
									<?php _e( 'Theme Name (*):', 'create-block-theme' ); ?><br />
									<input placeholder="<?php _e( 'Theme Name', 'create-block-theme' ); ?>" type="text" name="theme[name]" class="large-text" id="theme-name" autocomplete="off" />
								</label>
								<br /><br />
								<label>
									<?php _e( 'Theme Description:', 'create-block-theme' ); ?><br />
									<textarea placeholder="<?php _e( 'A short description of the theme.', 'create-block-theme' ); ?>" rows="4" cols="50" name="theme[description]" class="large-text"></textarea>
								</label>
								<br /><br />
								<label>
									<?php _e( 'Theme URI:', 'create-block-theme' ); ?><br />
									<small><?php _e( 'The URL of a public web page where users can find more information about the theme.', 'create-block-theme' ); ?></small><br />
									<input placeholder="<?php _e( 'https://github.com/wordpress/twentytwentythree/', 'create-block-theme' ); ?>" type="text" name="theme[uri]" class="large-text code" />
								</label>
								<br /><br />
								<label>
									<?php _e( 'Author:', 'create-block-theme' ); ?><br />
									<small><?php _e( 'The name of the individual or organization who developed the theme.', 'create-block-theme' ); ?></small><br />
									<input placeholder="<?php _e( 'the WordPress team', 'create-block-theme' ); ?>" type="text" name="theme[author]" class="large-text" />
								</label>
								<br /><br />
								<label>
									<?php _e( 'Author URI:', 'create-block-theme' ); ?><br />
									<small><?php _e( 'The URL of the authoring individual or organization.', 'create-block-theme' ); ?></small><br />
									<input placeholder="<?php _e( 'https://wordpress.org/', 'create-block-theme' ); ?>" type="text" name="theme[author_uri]" class="large-text code" />
								</label>
								<br /><br />
								<label for="screenshot">
									<?php _e( 'Screenshot:', 'create-block-theme' ); ?><br />
									<small><?php _e( 'Upload a new theme screenshot (2mb max | .png only | 1200x900 recommended)', 'create-block-theme' ); ?></small><br />
									<input type="file" accept=".png"  name="screenshot" id="screenshot" class="upload"/>
								</label>
								<br /><br />
								<label class="hide-on-blank-theme">
									<?php _e( 'Image Credits:', 'create-block-theme' ); ?><br />
									<small><?php _e( 'List the credits for each image you have included in the theme. Include the image name, license type, and source URL.', 'create-block-theme' ); ?></small><br />
									<small>
										<?php
										printf(
											/* Translators: Bundled resources licenses link. */
											esc_html__( 'All bundled resources must have GPL-compatible licenses (%s).', 'create-block-theme' ),
											'<a href="' . esc_url( __( 'https://make.wordpress.org/themes/handbook/review/resources/#licenses-bundled-resources', 'create-block-theme' ) ) . '" target="_blank">read more</a>'
										);
										?>
									</small><br />
									<?php
									$image_credits_placeholder = __(
										'Image Title
License Type
Source: https://example.com/source-url',
										'create-block-theme'
									);
									?>
									<textarea placeholder="<?php echo $image_credits_placeholder; ?>" rows="4" cols="50" name="theme[image_credits]" class="large-text"></textarea>
									<br /><br />
								</label>
								<label class="hide-on-blank-theme">
									<?php _e( 'Recommended Plugins:', 'create-block-theme' ); ?><br />
									<small>
										<?php
										printf(
											/* Translators: Recommended plugins link. */
											esc_html__( 'List the recommended plugins for this theme. e.g. contact forms, social media. Plugins must be from the WordPress.org plugin repository (%s).', 'create-block-theme' ),
											'<a href="' . esc_url( __( 'https://make.wordpress.org/themes/handbook/review/required/#6-plugins', 'create-block-theme' ) ) . '" target="_blank">read more</a>'
										);
										?>
									</small><br />
									<?php
									$recommended_plugins_placeholder = __(
										'Plugin Name
https://wordpress.org/plugins/plugin-name/
Plugin Description',
										'create-block-theme'
									);
									?>
									<textarea placeholder="<?php echo $recommended_plugins_placeholder; ?>" rows="4" cols="50" name="theme[recommended_plugins]" class="large-text"></textarea>
									<br /><br />
								</label>
								<div>
									<?php Theme_Tags::theme_tags_section(); ?>
								</div>
							</div>
							<input type="hidden" name="page" value="create-block-theme" />
							<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	public static function form_script() {
		wp_enqueue_script( 'form-script', plugin_dir_url( dirname( __FILE__ ) ) . 'js/form-script.js' );
		wp_enqueue_style( 'form-style', plugin_dir_url( dirname( __FILE__ ) ) . 'css/form.css' );

		// Enable localization in the form.
		wp_set_script_translations( 'form-script', 'create-block-theme' );
	}
}
