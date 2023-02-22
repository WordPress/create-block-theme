<?php

require_once( __DIR__ . '/resolver_additions.php' );
require_once( __DIR__ . '/create-theme/theme-tags.php' );
require_once( __DIR__ . '/create-theme/theme-zip.php' );
require_once( __DIR__ . '/create-theme/theme-media.php' );
require_once( __DIR__ . '/create-theme/theme-blocks.php' );
require_once( __DIR__ . '/create-theme/theme-patterns.php' );
require_once( __DIR__ . '/create-theme/theme-templates.php' );
require_once( __DIR__ . '/create-theme/theme-styles.php' );
require_once( __DIR__ . '/create-theme/theme-json.php' );
require_once( __DIR__ . '/create-theme/theme-utils.php' );
require_once( __DIR__ . '/create-theme/theme-readme.php' );
require_once( __DIR__ . '/create-theme/theme-form.php' );
require_once( __DIR__ . '/create-theme/form-messages.php' );

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
		add_theme_page( $page_title, $menu_title, 'edit_theme_options', 'create-block-theme', [ 'Theme_Form', 'create_admin_form_page' ] );

		add_action('admin_enqueue_scripts', [ 'Theme_Form', 'form_script' ] );
	}

	function save_theme_locally( $export_type ) {
		Theme_Templates::add_templates_to_local( $export_type );
		Theme_Json::add_theme_json_to_local( $export_type );
	}

	function save_variation ( $export_type, $theme ) {
		Theme_Json::add_theme_json_variation_to_local( $export_type, $theme );
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
		$theme['tags_custom'] = sanitize_text_field( $theme['tags_custom'] );
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
			Theme_Readme::build_readme_txt( $theme )
		);

		// Augment style.css
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		// Remove metadata from style.css file
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, "*/" ) + 2 ) );
		// Add new metadata
		$css_contents = Theme_Styles::build_child_style_css( $theme ) . $css_contents;
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
		$theme['tags_custom'] = sanitize_text_field( $theme['tags_custom'] );
		$theme['slug'] = $this->get_theme_slug( $theme['name'] );
		$theme['template'] = wp_get_theme()->get( 'Template' );
		$theme['original_theme'] = wp_get_theme()->get( 'Name' );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip = $this->create_zip( $filename );

		$zip = $this->copy_theme_to_zip( $zip, $theme['slug'], $theme['name']);

		$zip = $this->add_templates_to_zip( $zip, 'all', $theme['slug'] );
		$zip = $this->add_theme_json_to_zip( $zip, 'all' );

		// Add readme.txt.
		$zip->addFromString(
			'readme.txt',
			Theme_Readme::build_readme_txt( $theme )
		);

		// Augment style.css
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		// Remove metadata from style.css file
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, "*/" ) + 2 ) );
		// Add new metadata
		$css_contents = Theme_Styles::build_child_style_css( $theme ) . $css_contents;
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
		$theme['tags_custom'] = sanitize_text_field( $theme['tags_custom'] );
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
			Theme_Readme::build_readme_txt( $theme )
		);

		// Add style.css.
		$zip->addFromString(
			'style.css',
			Theme_Styles::build_child_style_css( $theme )
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
		$theme['tags_custom'] = sanitize_text_field( $theme['tags_custom'] );
		$theme['template'] = '';
		$theme['slug'] = $this->get_theme_slug( $theme['name'] );

		// Create theme directory.
		$source = plugin_dir_path( __DIR__ ) . 'assets/boilerplate';
		$blank_theme_path = get_theme_root() . DIRECTORY_SEPARATOR . $theme['slug'];
		if ( ! file_exists( $blank_theme_path ) ) {
			wp_mkdir_p( $blank_theme_path );
			// Add readme.txt.
			file_put_contents( 
				$blank_theme_path . DIRECTORY_SEPARATOR . 'readme.txt', 
				Theme_Readme::build_readme_txt( $theme )
			);

			// Add new metadata.
			$css_contents = Theme_Styles::build_child_style_css( $theme );

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
					wp_mkdir_p( $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
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
		$new_slug = preg_replace('/\s+/', '', $new_slug); // Remove spaces

		if( ! str_contains( $old_slug , '-') && str_contains( $new_slug, '-' ) ) {
			return str_replace( '-', '', $new_slug );
		}

		return $new_slug;
	}

	function is_absolute_url( $url ) {
		return ! empty( $url ) &&  isset( parse_url( $url )[ 'host' ] );
	}

	function get_file_extension_from_url ( $url ) {
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		return $extension;
	} 

	function add_media_to_zip ( $zip, $media ) {
		$media = array_unique( $media );
		foreach ( $media as $url ) {
			$folder_path = Theme_Media::get_media_folder_path_from_url( $url );
			$download_file = file_get_contents( $url );
			$zip->addFromString( $folder_path . basename( $url ), $download_file );
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
		$theme_templates = Theme_Templates::get_theme_templates( $export_type, $new_slug );

		if ( $theme_templates->templates ) {
			$zip->addEmptyDir( 'templates' );
		}

		if ( $theme_templates->parts ) {
			$zip->addEmptyDir( 'parts' );
		}

		foreach ( $theme_templates->templates as $template ) {
			$template_data = Theme_Blocks::make_template_images_local( $template );

			// If there are images in the template, add it as a pattern
			if ( count( $template_data->media ) > 0 ) {
				$pattern = Theme_Patterns::pattern_from_template( $template_data );
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
			$template_data = Theme_Blocks::make_template_images_local( $template_part );

			// If there are images in the template, add it as a pattern
			if ( count( $template_data->media ) > 0 ) {
				$pattern = Theme_Patterns::pattern_from_template( $template_data );
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

	function create_zip( $filename ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'Zip Export not supported.' );
		}
		$zip = new ZipArchive();
		$zip->open( $filename, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		return $zip;
	}

	function blockbase_save_theme() {

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'create-block-theme' && ! empty( $_POST['theme'] ) ) {

			// Check user capabilities.
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_name' ] );
			}

			// Check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) ) {
				return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_name' ] );
			}

			if ( $_POST['theme']['type'] === 'save' ) {
				// Avoid running if WordPress dosn't have permission to overwrite the theme folder
				if ( ! wp_is_writable( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_file_permissions' ] );
				}

				if ( is_child_theme() ) {
					$this->save_theme_locally( 'current' );
				}
				else {
					$this->save_theme_locally( 'all' );
				}
				Theme_Styles::clear_user_styles_customizations();
				Theme_Templates::clear_user_templates_customizations();

				add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_save_success' ] );
			}

			else if ( $_POST['theme']['type'] === 'variation' ) {

				if ( $_POST['theme']['variation'] === '' ) {
					return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_variation_name' ] );
				}

				// Avoid running if WordPress dosn't have permission to write the theme folder
				if ( ! wp_is_writable ( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_file_permissions' ] );
				}

				if ( is_child_theme() ) {
					$this->save_variation( 'current', $_POST['theme'] );
				}
				else {
					$this->save_variation( 'all', $_POST['theme'] );
				}
				Theme_Styles::clear_user_styles_customizations();

				add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_variation_success' ] );
			}

			else if ( $_POST['theme']['type'] === 'blank' ) {
				// Avoid running if WordPress dosn't have permission to write the themes folder
				if ( ! wp_is_writable ( get_theme_root() ) ) {
					return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_themes_file_permissions' ] );
				}

				if ( $_POST['theme']['name'] === '' ) {
					return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_name' ] );
				}
				$this->create_blank_theme( $_POST['theme'], $_FILES['screenshot'] );

				add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_blank_success' ] );
			}

			else if ( is_child_theme() ) {
				if ( $_POST['theme']['type'] === 'sibling' ) {
					if ( $_POST['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_name' ] );
					}
					$this->create_sibling_theme( $_POST['theme'], $_FILES['screenshot'] );
				}
				else {
					$this->export_child_theme( $_POST['theme'] );
				}
				add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_export_success' ] );
			} else {
				if( $_POST['theme']['type'] === 'child' ) {
					if ( $_POST['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_name' ] );
					}
					$this->create_child_theme( $_POST['theme'], $_FILES['screenshot'] );
				}
				else if( $_POST['theme']['type'] === 'clone' ) {
					if ( $_POST['theme']['name'] === '' ) {
						return add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_error_theme_name' ] );
					}
					$this->clone_theme( $_POST['theme'], $_FILES['screenshot'] );
				}
				else {
					$this->export_theme( $_POST['theme'] );
				}
				add_action( 'admin_notices', [ 'Form_Messages', 'admin_notice_export_success' ] );
			}

		}
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
