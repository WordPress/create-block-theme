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

require_once (__DIR__ . '/gutenberg_additions.php');

function create_block_theme_get_theme_json_for_export( $theme ) {

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

function blockbase_get_style_css( $theme ) {
	$slug = $theme['slug'];
	$name = $theme['name'];
	$description = $theme['description'];
	$uri = $theme['uri'];
	$author = $theme['author'];
	$author_uri = $theme['author_uri'];
	$template = !create_block_theme_get_new_parent( $theme ) ? "" : "\nTemplate: " . create_block_theme_get_new_parent( $theme ) ."\n";
	return "/*
Theme Name: {$name}
Theme URI: {$uri}
Author: {$author}
Author URI: {$author_uri}
Description: {$description}
Requires at least: 5.8
Tested up to: 5.9
Requires PHP: 5.7
Version: 0.0.1
License: GNU General Public License v2 or later
License URI: https://raw.githubusercontent.com/Automattic/themes/trunk/LICENSE" . 
$template .
"Text Domain: {$slug}
Tags: one-column, custom-colors, custom-menu, custom-logo, editor-style, featured-images, full-site-editing, rtl-language-support, theme-options, threaded-comments, translation-ready, wide-blocks
*/";
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
 * Build the CSS that a generated theme will include.
 * When building a STANDALONE theme (from a parent theme) the CURRENT (parent) theme's CSS is included.
 * When building a GRANDCHILD theme the CURRENT (child) theme's CSS is included.
 * When building a CHILD theme no extra CSS is included.
 */
function create_block_theme_get_theme_css( $theme ) {

	// if we are building a CHILD theme we don't need any CSS
	if ( $theme['type'] === 'child' && !$theme['template'] ) {
		return '';
	}

	$css_string = '';

	$current_theme = wp_get_theme();

	if ( $current_theme->exists() ){
		foreach ($current_theme->get_files('css', -1) as $key => $value) {
			if (strpos($key, '.css') !== false && file_exists( $value ) ) {

				$css_contents = file_get_contents( $value );

				if ($key === "style.css") {
					// Remove metadata from style.css file
					$css_contents = trim( substr( $css_contents, strpos( $css_contents, "*/" ) + 2 ) );
				}

				// If there is nothing but metadata in the style.css file don't include it.
				if ( strlen($css_contents) === 0 ) {
					continue;
				}

				$css_string .= "

/*
*
* Styles from " . $current_theme->get_stylesheet() . "/" . $key . "
*
*/

";
				$css_string .= $css_contents;
			}
		}
	}

	return $css_string;
}

/**
 * Standalone themes need a little extra logic to:
 *  - Load the theme.css
 *  - Load fonts from theme.json
 * This is that logic.
 */
function create_block_theme_get_functions( $theme ) {

	if ($theme['type'] !== 'block') {
		return null;
	}
	return '<?php

// TODO: Add Theme Supports that are not yet represented in theme.json

// TODO: No patterns are copied (or loaded) and we may want to consider that.

// Add Editor Styles
function '.$theme["slug"].'_editor_styles() {
	// Add the child theme CSS if it exists.
	if ( file_exists( get_stylesheet_directory() . "/assets/theme.css" ) ) {
		add_editor_style(
			"/assets/theme.css"
		);
	}
	add_editor_style(
		array(
			'.$theme["slug"].'_get_fonts_url(),
		)
	);
}
add_action( "admin_init", "'.$theme['slug'].'_editor_styles" );

// Add View Styles
function '.$theme['slug'].'_scripts() {
	wp_enqueue_style( "'.$theme["slug"].'-fonts", '.$theme["slug"].'_get_fonts_url(), array(), null );
	// Add the theme CSS if it exists.
	if ( file_exists( get_stylesheet_directory() . "/assets/theme.css" ) ) {
		wp_enqueue_style( "'.$theme['slug'].'-styles", get_stylesheet_directory_uri() . "/assets/theme.css", array(), wp_get_theme()->get( "Version" ) );
	}
}
add_action( "wp_enqueue_scripts", "'.$theme['slug'].'_scripts" );

function '.$theme['slug'].'_get_fonts_url() {

	$font_families = [];

	if ( ! class_exists( "WP_Theme_JSON_Resolver_Gutenberg" ) ) {
		return "";
	}

	$theme_data = WP_Theme_JSON_Resolver_Gutenberg::get_merged_data()->get_settings();
	if ( empty( $theme_data ) || empty( $theme_data["typography"] ) || empty( $theme_data["typography"]["fontFamilies"] ) ) {
		return "";
	}

	
	if ( ! empty( $theme_data["typography"]["fontFamilies"]["theme"] ) ) {
		foreach( $theme_data["typography"]["fontFamilies"]["theme"] as $font ) {
			if ( ! empty( $font["google"] ) ) {
				$font_families[] = $font["google"];
			}
		}
	}

	if ( empty( $font_families ) ) {
		return "";
	}

	// Make a single request for the theme or user fonts.
	return esc_url_raw( "https://fonts.googleapis.com/css2?" . implode( "&", array_unique( $font_families ) ) . "&display=swap" );
}	
 	';
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
		//if the theme we are building is a child and the source of the template is "theme" we don't want to include it
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
		//if the theme we are building is a child and the source of the template is "theme" we don't want to include it
		if ($template_part->source === 'theme' && $theme['type'] === 'child') {
			continue;
		}

		// _remove_theme_attribute_in_block_template_content is provided by Gutenberg in the Site Editor's template export workflow.
		if ( function_exists( '_remove_theme_attribute_in_block_template_content' ) ) {
			$template_part->content = _remove_theme_attribute_in_block_template_content( $template_part->content );
		} else if ( function_exists( '_remove_theme_attribute_from_content' ) ) {
			$template_part->content = _remove_theme_attribute_from_content( $template_part->content );
		}
	
		$zip->addFromString(
			$theme['slug'] . '/parts/' . $template_part->slug . '.html',
			$template_part->content
		);
	}

	// Add theme.json.
	$zip->addFromString(
		$theme['slug'] . '/theme.json',
		wp_json_encode( create_block_theme_get_theme_json_for_export( $theme ), JSON_PRETTY_PRINT )
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

	//Standalone themes need an index.php file and functions.php
	if( $theme['type'] === 'block' ) {
		$zip->addFromString(
			$theme['slug'] . '/index.php',
			'<?php //Silence is golden'
		);
		$zip->addFromString(
			$theme['slug'] . '/functions.php',
			create_block_theme_get_functions( $theme )
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
			<h2><?php _e('Create Block Theme', 'create-block-theme'); ?></h2>
			<p><?php _e('Save your current block templates and theme.json settings as a new theme.', 'create-block-theme'); ?></p>
			<p><?php wp_kses_post( _e( 'The new theme will be based on whatever theme you have active at the moment. <br />If you want a fresh start, try using <a href="https://github.com/WordPress/theme-experiments/tree/master/emptytheme">Empty Theme</a> as a base.', 'create-block-theme' ) ); ?></p>
			<p><?php _e('The current active theme is:', 'create-block-theme'); ?> <?php echo wp_get_theme()->get('Name'); ?></p>
			<form method="get">
				<label><?php _e('Theme Name', 'create-block-theme'); ?><br /><input required placeholder="<?php echo wp_get_theme()->get('Name'); ?>" type="text" name="theme[name]" class="regular-text" required /></label><br /><br />
				<label><?php _e('Theme Description', 'create-block-theme'); ?><br /><textarea placeholder="<?php echo wp_get_theme()->get('Description'); ?>" rows="4" cols="50" name="theme[description]" class="regular-text"></textarea></label><br /><br />
				<?php if ( ! is_child_theme() ): ?>
				<label><input checked value="child" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Child theme of the current active theme', 'create-block-theme'); ?></label><br /><br />
				<label><input value="block" type="radio" name="theme[type]" class="regular-text code" /><?php _e('Standalone theme', 'create-block-theme'); ?></label><br /><br />
				<?php else: ?>
				<input type="hidden" name="theme[type]" value="child" />
				<input type="hidden" name="theme[template]" value="<?php echo wp_get_theme()->get('Template'); ?>" />
				<?php endif; ?>
				<label><?php _e('Theme URI', 'create-block-theme'); ?><br /><input placeholder="<?php echo wp_get_theme()->get('ThemeURI'); ?>" type="text" name="theme[uri]" class="regular-text code" /></label><br /><br />
				<label><?php _e('Author', 'create-block-theme'); ?><br /><input placeholder="<?php echo wp_get_theme()->get('Author'); ?>" type="text" name="theme[author]" class="regular-text" /></label><br /><br />
				<label><?php _e('Author URI', 'create-block-theme'); ?><br /><input placeholder="<?php echo wp_get_theme()->get('AuthorURI'); ?>" type="text" name="theme[author_uri]" class="regular-text code" /></label><br /><br />
				<input type="hidden" name="page" value="create-block-theme" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
				<input type="submit" value="<?php _e('Create block theme', 'create-block-theme'); ?>" class="button button-primary" />
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
			return add_action( 'admin_notices', 'create_blockbase_child_admin_notice_error' );
		}

		if ( empty( $_GET['theme']['name'] ) ) {
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

function create_blockbase_child_admin_notice_error_wrong_theme() {
	$class = 'notice notice-error';
	$message = __( 'You can only create a child theme from a Block theme. Please switch your theme to a Block theme.', 'create-block-theme' );

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

function create_block_theme_get_new_parent( $theme ) {


	if( is_child_theme() ) {
		return wp_get_theme()->get( 'Template' );
	} elseif( $theme['type'] == 'child' ) {
		return wp_get_theme()->get( 'TextDomain' );
	}

	return false;
}