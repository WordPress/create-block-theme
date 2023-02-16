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

	function clear_user_styles_customizations(){
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
	}

	function clear_user_templates_customizations() {
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
	function create_sibling_theme( $theme, $screenshot ) {
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

		// Add / replace screenshot.
		if ( $this->is_valid_screenshot( $screenshot ) ){
			$zip->addFile(
				$screenshot['tmp_name'],
				'screenshot.png'
			);
		}

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
	function clone_theme( $theme, $screenshot ) {
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

		// Add / replace screenshot.
		if ( $this->is_valid_screenshot( $screenshot ) ){
			$zip->addFile(
				$screenshot['tmp_name'],
				'screenshot.png'
			);
		}

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
	function create_child_theme( $theme, $screenshot ) {
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
		
		// Add / replace screenshot.
		if ( $this->is_valid_screenshot( $screenshot ) ){
			$zip->addFile(
				$screenshot['tmp_name'],
				'screenshot.png'
			);
		}

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

	function create_blank_theme( $theme, $screenshot ) {
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

			// Add screenshot.
			if ( $this->is_valid_screenshot( $screenshot ) ){
				$zip->addFile(
					$screenshot['tmp_name'],
					'screenshot.png'
				);
			}

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

		$_POST['theme']['variation_slug'] = $variation_slug;
		
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

	function is_absolute_url( $url ) {
		return ! empty( $url ) &&  isset( parse_url( $url )[ 'host' ] );
	}

	function make_relative_media_url ( $absolute_url ) {
		if ( ! empty ( $absolute_url ) && $this->is_absolute_url( $absolute_url ) ) {
			$folder_path = $this->get_media_folder_path_from_url( $absolute_url );
			return '<?php echo esc_url( get_stylesheet_directory_uri() ); ?>' . $folder_path . basename( $absolute_url );
		}
		return $absolute_url;
	}

	function make_html_media_local ( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		// If the WP_HTML_Tag_Processor class exists, use it to parse the HTML.
		// This API was recently in Gutenberg and not yet available in WordPress core. https://github.com/WordPress/gutenberg/pull/42485
		// If it's not available, fallb ack to DOMDocument which can not be installed in all the systems and has some issues.
		// When WP_HTML_Tag_Processor is availabe in core (6.2) we can remove the DOMDocument fallback.
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$html = new WP_HTML_Tag_Processor( $html );
			while ( $html->next_tag( 'img' ) ) {
				if ( $this->is_absolute_url( $html->get_attribute( 'src' ) ) ) {
					$html->set_attribute( 'src', $this->make_relative_media_url( $html->get_attribute( 'src' ) ) );
				}
			}
			$html = new WP_HTML_Tag_Processor( $html->__toString() );
			while ( $html->next_tag( 'video' ) ) {
				if ( $this->is_absolute_url( $html->get_attribute( 'src' ) ) ) {
					$html->set_attribute( 'src', $this->make_relative_media_url( $html->get_attribute( 'src' ) ) );
				}
				if ( $this->is_absolute_url( $html->get_attribute( 'poster' ) ) ) {
					$html->set_attribute( 'poster', $this->make_relative_media_url( $html->get_attribute( 'poster' ) ) );
				}
			}
			$html = new WP_HTML_Tag_Processor( $html->__toString() );
			while ( $html->next_tag( 'div' ) ) {
				$style = $html->get_attribute( 'style' );
				if ( $style ) {
					preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $style, $match);
					$urls = $match[0];
					foreach ( $urls as $url ) {
						if ( $this->is_absolute_url( $url ) ) {
							$html->set_attribute( 'style', str_replace( $url, $this->make_relative_media_url( $url ), $style ) );
						}
					}
				}
			}
			return $html->__toString();
		}
		
		// Fallback to DOMDocument.
		// TODO: When WP_HTML_Tag_Processor is availabe in core (6.2) we can remove this implementation entirely.
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) { 
			$doc = new DOMDocument();
			@$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			// replace all images that have absolute urls
			$img_tags = $doc->getElementsByTagName( 'img' );
			foreach ( $img_tags as $tag ) {
				$image_url = $tag->getAttribute( 'src' );
				if ( $this->is_absolute_url( $image_url ) ) {
					$img_src = $tag->getAttribute( 'src' );
					$html = str_replace( $img_src, $this->make_relative_media_url( $img_src ), $html );
				}
			}
			// replace all video that have absolute urls
			$video_tags = $doc->getElementsByTagName( 'video' );
			foreach ( $video_tags as $tag ) {
				$video_url = $tag->getAttribute( 'src' );
				if ( !empty( $video_url ) && $this->is_absolute_url( $video_url ) ) {
					$video_src = $tag->getAttribute( 'src' );
					$html = str_replace( $video_src, $this->make_relative_media_url( $video_src ), $html );
				}
				$poster_url = $tag->getAttribute( 'poster' );
				if ( !empty ( $poster_url ) && $this->is_absolute_url( $poster_url ) ) {
					$html = str_replace( $poster_url, $this->make_relative_media_url( $poster_url ), $html );
				}
			}
			// also replace background images with absolute urls (used in cover blocks)
			$div_tags = $doc->getElementsByTagName( 'div' );
			foreach ( $div_tags as $tag ) {
				$style = $tag->getAttribute( 'style' );
				if ( $style ) {
					preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $style, $match);
					$urls = $match[0];
					foreach ( $urls as $url ) {
						if ( $this->is_absolute_url( $url ) ) {
							$html = str_replace( $url, $this->make_relative_media_url( $url ), $html );
						}
					}
				}
			}
			return $html;
		}

	}

	function make_image_video_block_local ( $block ) {
		if ( 'core/image' === $block[ 'blockName' ] || 'core/video' === $block[ 'blockName' ] ) {
			$inner_html =  $this->make_html_media_local( $block[ 'innerHTML' ] );
			$inner_html = $this->escape_alt_for_pattern ( $inner_html );
			$block['innerHTML'] = $inner_html;
			$block['innerContent'] = array ( $inner_html );
		}
		return $block;
	}

	function make_cover_block_local ( $block ) {
		if ( 'core/cover' === $block[ 'blockName' ] ) {
			$inner_html = $this->make_html_media_local( $block[ 'innerHTML' ] );
			$inner_html = $this->escape_alt_for_pattern ( $inner_html );
			$inner_content = [];
			foreach ( $block['innerContent'] as $content ) {
				$content_html = $this->make_html_media_local( $content );
				$content_html = $this->escape_alt_for_pattern ( $content_html );
				$inner_content[] = $content_html;
			}
			$block['innerHTML'] = $inner_html;
			$block['innerContent'] = $inner_content;
			if ( isset ( $block['attrs']['url'] ) && $this->is_absolute_url( $block['attrs']['url'] ) ) {
				$block['attrs']['url'] = $this->make_relative_media_url( $block['attrs']['url'] );
			}
		}
		return $block;
	}

	function make_mediatext_block_local ( $block ) {
		if ( 'core/media-text' === $block[ 'blockName' ] ) {
			$inner_html = $this->make_html_media_local( $block[ 'innerHTML' ] );
			$inner_html = $this->escape_alt_for_pattern ( $inner_html );
			$inner_content = [];
			foreach ( $block['innerContent'] as $content ) {
				$content_html = $this->make_html_media_local( $content );
				$content_html = $this->escape_alt_for_pattern ( $content_html );
				$inner_content[] = $content_html;
			}
			$block['innerHTML'] = $inner_html;
			$block['innerContent'] = $inner_content;
			if ( isset ( $block['attrs']['mediaLink'] ) && $this->is_absolute_url( $block['attrs']['mediaLink'] ) ) {
				$block['attrs']['mediaLink'] = $this->make_relative_media_url( $block['attrs']['mediaLink'] );
			}
		}
		return $block;
	}

	function add_theme_attr_to_template_part_block ( $block ) {
		// The template parts included in the patterns need to indicate the theme they belong to
		if ( 'core/template-part' === $block[ 'blockName' ] ) {
			$block['attrs']['theme'] = ( $_POST['theme']['type'] === "export" || $_POST['theme']['type'] === "save" )
			? strtolower( wp_get_theme()->get( 'Name' ) )
			: $_POST['theme']['name'];
		}
		return $block;
	}

	function make_media_blocks_local ( $nested_blocks ) {
		$new_blocks = [];
		foreach ( $nested_blocks as $block ) {
			$inner_blocks = $block['innerBlocks'];
			switch ( $block[ 'blockName' ] ) {
				case 'core/image':
				case 'core/video':
					$block = $this->make_image_video_block_local( $block );
					break;
				case 'core/cover':
					$block = $this->make_cover_block_local( $block );
					break;
				case 'core/media-text':
					$block = $this->make_mediatext_block_local( $block );
					break;
				case 'core/template-part':
					$block = $this->add_theme_attr_to_template_part_block( $block );
					break;
			}
			// recursive call for inner blocks
			if ( !empty ( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->make_media_blocks_local( $inner_blocks );
			}
			$new_blocks[] = $block;
		}
		return $new_blocks;
	}

	function get_media_absolute_urls_from_blocks ( $flatten_blocks ) {
		$media = [];

		// If WP_HTML_Tag_Processor is available, use it to get the absolute URLs of img and background images
		// This class is available in core yet, but it will be available in the future (6.2)
		// see https://github.com/WordPress/gutenberg/pull/42485
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			foreach ( $flatten_blocks as $block ) {
				// Gets the absolute URLs of img in these blocks
				if ( 
					'core/image' === $block[ 'blockName' ] ||
					'core/video' === $block[ 'blockName' ] ||
					'core/cover' === $block[ 'blockName' ] ||
					'core/media-text' === $block[ 'blockName' ]
				) {
					$html = new WP_HTML_Tag_Processor( $block[ 'innerHTML' ] );
					while ( $html->next_tag( 'img' ) ) {
						$url = $html->get_attribute( 'src' );
						if ( $this->is_absolute_url( $url ) ) {
							$media[] = $url;
						}
					}
					$html = new WP_HTML_Tag_Processor( $html->__toString() );
					while ( $html->next_tag( 'video' ) ) {
						$url = $html->get_attribute( 'src' );
						if ( $this->is_absolute_url( $url ) ) {
							$media[] = $url;
						}
						$poster_url = $html->get_attribute( 'poster' );
						if ( $this->is_absolute_url( $poster_url ) ) {
							$media[] = $poster_url;
						}
					}
				}

				// Gets the absolute URLs of background images in these blocks
				if ( 'core/cover' === $block['blockName'] ) {
					$html = new WP_HTML_Tag_Processor( $block[ 'innerHTML' ] );
					while ( $html->next_tag( 'div' ) ) {
						$style = $html->get_attribute( 'style' );
						if ( $style ) {
							$matches = [];
							preg_match( '/background-image: url\((.*)\)/', $style, $matches );
							if ( isset( $matches[1] ) ) {
								$url = $matches[1];
								if ( $this->is_absolute_url( $url ) ) {
									$media[] = $url;
								}
							}
						}
					}
				}

			}
		}

		// Fallback to DOMDocument.
		// TODO: When WP_HTML_Tag_Processor is availabe in core (6.2) we can remove this implementation entirely.
		if ( ! class_exists ( 'WP_HTML_Tag_Processor' ) ) {
			foreach ( $flatten_blocks as $block ) {
				if ( 
						'core/image' === $block[ 'blockName' ] ||
						'core/video' === $block[ 'blockName' ] ||
						'core/cover' === $block[ 'blockName' ] ||
						'core/media-text' === $block[ 'blockName' ]
					) {
					$doc = new DOMDocument();
					@$doc->loadHTML( $block['innerHTML'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

					// Get the media urls from img tags
					$tags = $doc->getElementsByTagName( 'img' );
					foreach ( $tags as $tag ) {
						$image_url = $tag->getAttribute( 'src' );
						if ($this->is_absolute_url( $image_url )) {
							$media[] = $tag->getAttribute( 'src' );
						}
					}
					// Get the media urls from video tags
					$tags = $doc->getElementsByTagName( 'video' );
					foreach ( $tags as $tag ) {
						$video_url = $tag->getAttribute( 'src' );
						if ($this->is_absolute_url( $video_url )) {
							$media[] = $tag->getAttribute( 'src' );
						}
						$poster_url = $tag->getAttribute( 'poster' );
						if ($this->is_absolute_url( $poster_url )) {
							$media[] = $tag->getAttribute( 'poster' );
						}
					}
					// Get the media urls from div style tags (used in cover blocks)
					$div_tags = $doc->getElementsByTagName( 'div' );
					foreach ( $div_tags as $tag ) {
						$style = $tag->getAttribute( 'style' );
						if ( $style ) {
							preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $style, $match);
							$urls = $match[0];
							foreach ( $urls as $url ) {
								if ( $this->is_absolute_url( $url ) ) {
									$media[] = $url;
								}
							}
						}
					}
				}
			}
		}

		return $media;
	}

	// find all the media files used in the templates and add them to the zip
	function make_template_images_local ( $template ) {
		$new_content         = $template->content;
		$template_blocks     = parse_blocks( $template->content );
		$flatten_blocks	     = _flatten_blocks( $template_blocks );
		
		$blocks = $this->make_media_blocks_local( $template_blocks );
		$blocks = serialize_blocks ( $blocks );

		$template->content = $this->clean_serialized_markup ( $blocks );
		$template->media = $this->get_media_absolute_urls_from_blocks ( $flatten_blocks );
		return $template;
	}

	function clean_serialized_markup ( $markup ) {
		$markup = str_replace( '%20', ' ', $markup );
		$markup = str_replace( '\u003c', '<', $markup );
		$markup = str_replace( '\u003e', '>', $markup );
		$markup = html_entity_decode( $markup, ENT_QUOTES | ENT_XML1, 'UTF-8' );
		return $markup;
	}

	function pattern_from_template ( $template ) {
		$theme_slug = wp_get_theme()->get( 'TextDomain' );
		$pattern_slug = $theme_slug . '/' . $template->slug;
		$pattern_content = (
'<?php
/**
 * Title: '. $template->slug .'
 * Slug: ' . $pattern_slug. '
 * Categories: hidden
 * Inserter: no
 */
?>
'. $template->content
		);
		return array (
			'slug' => $pattern_slug,
			'content' => $pattern_content
		);
	}

	function escape_text_for_pattern( $text ) {
		if ( $text && trim ( $text ) !== "" ) {
			return "<?php echo esc_attr_e( '" . $text . "', '". wp_get_theme()->get( "Name" ) ."' ); ?>";

		}
	}

	function escape_alt_for_pattern ( $html ) {
		if ( empty ( $html ) ){
			return $html;
		}

		// Use WP_HTML_Tag_Processor if available
		// see: https://github.com/WordPress/gutenberg/pull/42485
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$html = new WP_HTML_Tag_Processor( $html );
			while ( $html->next_tag( 'img' ) ) {
				$alt_attribute = $html->get_attribute( 'alt' );
				if ( !empty ( $alt_attribute ) ) {
					$html->set_attribute( 'alt', $this->escape_text_for_pattern( $alt_attribute ) );
				}
			}
			return $html->__toString();
		}
		
		// Fallback to regex
		// TODO: When WP_HTML_Tag_Processor is availabe in core (6.2) we can remove this implementation entirely.
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			preg_match( '@alt="([^"]+)"@' , $html, $match );
			if ( isset( $match[0] ) ) {
				$alt_attribute = $match[0];
				$alt_value= $match[1];
				$html = str_replace(
					$alt_attribute,
					'alt="'.$this->escape_text_for_pattern( $alt_value ).'"',
					$html
				);
			}
			return $html;
		}
	}

	function get_media_folder_path_from_url ( $url ) {
		$extension = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		$folder_path = "";
		$image_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp' ];
		$video_extensions = [ 'mp4', 'm4v', 'webm', 'ogv', 'wmv', 'avi', 'mov', 'mpg', 'ogv', '3gp', '3g2' ];
		if ( in_array( $extension, $image_extensions ) ) {
			$folder_path = "/assets/images/";
		} else if ( in_array( $extension, $video_extensions ) ) {
			$folder_path = "/assets/videos/";
		} else {
			$folder_path = "/assets/";
		}
		return $folder_path;
	}

	function get_file_extension_from_url ( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		return $extension;
	} 

	function add_media_to_zip ( $zip, $media ) {
		$media = array_unique( $media );
		foreach ( $media as $url ) {
			$folder_path = $this->get_media_folder_path_from_url( $url );
			$download_file = file_get_contents( $url );
			$zip->addFromString( $folder_path . basename( $url ), $download_file );
		}
	}

	function add_media_to_local ( $media ) {
		foreach ( $media as $url ) {
			$download_file = file_get_contents( $url );
			$media_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . $this->get_media_folder_path_from_url ( $url );
			if ( ! is_dir( $media_path ) ) {
				wp_mkdir_p( $media_path );
			}
			file_put_contents(
				$media_path . basename( $url ),
				$download_file
			);
		}
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
			$template_data = $this->make_template_images_local( $template );

			// If there are images in the template, add it as a pattern
			if ( count( $template_data->media ) > 0 ) {
				$pattern = $this->pattern_from_template( $template_data );
				$template_data->content = '<!-- wp:pattern {"slug":"'. $pattern[ 'slug' ] .'"} /-->';

				// Add pattern to zip
				$zip->addFromString(
					'patterns/' . $template_data->slug . '.php',
					$pattern[ 'content' ]
				);

				// Add media assets to zip
				$this->add_media_to_zip( $zip, $template_data->media );
			}

			// Add template to zip
			$zip->addFromString(
				'templates/' . $template_data->slug . '.html',
				$template_data->content
			);

		}

		foreach ( $theme_templates->parts as $template_part ) {
			$template_data = $this->make_template_images_local( $template_part );

			// If there are images in the template, add it as a pattern
			if ( count( $template_data->media ) > 0 ) {
				$pattern = $this->pattern_from_template( $template_data );
				$template_data->content = '<!-- wp:pattern {"slug":"'. $pattern[ 'slug' ] .'"} /-->';

				// Add pattern to zip
				$zip->addFromString(
					'patterns/' . $template_data->slug . '.php',
					$pattern[ 'content' ]
				);

				// Add media assets to zip
				$this->add_media_to_zip( $zip, $template_data->media );
			}

			// Add template to zip
			$zip->addFromString(
				'parts/' . $template_data->slug . '.html',
				$template_data->content
			);
		}

		return $zip;
	}

	function add_templates_to_local( $export_type ) {

		$theme_templates = $this->get_theme_templates( $export_type, null );
		$template_folders = get_block_theme_folders();

		// If there is no templates folder, create it.
		if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template']  ) ) {
			wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template'] );
		}

		foreach ( $theme_templates->templates as $template ) {
			$template_data = $this->make_template_images_local( $template );

			// If there are images in the template, add it as a pattern
			if ( ! empty ( $template_data->media ) ) {
				// If there is no templates folder, create it.
				if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns'  ) ) {
					wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' );
				}

				// If there are external images, add it as a pattern
				$pattern = $this->pattern_from_template( $template_data );
				$template_data->content = '<!-- wp:pattern {"slug":"'. $pattern[ 'slug' ] .'"} /-->';

				// Write the pattern
				file_put_contents(
					get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . $template_data->slug . '.php',
					$pattern[ 'content' ]
				);
			}

			// Write the template content
			file_put_contents(
				get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template'] . DIRECTORY_SEPARATOR . $template->slug . '.html',
				$template_data->content
			);

			// Write the media assets
			$this->add_media_to_local( $template_data->media );
			
		}

		// If there is no parts folder, create it.
		if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] ) ) {
			wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] );
		}

		foreach ( $theme_templates->parts as $template_part ) {
			$template_data = $this->make_template_images_local( $template_part );

			// If there are images in the template, add it as a pattern
			if ( ! empty ( $template_data->media ) ) {
				// If there is no templates folder, create it.
				if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns'  ) ) {
					wp_mkdir_p( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' );
				}

				// If there are external images, add it as a pattern
				$pattern = $this->pattern_from_template( $template_data );
				$template_data->content = '<!-- wp:pattern {"slug":"'. $pattern[ 'slug' ] .'"} /-->';

				// Write the pattern
				file_put_contents(
					get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'patterns' . DIRECTORY_SEPARATOR . $template_data->slug . '.php',
					$pattern[ 'content' ]
				);
			}

			// Write the template content
			file_put_contents(
				get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_folders['wp_template_part'] . DIRECTORY_SEPARATOR . $template_data->slug . '.html',
				$template_data->content
			);

			// Write the media assets
			$this->add_media_to_local( $template_data->media );
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
			<form enctype="multipart/form-data" method="POST">
				<div id="col-container">
					<div id="col-left">
						<div class="col-wrap">
							<p><?php printf( esc_html__('Export your current block theme (%1$s) with changes you made to Templates, Template Parts and Global Styles.', 'create-block-theme'),  esc_html( wp_get_theme()->get('Name') ) ); ?></p>

							<label>
								<input checked value="export" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', true );toggleForm( 'new_variation_metadata_form', true );" />
								<?php
									printf(
										/* translators: Theme Name. */
										__('Export %s', 'create-block-theme'),
										wp_get_theme()->get('Name')
									);
								?>
								<br />
								<?php _e('[Export the activated theme with user changes]', 'create-block-theme'); ?>
							</label>
							<br /><br />
							<?php if ( is_child_theme() ): ?>
								<label>
									<input value="sibling" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );"/>
									<?php
									printf(
										/* translators: Theme Name. */
										__('Create sibling of %s', 'create-block-theme'),
										wp_get_theme()->get('Name')
									);
									?>
								</label>
								<br />
								<?php _e('[Create a new theme cloning the activated child theme.  The parent theme will be the same as the parent of the currently activated theme. The resulting theme will have all of the assets of the activated theme, none of the assets provided by the parent theme, as well as user changes.]', 'create-block-theme'); ?>
								<p><b><?php _e('NOTE: Sibling themes created from this theme will have the original namespacing. This should be changed manually once the theme has been created.', 'create-block-theme'); ?></b></p>
								<br />
							<?php else: ?>
								<label>
									<input value="child" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );"/>
									<?php
									printf(
										/* translators: Theme Name. */
										__('Create child of %s', 'create-block-theme'),
										wp_get_theme()->get('Name')
									);
									?>
								</label>
								<br />
								<?php _e('[Create a new child theme. The currently activated theme will be the parent theme.]', 'create-block-theme'); ?>
								<br /><br />
								<label>
									<input value="clone" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );"/>
									<?php
										printf(
											/* translators: Theme Name. */
											__('Clone %s', 'create-block-theme'),
											wp_get_theme()->get('Name')
										);
									?>
									<br />
									<?php _e('[Create a new theme cloning the activated theme. The resulting theme will have all of the assets of the activated theme as well as user changes.]', 'create-block-theme'); ?>
								</label>
								<br /><br />
							<?php endif; ?>
							<label>
								<input value="save" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', true );toggleForm( 'new_variation_metadata_form', true );" />
								<?php
									printf(
										/* translators: Theme Name. */
										__('Overwrite %s', 'create-block-theme'),
										wp_get_theme()->get('Name')
									);
								?>
								<br />
								<?php _e('[Save USER changes as THEME changes and delete the USER changes.  Your changes will be saved in the theme on the folder.]', 'create-block-theme'); ?>
							</label>
							<br /><br />
							<label>
								<input value="blank" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_theme_metadata_form', false );" />
								<?php _e('Create blank theme', 'create-block-theme'); ?><br />
								<?php _e('[Generate a boilerplate "empty" theme inside of this site\'s themes directory.]', 'create-block-theme'); ?>
							</label>
							<br /><br />
							<label>
								<input value="variation" type="radio" name="theme[type]" class="regular-text code" onchange="toggleForm( 'new_variation_metadata_form', false );" />
								<?php _e('Create a style variation', 'create-block-theme'); ?><br />
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
								</label><br /><br />
								<label for="screenshot">
									<?php _e('Screenshot:', 'create-block-theme'); ?><br />
									<small><?php _e('Upload a new theme screenshot (2mb max | .png only | 1200x900 recommended)', 'create-block-theme'); ?></small><br />
									<input type="file" accept=".png"  name="screenshot" id="screenshot" class="upload"/>
								</label><br/>
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

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'create-block-theme' && ! empty( $_POST['theme'] ) ) {

			// Check user capabilities.
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
			}

			// Check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) ) {
				return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
			}

			if ( $_POST['theme']['type'] === 'save' ) {
				// Avoid running if WordPress dosn't have permission to overwrite the theme folder
				if ( ! wp_is_writable( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_file_permissions' ] );
				}

				if ( is_child_theme() ) {
					$this->save_theme_locally( 'current' );
				}
				else {
					$this->save_theme_locally( 'all' );
				}
				$this->clear_user_styles_customizations();
				$this->clear_user_templates_customizations();

				add_action( 'admin_notices', [ $this, 'admin_notice_save_success' ] );
			}

			else if ( $_POST['theme']['type'] === 'variation' ) {

				if ( $_POST['theme']['variation'] === '' ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_variation_name' ] );
				}

				// Avoid running if WordPress dosn't have permission to write the theme folder
				if ( ! wp_is_writable ( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_file_permissions' ] );
				}

				if ( is_child_theme() ) {
					$this->save_variation( 'current', $_POST['theme'] );
				}
				else {
					$this->save_variation( 'all', $_POST['theme'] );
				}
				$this->clear_user_styles_customizations();

				add_action( 'admin_notices', [ $this, 'admin_notice_variation_success' ] );
			}

			else if ( $_POST['theme']['type'] === 'blank' ) {
				// Avoid running if WordPress dosn't have permission to write the themes folder
				if ( ! wp_is_writable ( get_theme_root() ) ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_themes_file_permissions' ] );
				}

				if ( $_POST['theme']['name'] === '' ) {
					return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
				}
				$this->create_blank_theme( $_POST['theme'], $_FILES['screenshot'] );

				add_action( 'admin_notices', [ $this, 'admin_notice_blank_success' ] );
			}

			else if ( is_child_theme() ) {
				if ( $_POST['theme']['type'] === 'sibling' ) {
					if ( $_POST['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
					}
					$this->create_sibling_theme( $_POST['theme'], $_FILES['screenshot'] );
				}
				else {
					$this->export_child_theme( $_POST['theme'] );
				}
				add_action( 'admin_notices', [ $this, 'admin_notice_export_success' ] );
			} else {
				if( $_POST['theme']['type'] === 'child' ) {
					if ( $_POST['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
					}
					$this->create_child_theme( $_POST['theme'], $_FILES['screenshot'] );
				}
				else if( $_POST['theme']['type'] === 'clone' ) {
					if ( $_POST['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ $this, 'admin_notice_error_theme_name' ] );
					}
					$this->clone_theme( $_POST['theme'], $_FILES['screenshot'] );
				}
				else {
					$this->export_theme( $_POST['theme'] );
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
		$theme_name = $_POST['theme']['name'];

		?>
			<div class="notice notice-success is-dismissible">
				<p><?php printf( esc_html__( 'Blank theme created, head over to Appearance > Themes to activate %1$s', 'create-block-theme' ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

	function admin_notice_variation_success() {
		$theme_name = wp_get_theme()->get( 'Name' );
		$variation_name = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . $_POST['theme']['variation_slug'] .'.json';

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

    const ALLOWED_SCREENSHOT_TYPES = array(
        'png'   => 'image/png'
    );

    function is_valid_screenshot( $file ) {
		$filetype = wp_check_filetype( $file['name'], self::ALLOWED_SCREENSHOT_TYPES );
		if ( is_uploaded_file( $file['tmp_name'] ) && in_array( $filetype['type'], self::ALLOWED_SCREENSHOT_TYPES ) && $file['size'] < 2097152 ) {
			return 1;
		}
        return 0;
    }
}
