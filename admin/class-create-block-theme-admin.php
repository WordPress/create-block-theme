<?php

require_once (__DIR__ . '/resolver_additions.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Create_Block_Theme
 * @subpackage Create_Block_Theme/admin
 * @author     WordPress.org
 */
class Create_Block_Theme_Admin {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'create_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'blockbase_save_theme' ] );
	}

	function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}
		$page_title=_x('Create Block Theme', 'UI String', 'create-block-theme');
		$menu_title=_x('Create Block Theme', 'UI String', 'create-block-theme');
		add_theme_page( $page_title, $menu_title, 'edit_theme_options', 'create-block-theme', [ $this, 'create_admin_form_page' ] );

		add_action('admin_enqueue_scripts', [ $this, 'form_script' ] );
	}

	function save_theme_locally( $export_type ) {
		$this->add_templates_to_local( $export_type );
		$this->add_theme_json_to_local( $export_type );
	}

	function save_variation ( $export_type, $theme ) {
		$this->add_theme_json_variation_to_local( $export_type, $theme );
	}

	function clear_user_customizations() {

		// Clear all values in the user theme.json
		$user_custom_post_type_id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$global_styles_controller = new WP_REST_Global_Styles_Controller();
		$update_request = new WP_REST_Request( 'PUT', '/wp/v2/global-styles/' );
		$update_request->set_param( 'id', $user_custom_post_type_id );
		$update_request->set_param( 'settings', [] );
		$update_request->set_param( 'styles', [] );
		$updated_global_styles = $global_styles_controller->update_item( $update_request );
		delete_transient( 'global_styles' );
		delete_transient( 'global_styles_' . get_stylesheet() );
		delete_transient( 'gutenberg_global_styles' );
		delete_transient( 'gutenberg_global_styles_' . get_stylesheet() );

		//remove all user templates (they have been saved in the theme)
		$templates = get_block_templates();
		$template_parts = get_block_templates( array(), 'wp_template_part' );
		foreach ( $template_parts as $template ) {
			if ( $template->source !== 'custom' ) {
				continue;
			}
			wp_delete_post($template->wp_id, true);
		}

		foreach ( $templates as $template ) {
			if ( $template->source !== 'custom' ) {
				continue;
			}
			wp_delete_post($template->wp_id, true);
		}

	}

	/**
	 * Export activated child theme
	 */
	function export_child_theme( $theme ) {
		$theme['slug'] = wp_get_theme()->get( 'TextDomain' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip = $this->create_zip( $filename );

		$zip = $this->copy_theme_to_zip( $zip, null, null );
		$zip = $this->add_templates_to_zip( $zip, 'current', null );
		$zip = $this->add_theme_json_to_zip( $zip, 'current' );

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
		die();
	}

	/**
	 * Create a sibling theme of the activated theme
	 */
	function create_sibling_theme( $theme ) {
		// Sanitize inputs.
		$theme['name'] = sanitize_text_field( $theme['name'] );
		$theme['description'] = sanitize_text_field( $theme['description'] );
		$theme['uri'] = sanitize_text_field( $theme['uri'] );
		$theme['author'] = sanitize_text_field( $theme['author'] );
		$theme['author_uri'] = sanitize_text_field( $theme['author_uri'] );
		$theme['slug'] = $this->get_theme_slug( $theme['name'] );
		$theme['template'] = wp_get_theme()->get( 'Template' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip = $this->create_zip( $filename );

		$zip = $this->copy_theme_to_zip( $zip, $theme['slug'], $theme['name'] );
		$zip = $this->add_templates_to_zip( $zip, 'current', $theme['slug'] );
		$zip = $this->add_theme_json_to_zip( $zip, 'current' );

		// Add readme.txt.
		$zip->addFromString(
			'readme.txt',
			$this->build_readme_txt( $theme )
		);

		// Augment style.css
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		// Remove metadata from style.css file
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, "*/" ) + 2 ) );
		// Add new metadata
		$css_contents = $this->build_child_style_css( $theme ) . $css_contents;
		$zip->addFromString(
			'style.css',
			$css_contents
		);

		// Add screenshot.png.
		$zip->addFile(
			__DIR__ . '/../screenshot.png',
			'screenshot.png'
		);

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
		die();
	}

	/**
	 * Clone the activated theme to create a new theme
	 */
	function clone_theme( $theme ) {
		// Sanitize inputs.
		$theme['name'] = sanitize_text_field( $theme['name'] );
		$theme['description'] = sanitize_text_field( $theme['description'] );
		$theme['uri'] = sanitize_text_field( $theme['uri'] );
		$theme['author'] = sanitize_text_field( $theme['author'] );
		$theme['author_uri'] = sanitize_text_field( $theme['author_uri'] );
		$theme['slug'] = $this->get_theme_slug( $theme['name'] );
		$theme['template'] = wp_get_theme()->get( 'Template' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip = $this->create_zip( $filename );

		$zip = $this->copy_theme_to_zip( $zip, $theme['slug'], $theme['name']);

		$zip = $this->add_templates_to_zip( $zip, 'all', $theme['slug'] );
		$zip = $this->add_theme_json_to_zip( $zip, 'all' );

		// Add readme.txt.
		$zip->addFromString(
			'readme.txt',
			$this->build_readme_txt( $theme )
		);

		// Augment style.css
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		// Remove metadata from style.css file
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, "*/" ) + 2 ) );
		// Add new metadata
		$css_contents = $this->build_child_style_css( $theme ) . $css_contents;
		$zip->addFromString(
			'style.css',
			$css_contents
		);

		// Add screenshot.png.
		$zip->addFile(
			__DIR__ . '/../screenshot.png',
			'screenshot.png'
		);

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
		die();
	}
	/**
	 * Create a child theme of the activated theme
	 */
	function create_child_theme( $theme ) {
		// Sanitize inputs.
		$theme['name'] = sanitize_text_field( $theme['name'] );
		$theme['description'] = sanitize_text_field( $theme['description'] );
		$theme['uri'] = sanitize_text_field( $theme['uri'] );
		$theme['author'] = sanitize_text_field( $theme['author'] );
		$theme['author_uri'] = sanitize_text_field( $theme['author_uri'] );
		$theme['slug'] = $this->get_theme_slug( $theme['name'] );
		$theme['template'] = wp_get_theme()->get( 'TextDomain' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip = $this->create_zip( $filename );

		$zip = $this->add_templates_to_zip( $zip, 'user', null );
		$zip = $this->add_theme_json_to_zip( $zip, 'user' );

		// Add readme.txt.
		$zip->addFromString(
			'readme.txt',
			$this->build_readme_txt( $theme )
		);

		// Add style.css.
		$zip->addFromString(
			'style.css',
			$this->build_child_style_css( $theme )
		);

		// Add screenshot.png.
		$zip->addFile(
			__DIR__ . '/../screenshot.png',
			'screenshot.png'
		);

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
		die();
	}

	/**
	 * Export activated parent theme
	 */
	function export_theme( $theme ) {
		$theme['slug'] = wp_get_theme()->get( 'TextDomain' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip = $this->create_zip( $filename );

		$zip = $this->copy_theme_to_zip( $zip, null, null );
		$zip = $this->add_templates_to_zip( $zip, 'all', null );
		$zip = $this->add_theme_json_to_zip( $zip, 'all' );

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
		die();
	}

	function create_blank_theme( $theme ) {
		// Sanitize inputs.
		$theme['name'] = sanitize_text_field( $theme['name'] );
		$theme['description'] = sanitize_text_field( $theme['description'] );
		$theme['uri'] = sanitize_text_field( $theme['uri'] );
		$theme['author'] = sanitize_text_field( $theme['author'] );
		$theme['author_uri'] = sanitize_text_field( $theme['author_uri'] );
		$theme['template'] = '';
		$theme['slug'] = $this->get_theme_slug( $theme['name'] );

		// Create theme directory.
		$source = plugin_dir_path( __DIR__ ) . 'assets/boilerplate';
		$blank_theme_path = get_theme_root() . DIRECTORY_SEPARATOR . $theme['slug'];
		if ( ! file_exists( $blank_theme_path ) ) {
			mkdir( $blank_theme_path, 0755 );
			// Add readme.txt.
			file_put_contents( 
				$blank_theme_path . DIRECTORY_SEPARATOR . 'readme.txt', 
				$this->build_readme_txt( $theme )
			);

			// Add new metadata.
			$css_contents = $this->build_child_style_css( $theme );

			// Add style.css.
			file_put_contents( 
				$blank_theme_path . DIRECTORY_SEPARATOR . 'style.css', 
				$css_contents
			);

			foreach (
				$iterator = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS),
					\RecursiveIteratorIterator::SELF_FIRST) as $item
				) {
				if ($item->isDir()) {
					mkdir( $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
				} else {
					copy($item, $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
				}
			}

			if ( ! defined( 'IS_GUTENBERG_PLUGIN' ) ) {
				global $wp_version;
				$theme_json_version = 'wp/' . substr( $wp_version, 0, 3 );
    				$schema = '"$schema": "https://schemas.wp.org/' . $theme_json_version . '/theme.json"';
				$theme_json_path = $blank_theme_path . DIRECTORY_SEPARATOR . 'theme.json';
				$theme_json_string = file_get_contents( $theme_json_path );
				$theme_json_string = str_replace('"$schema": "https://schemas.wp.org/trunk/theme.json"', $schema, $theme_json_string );
				file_put_contents( $theme_json_path, $theme_json_string );
			}
		}

	}

	function add_theme_json_to_zip ( $zip, $export_type ) {
		$zip->addFromString(
			'theme.json',
			MY_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
		return $zip;
	}

	function add_theme_json_to_local ( $export_type ) {
		file_put_contents(
			get_stylesheet_directory() . '/theme.json',
			MY_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
	}

	function add_theme_json_variation_to_local ( $export_type, $theme ) {
		$variation_slug = sanitize_title( $theme['variation'] );
		$variation_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;
		$file_counter = 0;

		if ( ! file_exists( $variation_path ) ) {
			mkdir( $variation_path, 0755, true );
		}
		
		if ( file_exists( $variation_path . $variation_slug . '.json' ) ) {
			$file_counter++;
			while ( file_exists( $variation_path . $variation_slug . '_' . $file_counter . '.json' ) ) {
				$file_counter++;
		   	}
			$variation_slug = $variation_slug . '_' . $file_counter;
		}

		$_GET['theme']['variation_slug'] = $variation_slug;
		
		file_put_contents(
			$variation_path . $variation_slug . '.json',
			MY_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
	}

	function copy_theme_to_zip( $zip, $new_slug, $new_name ) {

		// Get real path for our folder
		$theme_path = get_stylesheet_directory();

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $theme_path ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		// Add all the files (except for templates)
		foreach ( $files as $name => $file ) {

			// Skip directories (they would be added automatically)
			if ( ! $file->isDir() ) {

				// Get real and relative path for current file
				$file_path = wp_normalize_path( $file );

				// If the path is for templates/parts ignore it
				if (
					strpos($file_path, 'block-template-parts/' ) ||
					strpos($file_path, 'block-templates/' ) ||
					strpos($file_path, 'templates/' ) ||
					strpos($file_path, 'parts/' )
				) {
					continue;
				}

				$relative_path = substr( $file_path, strlen( $theme_path ) + 1 );

				// Replace only text files, skip png's and other stuff.
				$valid_extensions = array( 'php', 'css', 'scss', 'js', 'txt', 'html' );
				$valid_extensions_regex = implode( '|', $valid_extensions );
				if ( ! preg_match( "/\.({$valid_extensions_regex})$/", $relative_path ) ) {
					$zip->addFile( $file_path, $relative_path );
				}

				else {
					$contents = file_get_contents( $file_path );

					// Replace namespace values if provided
					if ( $new_slug ) {
						$contents = $this->replace_namespace( $contents, $new_slug, $new_name );
					}

					// Add current file to archive
					$zip->addFromString( $relative_path, $contents );
				}

			}
		}

		return $zip;
	}

	function replace_namespace( $content, $new_slug, $new_name ) {

		$old_slug = wp_get_theme()->get( 'TextDomain' );
		$new_slug_underscore = str_replace( '-', '_', $new_slug );
		$old_slug_underscore = str_replace( '-', '_', $old_slug );
		$old_name = wp_get_theme()->get( 'Name' );

		$content = str_replace( $old_slug, $new_slug, $content );
		$content = str_replace( $old_slug_underscore, $new_slug_underscore, $content );
		$content = str_replace( $old_name, $new_name, $content );

		return $content;
	}

	function get_theme_slug( $new_theme_name ) {

		// If the source theme has a single-word slug but the new theme has a multi-word slug
		// then function will look like: function apple-bumpkin_support() and that won't work.
		// There are no issues if it is multi-word>single-word or multi>multi or single>single.
		// Due to the complexity of this situation (compared to the simplicity of the others)
		// this will enforce the usage of a singleword slug for those themes.

		$old_slug = wp_get_theme()->get( 'TextDomain' );
 		$new_slug = sanitize_title( $new_theme_name );

		if( ! str_contains( $old_slug , '-') && str_contains( $new_slug, '-' ) ) {
			return str_replace( '-', '', $new_slug );
		}

		return $new_slug;
	}

	/*
	 * Filter a template out (return false) based on the export_type expected and the templates origin.
	 * Templates not filtered out are modified based on the slug information provided and cleaned up
	 * to have the expected exported value.
	 */
	function filter_theme_template( $template, $export_type, $path, $old_slug, $new_slug ) {
		if ($template->source === 'theme' && $export_type === 'user') {
			return false;
		}
		if (
			$template->source === 'theme' &&
			$export_type === 'current' &&
			! file_exists( $path . $template->slug . '.html' )
		) {
			return false;
		}

		$template->content = _remove_theme_attribute_in_block_template_content( $template->content );

		// NOTE: Dashes are encoded as \u002d in the content that we get (noteably in things like css variables used in templates)
		// This replaces that with dashes again. We should consider decoding the entire string but that is proving difficult.
		$template->content = str_replace( '\u002d', '-', $template->content );

		if ( $new_slug ) {
			$template->content = str_replace( $old_slug, $new_slug, $template->content );
		}

		return $template;
	}

	/*
	 * Build a collection of templates and template-parts that should be exported (and modified) based on the given export_type and new slug
	 */
	function get_theme_templates( $export_type, $new_slug ) {

		$old_slug = wp_get_theme()->get( 'TextDomain' );
		$templates = get_block_templates();
		$template_parts = get_block_templates ( array(), 'wp_template_part' );
		$exported_templates = [];
		$exported_parts = [];

		// build collection of templates/parts in currently activated theme
		$templates_paths = get_block_theme_folders();
		$templates_path =  get_stylesheet_directory() . '/' . $templates_paths['wp_template'] . '/';
		$parts_path =  get_stylesheet_directory() . '/' . $templates_paths['wp_template_part'] . '/';

		foreach ( $templates as $template ) {
			$template = $this->filter_theme_template(
				$template,
				$export_type,
				$templates_path,
				$old_slug,
				$new_slug
			);
			if ( $template ) {
				$exported_templates[] = $template;
			}
		}

		foreach ( $template_parts as $template ) {
			$template = $this->filter_theme_template(
				$template,
				$export_type,
				$parts_path,
				$old_slug,
				$new_slug

			);
			if ( $template ) {
				$exported_parts[] = $template;
			}
		}

		return (object)[
			'templates'=>$exported_templates,
			'parts'=>$exported_parts
		];

	}

	/**
	 * Add block templates and parts to the zip.
	 *
	 * @since    0.0.2
	 * @param    object               $zip          The zip archive to add the templates to.
	 * @param    string               $export_type  Determine the templates that should be exported.
	 * 						current = templates from currently activated theme (but not a parent theme if there is one) as well as user edited templates
	 * 						user = only user edited templates
	 * 						all = all templates no matter what
	 */
	function add_templates_to_zip( $zip, $export_type, $new_slug ) {

		$theme_templates = $this->get_theme_templates( $export_type, $new_slug );

		if ( $theme_templates->templates ) {
			$zip->addEmptyDir( 'templates' );
		}

		if ( $theme_templates->parts ) {
			$zip->addEmptyDir( 'parts' );
		}

		foreach ( $theme_templates->templates as $template ) {
			$zip->addFromString(
				'templates/' . $template->slug . '.html',
				$template->content
			);
		}

		foreach ( $theme_templates->parts as $template_part ) {
			$zip->addFromString(
				'parts/' . $template_part->slug . '.html',
				$template_part->content
			);
		}

		return $zip;
	}

	function add_templates_to_local( $export_type ) {

		$theme_templates = $this->get_theme_templates( $export_type, null );
		$template_folders = get_block_theme_folders();

		// If there is no templates folder, create it.
		if ( ! is_dir( get_stylesheet_directory() . '/' . $template_folders['wp_template']  ) ) {
			wp_mkdir_p( get_stylesheet_directory() . '/' . $template_folders['wp_template'] );
		}

		foreach ( $theme_templates->templates as $template ) {
			file_put_contents(
				get_stylesheet_directory() . '/' . $template_folders['wp_template'] . '/' . $template->slug . '.html',
				$template->content
			);
		}

		// If there is no parts folder, create it.
		if ( ! is_dir( get_stylesheet_directory() . '/' . $template_folders['wp_template_part'] ) ) {
			wp_mkdir_p( get_stylesheet_directory() . '/' . $template_folders['wp_template_part'] );
		}

		foreach ( $theme_templates->parts as $template_part ) {
			file_put_contents(
				get_stylesheet_directory() . '/' . $template_folders['wp_template_part'] . '/' . $template_part->slug . '.html',
				$template_part->content
			);
		}
	}

	function create_zip( $filename ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'Zip Export not supported.' );
		}
		$zip = new ZipArchive();
		$zip->open( $filename, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		return $zip;
	}

	/**
	 * Build a readme.txt file for CHILD/GRANDCHILD themes.
	 */
	function build_readme_txt( $theme ) {
		$slug = $theme['slug'];
		$name = $theme['name'];
		$description = $theme['description'];
		$uri = $theme['uri'];
		$author = $theme['author'];
		$author_uri = $theme['author_uri'];
		$copyYear = date('Y');

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

= 0.0.1 =
* Initial release

== Copyright ==

{$name} WordPress Theme, (C) {$copyYear} {$author}
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
	 * Build a style.css file for CHILD/GRANDCHILD themes.
	 */
	function build_child_style_css( $theme ) {
		$slug = $theme['slug'];
		$name = $theme['name'];
		$description = $theme['description'];
		$uri = $theme['uri'];
		$author = $theme['author'];
		$author_uri = $theme['author_uri'];
		$template = $theme['template'];
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
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Template: {$template}
Text Domain: {$slug}
Tags: one-column, custom-colors, custom-menu, custom-logo, editor-style, featured-images, full-site-editing, rtl-language-support, theme-options, threaded-comments, translation-ready, wide-blocks
*/";
	}

	function create_admin_form_page() {
		if ( ! wp_is_block_theme() ) {
			?>
			<div class="wrap">
				<h2><?php _ex('Create Block Theme', 'UI String', 'create-block-theme'); ?></h2>
				<p><?php _e('Activate a block theme to use this tool.', 'create-block-theme'); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap">
			<h2><?php _ex('Create Block Theme', 'UI String', 'create-block-theme'); ?></h2>
			<form method="get">
				<div id="col-container">
					<div id="col-left">
						<div class="col-wrap">
							<p><?php printf( esc_html__('Export your current block theme (%1$s) with changes you made to Templates, Template Parts and Global Styles.', 'create-block-theme'),  esc_html( wp_get_theme()->get('Name') ) ); ?></p>

							<label>
								<input checked value="export" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', true );toggleForm( 'new_variation_metadata_form', true );" />
								<?php _e('Export ', 'create-block-theme'); echo wp_get_theme()->get('Name'); ?><br />
								<?php _e('[Export the activated theme with user changes]', 'create-block-theme'); ?>
							</label>
							<br /><br />
							<?php if ( is_child_theme() ): ?>
								<label>
									<input value="sibling" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );"/>
									<?php _e('Create sibling of ', 'create-block-theme'); echo wp_get_theme()->get('Name'); ?>
								</label>
								<br />
								<?php _e('[Create a new theme cloning the activated child theme.  The parent theme will be the same as the parent of the currently activated theme. The resulting theme will have all of the assets of the activated theme, none of the assets provided by the parent theme, as well as user changes.]', 'create-block-theme'); ?>
								<p><b><?php _e('NOTE: Sibling themes created from this theme will have the original namespacing. This should be changed manually once the theme has been created.', 'create-block-theme'); ?></b></p>
								<br />
							<?php else: ?>
								<label>
									<input value="child" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );"/>
									<?php _e('Create child of ', 'create-block-theme'); echo wp_get_theme()->get('Name'); ?>
								</label>
								<br />
								<?php _e('[Create a new child theme. The currently activated theme will be the parent theme.]', 'create-block-theme'); ?>
								<br /><br />
								<label>
									<input value="clone" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );"/>
									<?php _e('Clone ', 'create-block-theme'); echo wp_get_theme()->get('Name'); ?><br />
									<?php _e('[Create a new theme cloning the activated theme. The resulting theme will have all of the assets of the activated theme as well as user changes.]', 'create-block-theme'); ?>
								</label>
								<br /><br />
							<?php endif; ?>
							<label>
								<input value="save" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', true );toggleForm( 'new_variation_metadata_form', true );" />
								<?php _e('Overwrite ', 'create-block-theme'); echo wp_get_theme()->get('Name'); ?><br />
								<?php _e('[Save USER changes as THEME changes and delete the USER changes.  Your changes will be saved in the theme on the folder.]', 'create-block-theme'); ?>
							</label>
							<br /><br />
							<label>
								<input value="blank" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );" />
								<?php _e('Create blank theme ', 'create-block-theme'); ?><br />
								<?php _e('[Generate a boilerplate "empty" theme inside of this site\'s themes directory.]', 'create-block-theme'); ?>
							</label>
							<br /><br />
							<label>
								<input value="variation" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_variation_metadata_form', false );" />
								<?php _e('Create a style variation ', 'create-block-theme'); ?><br />
								<?php printf( esc_html__('[Save user changes as a style variation of %1$s.]', 'create-block-theme'),  esc_html( wp_get_theme()->get('Name') ) ); ?>
							</label>
							<br /><br />

							<input type="submit" value="<?php _e('Generate', 'create-block-theme'); ?>" class="button button-primary" />

						</div>
					</div>
					<div id="col-right">
						<div class="col-wrap">
							<div hidden id="new_variation_metadata_form">
								<label>
									<?php _e('Variation Name (*):', 'create-block-theme'); ?><br />
									<input placeholder="<?php _e('Variation Name', 'create-block-theme'); ?>" type="text" name="theme[variation]" class="large-text" />
								</label>
							</div>
							<div hidden id="new_theme_metadata_form">
								<label>
									<?php _e('Theme Name (*):', 'create-block-theme'); ?><br />
									<input placeholder="<?php _e('Theme Name', 'create-block-theme'); ?>" type="text" name="theme[name]" class="large-text" />
								</label>
								<br /><br />
								<label>
									<?php _e('Theme Description:', 'create-block-theme'); ?><br />
									<textarea placeholder="<?php _e('A short description of the theme.', 'create-block-theme'); ?>" rows="4" cols="50" name="theme[description]" class="large-text"></textarea>
								</label>
								<br /><br />
								<label>
									<?php _e('Theme URI:', 'create-block-theme'); ?><br />
									<small><?php _e('The URL of a public web page where users can find more information about the theme.', 'create-block-theme'); ?></small><br />
									<input placeholder="<?php _e('https://github.com/wordpress/twentytwentytwo/', 'create-block-theme'); ?>" type="text" name="theme[uri]" class="large-text code" />
								</label>
								<br /><br />
								<label>
									<?php _e('Author:', 'create-block-theme'); ?><br />
									<small><?php _e('The name of the individual or organization who developed the theme.', 'create-block-theme'); ?></small><br />
									<input placeholder="<?php _e('the WordPress team', 'create-block-theme'); ?>" type="text" name="theme[author]" class="large-text" />
								</label>
								<br /><br />
								<label>
									<?php _e('Author URI:', 'create-block-theme'); ?><br />
									<small><?php _e('The URL of the authoring individual or organization.', 'create-block-theme'); ?></small><br />
									<input placeholder="<?php _e('https://wordpress.org/', 'create-block-theme'); ?>" type="text" name="theme[author_uri]" class="large-text code" />
								</label><br />
								<p><?php _e('Items indicated with (*) are required.', 'create-block-theme'); ?></p><br />
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

	function form_script() {
		wp_enqueue_script('form-script', plugin_dir_url(__FILE__) . '/js/form-script.js');
	}

	function blockbase_save_theme() {

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'create-block-theme' && ! empty( $_GET['theme'] ) ) {

			// Check user capabilities.
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
			}

			// Check nonce
			if ( ! wp_verify_nonce( $_GET['nonce'], 'create_block_theme' ) ) {
				return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
			}

			if ( $_GET['theme']['type'] === 'save' ) {
				// Avoid running if WordPress dosn't have permission to overwrite the theme folder
				if ( ! is_writable( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_file_permissions' ] );
				}

				if ( is_child_theme() ) {
					$this->save_theme_locally( 'current' );
				}
				else {
					$this->save_theme_locally( 'all' );
				}
				$this->clear_user_customizations();

				add_action( 'admin_notices', [ $this, 'admin_notice_save_success' ] );
			}

			else if ( $_GET['theme']['type'] === 'variation' ) {

				if ( $_GET['theme']['variation'] === '' ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_variation_name' ] );
				}

				// Avoid running if WordPress dosn't have permission to write the theme folder
				if ( ! is_writable ( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_file_permissions' ] );
				}

				if ( is_child_theme() ) {
					$this->save_variation( 'current', $_GET['theme'] );
				}
				else {
					$this->save_variation( 'all', $_GET['theme'] );
				}
				$this->clear_user_customizations();

				add_action( 'admin_notices', [ $this, 'admin_notice_variation_success' ] );
			}

			else if ( $_GET['theme']['type'] === 'blank' ) {
				// Avoid running if WordPress dosn't have permission to write the themes folder
				if ( ! is_writable ( get_theme_root() ) ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_themes_file_permissions' ] );
				}

				if ( $_GET['theme']['name'] === '' ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
				}
				$this->create_blank_theme( $_GET['theme'] );

				add_action( 'admin_notices', [ $this, 'admin_notice_blank_success' ] );
			}

			else if ( is_child_theme() ) {
				if ( $_GET['theme']['type'] === 'sibling' ) {
					if ( $_GET['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
					}
					$this->create_sibling_theme( $_GET['theme'] );
				}
				else {
					$this->export_child_theme( $_GET['theme'] );
				}
				add_action( 'admin_notices', [ $this, 'admin_notice_export_success' ] );
			} else {
				if( $_GET['theme']['type'] === 'child' ) {
					if ( $_GET['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
					}
					$this->create_child_theme( $_GET['theme'] );
				}
				else if( $_GET['theme']['type'] === 'clone' ) {
					if ( $_GET['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
					}
					$this->clone_theme( $_GET['theme'] );
				}
				else {
					$this->export_theme( $_GET['theme'] );
				}
				add_action( 'admin_notices', [ $this, 'admin_notice_export_success' ] );
			}

		}
	}

	function admin_notice_error_theme_name() {
		$class = 'notice notice-error';
		$message = __( 'Please specify a theme name.', 'create-block-theme' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	function admin_notice_error_variation_name() {
		$class = 'notice notice-error';
		$message = __( 'Please specify a variation name.', 'create-block-theme' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	function admin_notice_export_success() {
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'Block theme exported successfully!', 'create-block-theme' ); ?></p>
			</div>
		<?php
	}

	function admin_notice_save_success() {
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'Block theme saved and user customizations cleared!', 'create-block-theme' ); ?></p>
			</div>
		<?php
	}

	function admin_notice_blank_success() {
		$theme_name = $_GET['theme']['name'];

		?>
			<div class="notice notice-success is-dismissible">
				<p><?php printf( esc_html__( 'Blank theme created, head over to Appearance > Themes to activate %1$s', 'create-block-theme' ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

	function admin_notice_variation_success() {
		$theme_name = wp_get_theme()->get( 'Name' );
		$variation_name = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . $_GET['theme']['variation_slug'] .'.json';

		?>
			<div class="notice notice-success is-dismissible">
				<p><?php printf( esc_html__( 'Your variation of %1$s has been created successfully. The new variation file is in %2$s', 'create-block-theme' ), esc_html( $theme_name ) , esc_html( $variation_name )  ); ?></p>
			</div>
		<?php
	}

	function admin_notice_error_theme_file_permissions () {
		$theme_name = wp_get_theme()->get( 'Name' );
		$theme_dir = get_stylesheet_directory();
		?>
			<div class="notice notice-error">
				<p><?php printf( esc_html__( 'Your theme ( %1$s ) directory ( %2$s ) is not writable. Please check your file permissions.', 'create-block-theme' ), esc_html( $theme_name ) , esc_html( $theme_dir )  ); ?></p>
			</div>
		<?php
	}

	function admin_notice_error_themes_file_permissions () {
		$themes_dir = get_theme_root();
		?>
			<div class="notice notice-error">
				<p><?php printf( esc_html__( 'Your themes directory ( %1$s ) is not writable. Please check your file permissions.', 'create-block-theme' ), esc_html( $themes_dir )  ); ?></p>
			</div>
		<?php
	}

}
