<?php

/**
 * Plugin Name: Create Block theme.
 * Plugin URI: https://github.com/Automattic/create-block-theme
 * Description: Generates a block theme
 * Version: 0.0.1
 * Author: Automattic
 * Author URI: https://automattic.com/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: create-block-theme
 */

function augment_gutenberg_with_utilities() {

	//Ultimately it is desireable for Gutenberg to have this functionality natively.
	// In the meantime we are patching the functionality we are expecting into the Theme JSON Resolver here

	class MY_Theme_JSON_Resolver extends WP_Theme_JSON_Resolver_Gutenberg {

		/**
		 * 'Flatten' theme data that expresses both theme and user data.
		 * change property.[custom|theme].value to property.value
		 * Uses custom value if available, otherwise theme value
		 * I feel like there should be a function to do this in Gutenberg but I couldn't find it
		 */
		private static function flatten_theme_json( $data, $name ) {
			if ( is_array( $data ) ) {

				// 'settings' can have a 'custom' object that is different and should not be processed in the same way
				// as values that could be represented as both theme and user (user data being 'custom')
				if ( $name !== 'settings' ) {

					// When there is BOTH custom AND THEME combine the two
					if ( array_key_exists( 'custom', $data ) && array_key_exists( 'theme', $data ) ) {
						$merged = array_merge( $data['custom'], $data['theme'] );
						// eliminate values with duplicate slugs
						// TODO: This could probably be done better...
						$filtered = array();
						$sluglist = array();
						foreach ( $merged as $item ) {
							if( array_key_exists('slug', $item) ) {
								if( ! in_array($item['slug'], $sluglist) ) {
									$sluglist[] = $item['slug'];
									$filtered[] = $item;
								}
							} 
							else {
								$filtered[] = $item;
							}
						}
						return MY_Theme_JSON_Resolver::flatten_theme_json($filtered, $name);
					}

					// When there is CUSTOM but no THEME return custom
					if ( array_key_exists( 'custom', $data ) ) {
						return MY_Theme_JSON_Resolver::flatten_theme_json($data['custom'], $name);
					}

					// When there is THEME but no CUSTOM return theme 
					if ( array_key_exists( 'theme', $data ) ) {
						return MY_Theme_JSON_Resolver::flatten_theme_json($data['theme'], $name);
					}

				}

				foreach( $data as $node_name => $node_value  ) {
					$data[ $node_name ] = MY_Theme_JSON_Resolver::flatten_theme_json( $node_value, $node_name );
				}
			}

			return $data;
		}

		/**
		 * Export the combined (and flattened) THEME and CUSTOM data.
		 * 
		 * @param string $content ['all', 'current', 'user'] Determines which settings content to include in the export.
		 * All options include user settings.  
		 * 'current' will include settings from the currently installed theme but NOT from the parent theme.
		 * 'all' will include settings from the current theme as well as the parent theme (if it has one)
		 */
		public static function export_theme_data( $content ) {

	        	$theme = new WP_Theme_JSON_Gutenberg();

			if ( $content === 'all' && wp_get_theme()->parent() ) {
				// Get parent theme.json.
				$parent_theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json', true ) );
				$parent_theme_json_data = static::translate( $parent_theme_json_data, wp_get_theme()->parent()->get( 'TextDomain' ) );
				$parent_theme           = new WP_Theme_JSON_Gutenberg( $parent_theme_json_data );
				$theme->merge ($parent_theme);
			}

			if ( $content === 'all' || $content === 'current' ) {
	        		$theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json' ) );
	        		$theme_json_data = static::translate( $theme_json_data, wp_get_theme()->get( 'TextDomain' ) );
				$theme_theme     = new WP_Theme_JSON_Gutenberg( $theme_json_data );
 				$theme->merge( $theme_theme );
			}

	        	$theme->merge( static::get_user_data() );

			$data = MY_Theme_JSON_Resolver::flatten_theme_json($theme->get_raw_data(), null);

			// die(json_encode($data));

			return $data;

		}

	}
}

add_action( 'plugins_loaded', 'augment_gutenberg_with_utilities' );

function gutenberg_edit_site_get_theme_json_for_export( $theme ) {

	// For STANDALONE themes we want all of the user and theme settings (including current and parent)
	if ($theme['type'] == 'block') {
		return MY_Theme_JSON_Resolver::export_theme_data('all');
	}

	// For GRANDCHILDREN themes we want all of the CURRENT theme settings, the USER theme settings but NOT the PARENT settings
	// (since those will continue to be provided by the parent)
	// If the theme we are building from is a child theme then we are building a grandchild theme
	if ( is_child_theme() ) {
		return MY_Theme_JSON_Resolver::export_theme_data('current');
	}

	// For CHILD themes we only want the USER settings
	return MY_Theme_JSON_Resolver::export_theme_data('user');

}

function create_block_theme_get_theme_css( $theme ) {
	// NOTE: Themes that keep their CSS in a structure other than Blockbase's will need something different...

	// if we are building a STANDALONE theme we need the CURRENT theme's CSS OR ponyfill.css (if our theme is Blockbase)	
	// if we are building a GRANDCHILD theme we need the CURRENT theme's CSS
	if ($theme['type'] == 'block' || is_child_theme()) {
		if ( get_stylesheet() === 'blockbase' ) {
			//return Blockbase's /assets/ponyfill.css
			return file_get_contents(get_stylesheet_directory() . '/assets/ponyfill.css');
		} 
		else if (file_exists(get_stylesheet_directory() . '/assets/theme.css')) {
			//return the current theme's /assets/theme.css
			return file_get_contents(get_stylesheet_directory() . '/assets/theme.css');
		}
		// It's a child theme but there's no theme.css so I dunno what to do. :)
		return '';
	}

	// if we are building a CHILD theme we don't need any CSS
	return '';
}

function blockbase_get_style_css( $theme ) {
	$slug = $theme['slug'];
	$name = $theme['name'];
	$description = $theme['description'];
	$uri = $theme['uri'];
	$author = $theme['author'];
	$author_uri = $theme['author_uri'];
	$template = !create_blockbase_get_new_parent( $theme ) ? "" : "Template: ". create_blockbase_get_new_parent( $theme ) ."\n";

	return "/*\n" .
	"Theme Name: {$name}\n" .
	"Theme URI: {$uri}\n" .
	"Author: {$author}\n" .
	"Author URI: {$author_uri}\n" .
	"Description: {$description}\n" .
	"Requires at least: 5.8\n" .
	"Tested up to: 5.9\n" .
	"Requires PHP: 5.7\n" .
	"Version: 0.0.1\n" .
	"License: GNU General Public License v2 or later\n" .
	"License URI: https://raw.githubusercontent.com/Automattic/themes/trunk/LICENSE\n" .
	$template .
	"Text Domain: {$slug}\n" .
	"Tags: one-column, custom-colors, custom-menu, custom-logo, editor-style, featured-images, full-site-editing, rtl-language-support, theme-options, threaded-comments, translation-ready, wide-blocks\n" .
	"*/";
}

function blockbase_get_readme_txt( $theme ) {
	$slug = $theme['slug'];
	$name = $theme['name'];
	$description = $theme['description'];
	$uri = $theme['uri'];
	$author = $theme['author'];
	$author_uri = $theme['author_uri'];

	return "=== {$name} ===
Contributors: {$author}
Requires at least: 5.8
Tested up to: 5.9
Requires PHP: 5.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

{$description}

== Changelog ==

= 1.0.0 =
* Initial release

== Copyright ==

{$name} WordPress Theme, (C) 2021 {$author}
{$name} is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
";
}

/**
 * Creates an export of the current templates and
 * template parts from the site editor at the
 * specified path in a ZIP file.
 *
 * @param string $filename path of the ZIP file.
 */
function gutenberg_edit_site_export_theme_create_zip( $filename, $theme ) {

	$base_theme = wp_get_theme()->get('TextDomain');

	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'Zip Export not supported.' );
	}

	$zip = new ZipArchive();
	$zip->open( $filename, ZipArchive::OVERWRITE );
	$zip->addEmptyDir( $theme['slug'] );
	$zip->addEmptyDir( $theme['slug'] . '/templates' );
	$zip->addEmptyDir( $theme['slug'] . '/parts' );

	// Load templates into the zip file.
	$templates = gutenberg_get_block_templates();
	foreach ( $templates as $template ) {

		//Currently, when building against CHILD themes of Blockbase, block templates provided by Blockbase, not modified by the child theme or the user are included in the page. This is a bug.
		//if the theme is a child and the source is "theme" we don't want it
		if ($template->source === 'theme' && $theme['type'] === 'child') {
			continue;
		}

		// _remove_theme_attribute_in_block_template_content is provided by Gutenberg in the Site Editor's template export workflow.
		if ( function_exists( '_remove_theme_attribute_in_block_template_content' ) ) {
			$template->content = _remove_theme_attribute_in_block_template_content( $template->content );
		} else if ( function_exists( '_remove_theme_attribute_from_content' ) ) {
			$template->content = _remove_theme_attribute_from_content( $template->content );
		}
		$zip->addFromString(
			$theme['slug'] . '/templates/' . $template->slug . '.html',
			$template->content
		);
	}

	// Load template parts into the zip file.
	$template_parts = gutenberg_get_block_templates( array(), 'wp_template_part' );
	foreach ( $template_parts as $template_part ) {

		//Currently, when building against CHILD themes of Blockbase, block template parts provided by Blockbase, not modified by the child theme or the user are included in the page. This is a bug.
		//if the theme is a child and the source is "theme" we don't want it
		if ($template->source === 'theme' && $theme['type'] === 'child') {
			continue;
		}

		$zip->addFromString(
			$theme['slug'] . '/parts/' . $template_part->slug . '.html',
			$template_part->content
		);
	}

	// Add theme.json.

	$zip->addFromString(
		$theme['slug'] . '/theme.json',
		wp_json_encode( gutenberg_edit_site_get_theme_json_for_export( $theme ), JSON_PRETTY_PRINT )
	);

	// Add style.css.
	$zip->addFromString(
		$theme['slug'] . '/style.css',
		blockbase_get_style_css( $theme )
	);

	// Add theme.css combining all the current theme's css files.
	$zip->addFromString(
		$theme['slug'] . '/assets/theme.css',
		create_block_theme_get_theme_css( $theme )
	);

	// Add readme.txt.
	$zip->addFromString(
		$theme['slug'] . '/readme.txt',
		blockbase_get_readme_txt( $theme )
	);

	// Add screenshot.png.
	$zip->addFile(
		__DIR__ . '/screenshot.png',
		$theme['slug'] . '/screenshot.png'
	);

	//Standalone themes need an index.php file
	if( $theme['type'] === 'block' ) {
		$zip->addFromString(
			$theme['slug'] . '/index.php',
			'<?php //Silence is golden'
		);
	}

	// Save changes to the zip file.
	$zip->close();
}

/**
 * Output a ZIP file with an export of the current templates
 * and template parts from the site editor, and close the connection.
 */
function gutenberg_edit_site_export_theme( $theme ) {
	// Sanitize inputs.
	$theme['name'] = sanitize_text_field( $theme['name'] );
	$theme['description'] = sanitize_text_field( $theme['description'] );
	$theme['uri'] = sanitize_text_field( $theme['uri'] );
	$theme['author'] = sanitize_text_field( $theme['author'] );
	$theme['author_uri'] = sanitize_text_field( $theme['author_uri'] );
	$theme['type'] = sanitize_text_field( $theme['type'] );
	$theme['grandchild'] = filter_var( sanitize_text_field( $theme['grandchild'] ), FILTER_VALIDATE_BOOLEAN);
	$theme['slug'] = sanitize_title( $theme['name'] );
	// Create ZIP file in the temporary directory.
	$filename = tempnam( get_temp_dir(), $theme['slug'] );
	gutenberg_edit_site_export_theme_create_zip( $filename, $theme );

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
	header( 'Content-Length: ' . filesize( $filename ) );
	flush();
	echo readfile( $filename );
	die();
}

// In Gutenberg a similar route is called from the frontend to export template parts
// I've left this in although we aren't using it at the moment, as I think eventually this will become part of Gutenberg.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'__experimental/edit-site/v1',
			'/create-theme',
			array(
				'methods'             => 'GET',
				'callback'            => 'gutenberg_edit_site_export_theme',
				'permission_callback' => function () {
					return current_user_can( 'edit_theme_options' );
				},
			)
		);
	}
);

function create_blockbase_theme_page() {
	?>
		<div class="wrap">
<<<<<<< HEAD
			<h2><?php _e('Create Block Theme', 'create-block-theme'); ?></h2>
			<p><?php _e('Save your current block templates and theme.json settings as a new theme.', 'create-block-theme'); ?></p>
			<form method="get">
			<label><?php _e('Theme name', 'create-block-theme'); ?><br /><input placeholder="<?php _e('Blockbase', 'create-block-theme'); ?>" type="text" name="theme[name]" class="regular-text" required /></label><br /><br />
				<label><?php _e('Theme description', 'create-block-theme'); ?><br /><textarea placeholder="<?php _e('Blockbase is a simple theme that supports full-site editing. Use it to build something beautiful.', 'create-block-theme'); ?>" rows="4" cols="50" name="theme[description]" class="regular-text"></textarea></label><br /><br />
				<p>The new theme will be based on whatever theme you have active at the moment. <br />If you want a fresh start, try using <a href="https://github.com/WordPress/theme-experiments/tree/master/emptytheme">Empty Theme</a> as a base.</p>
				<label><input checked value="block" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Standalone theme', 'create-blockbase-theme'); ?></label><br /><br />
				<label><input value="child" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Child theme of the current active theme', 'create-blockbase-theme'); ?></label><br /><br />
				<label><input checked value="block" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Standalone theme', 'create-block-theme'); ?></label><br /><br />
				<label><input value="child" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Child theme (make sure the parent theme is currently active)', 'create-blockbase-theme'); ?></label><br /><br />
				<label><?php _e('Theme URI', 'create-block-theme'); ?><br /><input placeholder="https://github.com/automattic/themes/tree/trunk/blockbase" type="text" name="theme[uri]" class="regular-text code" /></label><br /><br />
				<label><?php _e('Author', 'create-block-theme'); ?><br /><input placeholder="<?php _e('Automattic', 'create-block-theme'); ?>" type="text" name="theme[author]" class="regular-text" /></label><br /><br />
				<label><?php _e('Author URI', 'create-block-theme'); ?><br /><input placeholder="<?php _e('https://automattic.com/', 'create-block-theme'); ?>" type="text" name="theme[author_uri]" class="regular-text code" /></label><br /><br />
				<input type="hidden" name="page" value="create-block-theme" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
				<input type="submit" value="<?php _e('Create block theme', 'create-block-theme'); ?>" class="button button-primary" />
=======
			<h2><?php _e('Create Block Theme', 'create-blockbase-theme'); ?></h2>
			<p><?php _e('Save your current block templates and theme.json settings as a new theme.', 'create-blockbase-theme'); ?></p>
			<p><?php wp_kses_post( _e( 'The new theme will be based on whatever theme you have active at the moment. <br />If you want a fresh start, try using <a href="https://github.com/WordPress/theme-experiments/tree/master/emptytheme">Empty Theme</a> as a base.', 'create-blockbase-theme' ) ); ?></p>
			<p><?php _e('The current active theme is:', 'create-blockbase-theme'); ?> <?php echo wp_get_theme()->get('Name'); ?></p>
			<form method="get">
				<label><?php _e('Theme name', 'create-blockbase-theme'); ?><br /><input placeholder="<?php _e('Blockbase', 'create-blockbase-theme'); ?>" type="text" name="theme[name]" class="regular-text" required /></label><br /><br />
				<label><?php _e('Theme description', 'create-blockbase-theme'); ?><br /><textarea placeholder="<?php _e('Blockbase is a simple theme that supports full-site editing. Use it to build something beautiful.', 'create-blockbase-theme'); ?>" rows="4" cols="50" name="theme[description]" class="regular-text"></textarea></label><br /><br />
				<?php if ( ! is_child_theme() ): ?>
				<label><input checked value="block" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Standalone theme', 'create-blockbase-theme'); ?></label><br /><br />
				<label><input value="child" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Child theme of the current active theme', 'create-blockbase-theme'); ?></label><br /><br />
				<?php else: ?>
				<input type="hidden" name="theme[type]" value="child" />
				<?php endif; ?>
				<label><?php _e('Theme URI', 'create-blockbase-theme'); ?><br /><input placeholder="https://github.com/automattic/themes/tree/trunk/blockbase" type="text" name="theme[uri]" class="regular-text code" /></label><br /><br />
				<label><?php _e('Author', 'create-blockbase-theme'); ?><br /><input placeholder="<?php _e('Automattic', 'create-blockbase-theme'); ?>" type="text" name="theme[author]" class="regular-text" /></label><br /><br />
				<label><?php _e('Author URI', 'create-blockbase-theme'); ?><br /><input placeholder="<?php _e('https://automattic.com/', 'create-blockbase-theme'); ?>" type="text" name="theme[author_uri]" class="regular-text code" /></label><br /><br />
				<input type="hidden" name="page" value="create-blockbase-theme" />
				<input type="hidden" name="theme[grandchild]" value="<?php echo is_child_theme(); ?>" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_blockbase_theme' ); ?>" />
				<input type="submit" value="<?php _e('Create Block theme', 'create-blockbase-theme'); ?>" class="button button-primary" />
>>>>>>> d5ea34f (hide options when active theme is a child theme)
			</form>
		</div>
	<?php
}
function blockbase_create_theme_menu() {
	$page_title=__('Create Block Theme', 'create-block-theme');
	$menu_title=__('Create Block Theme', 'create-block-theme');
	add_theme_page( $page_title, $menu_title, 'edit_theme_options', 'create-block-theme', 'create_blockbase_theme_page' );
}

add_action( 'admin_menu', 'blockbase_create_theme_menu' );

function blockbase_save_theme() {
	// I can't work out how to call the API but this works for now.
	if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'create-block-theme' && ! empty( $_GET['theme'] ) ) {

		// Check user capabilities.
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return add_action( 'admin_notices', 'create_blockbase_child_admin_notice_error' );
		}

		// Check nonce
		if ( ! wp_verify_nonce( $_GET['nonce'], 'create_block_theme' ) ) {
			var_dump('this>asdfs');
			return add_action( 'admin_notices', 'create_blockbase_child_admin_notice_error' );
		}

		if ( empty( $_GET['theme']['name'] ) ) {
			var_dump('this>23cc');
			return add_action( 'admin_notices', 'create_blockbase_child_admin_notice_error' );
		}

		if( $_GET['theme']['type'] === 'child' && !wp_is_block_theme() ) {
			return add_action( 'admin_notices', 'create_blockbase_child_admin_notice_error_wrong_parent' );
		}

		add_action( 'admin_notices', 'create_blockbase_child_admin_notice_success' );
		gutenberg_edit_site_export_theme( $_GET['theme'] );
	}
}
add_action( 'admin_init', 'blockbase_save_theme');

function create_blockbase_child_admin_notice_error_wrong_parent() {
	$class = 'notice notice-error';
	$message = __( 'You can only create a child theme from a Block theme. Please switch your theme to a Block theme.', 'create-blockbase-theme' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

function create_blockbase_child_admin_notice_error() {
	$class = 'notice notice-error';
	$message = __( 'Please specify a theme name.', 'create-block-theme' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

function create_blockbase_child_admin_notice_success() {
	?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e( 'New block theme created!', 'create-block-theme' ); ?></p>
		</div>
	<?php
}

function create_blockbase_get_new_parent( $theme ) {

	if( is_child_theme() ) {
		return wp_get_theme()->get( 'Template' );
	} elseif( $theme['type'] == 'child' ) {
		return wp_get_theme()->get( 'TextDomain' );
	}

	return false;
}