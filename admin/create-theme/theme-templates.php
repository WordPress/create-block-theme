<?php

require_once( __DIR__ . '/theme-media.php' );
require_once( __DIR__ . '/theme-patterns.php' );
require_once( __DIR__ . '/theme-blocks.php' );

class Theme_Templates {
	/*
	 * Build a collection of templates and template-parts that should be exported (and modified) based on the given export_type and new slug
	 */
	public static function get_theme_templates( $export_type ) {
		$templates          = get_block_templates();
		$template_parts     = get_block_templates( array(), 'wp_template_part' );
		$exported_templates = array();
		$exported_parts     = array();

		// build collection of templates/parts in currently activated theme
		$templates_paths = get_block_theme_folders();
		$templates_path  = get_stylesheet_directory() . '/' . $templates_paths['wp_template'] . '/';
		$parts_path      = get_stylesheet_directory() . '/' . $templates_paths['wp_template_part'] . '/';

		foreach ( $templates as $template ) {
			$template = self::filter_theme_template(
				$template,
				$export_type,
				$templates_path
			);
			if ( $template ) {
				$exported_templates[] = $template;
			}
		}

		foreach ( $template_parts as $template ) {
			$template = self::filter_theme_template(
				$template,
				$export_type,
				$parts_path
			);
			if ( $template ) {
				$exported_parts[] = $template;
			}
		}

		return (object) array(
			'templates' => $exported_templates,
			'parts'     => $exported_parts,
		);

	}

	/*
	 * Filter a template out (return false) based on the export_type expected and the templates origin.
	 * Templates not filtered out are modified based on the slug information provided and cleaned up
	 * to have the expected exported value.
	 */
	static function filter_theme_template( $template, $export_type, $path ) {
		if ( 'theme' === $template->source && 'user' === $export_type ) {
			return false;
		}
		if (
			'theme' === $template->source &&
			'current' === $export_type &&
			! file_exists( $path . $template->slug . '.html' )
		) {
			return false;
		}

		// NOTE: Dashes are encoded as \u002d in the content that we get (noteably in things like css variables used in templates)
		// This replaces that with dashes again. We should consider decoding the entire string but that is proving difficult.
		$template->content = str_replace( '\u002d', '-', $template->content );

		// NOTE: Templates that reference template parts are exported with the 'theme' attribute.
		// This is undesirable and should be removed.
		$template->content = str_replace( ',"theme":"' . get_stylesheet() . '"', '', $template->content );

		return $template;
	}

	public static function replace_template_namespace( $template, $new_slug ) {
		$old_slug = wp_get_theme()->get( 'TextDomain' );
		if ( $new_slug ) {
			$template->content = str_replace( $old_slug, $new_slug, $template->content );
		}
		return $template;
	}

	public static function clear_user_templates_customizations() {
		//remove all user templates (they have been saved in the theme)
		$templates      = get_block_templates();
		$template_parts = get_block_templates( array(), 'wp_template_part' );
		foreach ( $template_parts as $template ) {
			if ( 'custom' !== $template->source ) {
				continue;
			}
			wp_delete_post( $template->wp_id, true );
		}

		foreach ( $templates as $template ) {
			if ( 'custom' !== $template->source ) {
				continue;
			}
			wp_delete_post( $template->wp_id, true );
		}
	}

	public static function add_templates_to_local( $export_type ) {

		$theme_templates  = self::get_theme_templates( $export_type );
		$template_folders = get_block_theme_folders();

		// If there is no templates folder, create it.
		if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template'] ) ) {
			wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template'] );
		}

		foreach ( $theme_templates->templates as $template ) {
			$template_data = Theme_Blocks::make_template_images_local( $template );

			// If there are images in the template, add it as a pattern
			if ( ! empty( $template_data->media ) ) {
				// If there is no templates folder, create it.
				if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' ) ) {
					wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' );
				}

				// If there are external images, add it as a pattern
				$pattern                 = Theme_Patterns::pattern_from_template( $template_data );
				$pattern_link_attributes = array(
					'slug' => $pattern['slug'],
				);
				$template_data->content  = Theme_Patterns::create_pattern_link( $pattern_link_attributes );

				// Write the pattern
				file_put_contents(
					get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . $template_data->slug . '.php',
					$pattern['content']
				);
			}

			// Write the template content
			file_put_contents(
				get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template'] . DIRECTORY_SEPARATOR . $template->slug . '.html',
				$template_data->content
			);

			// Write the media assets
			Theme_Media::add_media_to_local( $template_data->media );

		}

		// If there is no parts folder, create it.
		if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] ) ) {
			wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] );
		}

		foreach ( $theme_templates->parts as $template_part ) {
			$template_data = Theme_Blocks::make_template_images_local( $template_part );

			// If there are images in the template, add it as a pattern
			if ( ! empty( $template_data->media ) ) {
				// If there is no templates folder, create it.
				if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' ) ) {
					wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' );
				}

				// If there are external images, add it as a pattern
				$pattern                 = Theme_Patterns::pattern_from_template( $template_data );
				$pattern_link_attributes = array(
					'slug' => $pattern['slug'],
				);
				$template_data->content  = Theme_Patterns::create_pattern_link( $pattern_link_attributes );

				// Write the pattern
				file_put_contents(
					get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . $template_data->slug . '.php',
					$pattern['content']
				);
			}

			// Write the template content
			file_put_contents(
				get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] . DIRECTORY_SEPARATOR . $template_data->slug . '.html',
				$template_data->content
			);

			// Write the media assets
			Theme_Media::add_media_to_local( $template_data->media );
		}
	}
}
