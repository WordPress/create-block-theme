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
	// NOTE: We aren't yet exporting 'standalone' themes but this is how it would be exported
	// if ($theme['type'] == 'block') {
	// 	return MY_Theme_JSON_Resolver::export_theme_data('all');
	// }

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
License URI: https://raw.githubusercontent.com/Automattic/themes/trunk/LICENSE
Template: blockbase
Text Domain: {$slug}
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
 * When building a STANDALONE theme from Blockbase the ponyfill.css will be included.
 * When building a GRANDCHILD theme the CURRENT theme's CSS is included.
 * When building a CHILD theme no extra CSS is included.
 */
function create_block_theme_get_theme_css( $theme ) {

	// if we are building a CHILD theme we don't need any CSS
	if ( $theme['type'] === 'child' ) {
		return '';
	}

	$css_string = '';

	$current_theme = wp_get_theme( );
	if ( $current_theme->exists() && $current_theme->get( 'TextDomain' ) !== 'blockbase' ){
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

		//if the theme is blockbase and the source is "theme" we don't want it
		if ($template->source === 'theme' && strpos($template->theme, 'blockbase') !== false) {
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
		//if the theme is blockbase and the source is "theme" we don't want it
		if ($template_part->source === 'theme' && strpos($template_part->theme, 'blockbase') !== false) {
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
			<form method="get">
				<label><?php _e('Theme name', 'create-block-theme'); ?><br /><input placeholder="<?php _e('Blockbase', 'create-block-theme'); ?>" type="text" name="theme[name]" class="regular-text" required /></label><br /><br />
				<label><?php _e('Theme description', 'create-block-theme'); ?><br /><textarea placeholder="<?php _e('Blockbase is a simple theme that supports full-site editing. Use it to build something beautiful.', 'create-block-theme'); ?>" rows="4" cols="50" name="theme[description]" class="regular-text"></textarea></label><br /><br />
				<label><?php _e('Theme URI', 'create-block-theme'); ?><br /><input placeholder="https://github.com/automattic/themes/tree/trunk/blockbase" type="text" name="theme[uri]" class="regular-text code" /></label><br /><br />
				<label><?php _e('Author', 'create-block-theme'); ?><br /><input placeholder="<?php _e('Automattic', 'create-block-theme'); ?>" type="text" name="theme[author]" class="regular-text" /></label><br /><br />
				<label><?php _e('Author URI', 'create-block-theme'); ?><br /><input placeholder="<?php _e('https://automattic.com/', 'create-block-theme'); ?>" type="text" name="theme[author_uri]" class="regular-text code" /></label><br /><br />
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

		add_action( 'admin_notices', 'create_blockbase_child_admin_notice_success' );
		gutenberg_edit_site_export_theme( $_GET['theme'] );
	}
}
add_action( 'admin_init', 'blockbase_save_theme');

function create_blockbase_child_admin_notice_error_wrong_theme() {
	$class = 'notice notice-error';
	$message = __( 'You can only create a Blockbase child theme from Blockbase. Please switch your theme to Blockbase.', 'create-block-theme' );

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
