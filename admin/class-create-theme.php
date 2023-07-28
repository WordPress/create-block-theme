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
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'blockbase_save_theme' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'create_block_theme_enqueue' ) );
	}

	function create_block_theme_enqueue() {
		global $pagenow;

		if ( 'site-editor.php' !== $pagenow ) {
			return;
		}

		$asset_file = include( plugin_dir_path( dirname( __FILE__ ) ) . 'build/plugin-sidebar.asset.php' );

		wp_register_script(
			'create-block-theme-slot-fill',
			plugins_url( 'build/plugin-sidebar.js', dirname( __FILE__ ) ),
			$asset_file['dependencies'],
			$asset_file['version']
		);
		wp_enqueue_script(
			'create-block-theme-slot-fill',
		);

		// Enable localization in the plugin sidebar.
		wp_set_script_translations( 'create-block-theme-slot-fill', 'create-block-theme' );
	}

	function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}
		$page_title = _x( 'Create Block Theme', 'UI String', 'create-block-theme' );
		$menu_title = _x( 'Create Block Theme', 'UI String', 'create-block-theme' );
		add_theme_page( $page_title, $menu_title, 'edit_theme_options', 'create-block-theme', array( 'Theme_Form', 'create_admin_form_page' ) );

		add_action( 'admin_enqueue_scripts', array( 'Theme_Form', 'form_script' ) );
	}

	function save_theme_locally( $export_type ) {
		Theme_Templates::add_templates_to_local( $export_type );
		Theme_Json::add_theme_json_to_local( $export_type );
	}

	function save_variation( $export_type, $theme ) {
		Theme_Json::add_theme_json_variation_to_local( 'variation', $theme );
	}

	/**
	 * Export activated child theme
	 */
	function export_child_theme( $theme ) {
		$theme['slug'] = Theme_Utils::get_theme_slug( $theme['name'] );

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::copy_theme_to_zip( $zip, null, null );
		$zip = Theme_Zip::add_templates_to_zip( $zip, 'current', $theme['slug'] );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'current' );

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
		$theme_slug = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']                = sanitize_text_field( $theme['name'] );
		$theme['description']         = sanitize_text_field( $theme['description'] );
		$theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$theme['author']              = sanitize_text_field( $theme['author'] );
		$theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$theme['image_credits']       = sanitize_textarea_field( $theme['image_credits'] );
		$theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$theme['slug']                = $theme_slug;
		$theme['template']            = wp_get_theme()->get( 'Template' );
		$theme['text_domain']         = $theme_slug;

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::copy_theme_to_zip( $zip, $theme['slug'], $theme['name'] );
		$zip = Theme_Zip::add_templates_to_zip( $zip, 'current', $theme['slug'] );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'current' );

		// Add readme.txt.
		$zip->addFromStringToTheme(
			'readme.txt',
			Theme_Readme::build_readme_txt( $theme )
		);

		// Augment style.css
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		// Remove metadata from style.css file
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, '*/' ) + 2 ) );
		// Add new metadata
		$css_contents = Theme_Styles::build_child_style_css( $theme ) . $css_contents;
		$zip->addFromStringToTheme(
			'style.css',
			$css_contents
		);

		// Add / replace screenshot.
		if ( $this->is_valid_screenshot( $screenshot ) ) {
			$zip->addFileToTheme(
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
		$theme_slug = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']                = sanitize_text_field( $theme['name'] );
		$theme['description']         = sanitize_text_field( $theme['description'] );
		$theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$theme['author']              = sanitize_text_field( $theme['author'] );
		$theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$theme['image_credits']       = sanitize_textarea_field( $theme['image_credits'] );
		$theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$theme['slug']                = $theme_slug;
		$theme['template']            = '';
		$theme['original_theme']      = wp_get_theme()->get( 'Name' );
		$theme['text_domain']         = $theme_slug;

		// Use previous theme's tags if custom tags are empty.
		if ( empty( $theme['tags_custom'] ) ) {
			$theme['tags_custom'] = implode( ', ', wp_get_theme()->get( 'Tags' ) );
		}

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::copy_theme_to_zip( $zip, $theme['slug'], $theme['name'] );

		$zip = Theme_Zip::add_templates_to_zip( $zip, 'all', $theme['slug'] );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'all' );

		// Add readme.txt.
		$zip->addFromStringToTheme(
			'readme.txt',
			Theme_Readme::build_readme_txt( $theme )
		);

		// Augment style.css
		$css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );
		// Remove metadata from style.css file
		$css_contents = trim( substr( $css_contents, strpos( $css_contents, '*/' ) + 2 ) );
		// Add new metadata
		$css_contents = Theme_Styles::build_child_style_css( $theme ) . $css_contents;
		$zip->addFromStringToTheme(
			'style.css',
			$css_contents
		);

		// Add / replace screenshot.
		if ( $this->is_valid_screenshot( $screenshot ) ) {
			$zip->addFileToTheme(
				$screenshot['tmp_name'],
				'screenshot.png'
			);
		}

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		readfile( $filename );
		unlink( $filename );
		exit;
	}

	/**
	 * Create a child theme of the activated theme
	 */
	function create_child_theme( $theme, $screenshot ) {

		$parent_theme_slug = Theme_Utils::get_theme_slug( wp_get_theme()->get( 'Name' ) );
		$child_theme_slug  = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']                = sanitize_text_field( $theme['name'] );
		$theme['description']         = sanitize_text_field( $theme['description'] );
		$theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$theme['author']              = sanitize_text_field( $theme['author'] );
		$theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$theme['image_credits']       = sanitize_textarea_field( $theme['image_credits'] );
		$theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$theme['is_parent_theme']     = true;
		$theme['text_domain']         = $child_theme_slug;
		$theme['template']            = $parent_theme_slug;
		$theme['slug']                = $child_theme_slug;

		// Create ZIP file in the temporary directory.
		$filename = tempnam( get_temp_dir(), $theme['slug'] );
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::add_templates_to_zip( $zip, 'user', $theme['slug'] );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'user' );

		// Add readme.txt.
		$zip->addFromStringToTheme(
			'readme.txt',
			Theme_Readme::build_readme_txt( $theme )
		);

		// Add style.css.
		$zip->addFromStringToTheme(
			'style.css',
			Theme_Styles::build_child_style_css( $theme )
		);

		// Add / replace screenshot.
		if ( $this->is_valid_screenshot( $screenshot ) ) {
			$zip->addFileToTheme(
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
		$zip      = Theme_Zip::create_zip( $filename );

		$zip = Theme_Zip::copy_theme_to_zip( $zip, null, null );
		$zip = Theme_Zip::add_templates_to_zip( $zip, 'all', null );
		$zip = Theme_Zip::add_theme_json_to_zip( $zip, 'all' );

		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $theme['slug'] . '.zip' );
		header( 'Content-Length: ' . filesize( $filename ) );
		flush();
		echo readfile( $filename );
		die();
	}

	function create_blank_theme( $theme, $screenshot ) {
		$theme_slug = Theme_Utils::get_theme_slug( $theme['name'] );

		// Sanitize inputs.
		$theme['name']                = sanitize_text_field( $theme['name'] );
		$theme['description']         = sanitize_text_field( $theme['description'] );
		$theme['uri']                 = sanitize_text_field( $theme['uri'] );
		$theme['author']              = sanitize_text_field( $theme['author'] );
		$theme['author_uri']          = sanitize_text_field( $theme['author_uri'] );
		$theme['tags_custom']         = sanitize_text_field( $theme['tags_custom'] );
		$theme['image_credits']       = sanitize_textarea_field( $theme['image_credits'] );
		$theme['recommended_plugins'] = sanitize_textarea_field( $theme['recommended_plugins'] );
		$theme['template']            = '';
		$theme['slug']                = $theme_slug;
		$theme['text_domain']         = $theme_slug;

		// Create theme directory.
		$source           = plugin_dir_path( __DIR__ ) . 'assets/boilerplate';
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

			// Add style.css.
			file_put_contents(
				$blank_theme_path . DIRECTORY_SEPARATOR . 'style.css',
				$css_contents
			);

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach (
				$iterator as $item
				) {
				if ( $item->isDir() ) {
					wp_mkdir_p( $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname() );
				} else {
					copy( $item, $blank_theme_path . DIRECTORY_SEPARATOR . $iterator->getSubPathname() );
				}
			}

			// Overwrite default screenshot if one is provided.
			if ( $this->is_valid_screenshot( $screenshot ) ) {
				file_put_contents(
					$blank_theme_path . DIRECTORY_SEPARATOR . 'screenshot.png',
					file_get_contents( $screenshot['tmp_name'] )
				);
			}

			if ( ! defined( 'IS_GUTENBERG_PLUGIN' ) ) {
				global $wp_version;
				$theme_json_version = 'wp/' . substr( $wp_version, 0, 3 );
					$schema         = '"$schema": "https://schemas.wp.org/' . $theme_json_version . '/theme.json"';
				$theme_json_path    = $blank_theme_path . DIRECTORY_SEPARATOR . 'theme.json';
				$theme_json_string  = file_get_contents( $theme_json_path );
				$theme_json_string  = str_replace( '"$schema": "https://schemas.wp.org/trunk/theme.json"', $schema, $theme_json_string );
				file_put_contents( $theme_json_path, $theme_json_string );
			}
		}

	}

	function blockbase_save_theme() {

		if ( ! empty( $_GET['page'] ) && 'create-block-theme' === $_GET['page'] && ! empty( $_POST['theme'] ) ) {

			// Check user capabilities.
			if ( ! current_user_can( 'edit_theme_options' ) ) {
				return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_name' ) );
			}

			// Check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) ) {
				return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_name' ) );
			}

			if ( 'save' === $_POST['theme']['type'] ) {
				// Avoid running if WordPress dosn't have permission to overwrite the theme folder
				if ( ! wp_is_writable( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_file_permissions' ) );
				}

				if ( is_child_theme() ) {
					$this->save_theme_locally( 'current' );
				} else {
					$this->save_theme_locally( 'all' );
				}
				Theme_Styles::clear_user_styles_customizations();
				Theme_Templates::clear_user_templates_customizations();

				add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_save_success' ) );
			} elseif ( 'variation' === $_POST['theme']['type'] ) {

				if ( '' === $_POST['theme']['variation'] ) {
					return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_variation_name' ) );
				}

				// Avoid running if WordPress dosn't have permission to write the theme folder
				if ( ! wp_is_writable( get_stylesheet_directory() ) ) {
					return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_file_permissions' ) );
				}

				if ( is_child_theme() ) {
					$this->save_variation( 'current', $_POST['theme'] );
				} else {
					$this->save_variation( 'all', $_POST['theme'] );
				}
				Theme_Styles::clear_user_styles_customizations();

				add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_variation_success' ) );
			} elseif ( 'blank' === $_POST['theme']['type'] ) {
				// Avoid running if WordPress dosn't have permission to write the themes folder
				if ( ! wp_is_writable( get_theme_root() ) ) {
					return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_themes_file_permissions' ) );
				}

				if ( '' === $_POST['theme']['name'] ) {
					return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_name' ) );
				}
				$this->create_blank_theme( $_POST['theme'], $_FILES['screenshot'] );

				add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_blank_success' ) );
			} elseif ( is_child_theme() ) {
				if ( 'sibling' === $_POST['theme']['type'] ) {
					if ( '' === $_POST['theme']['name'] ) {
						return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_name' ) );
					}
					$this->create_sibling_theme( $_POST['theme'], $_FILES['screenshot'] );
				} else {
					$this->export_child_theme( $_POST['theme'] );
				}
				add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_export_success' ) );
			} else {
				if ( 'child' === $_POST['theme']['type'] ) {
					if ( '' === $_POST['theme']['name'] ) {
						return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_name' ) );
					}
					$this->create_child_theme( $_POST['theme'], $_FILES['screenshot'] );
				} elseif ( 'clone' === $_POST['theme']['type'] ) {
					if ( '' === $_POST['theme']['name'] ) {
						return add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_error_theme_name' ) );
					}
					$this->clone_theme( $_POST['theme'], $_FILES['screenshot'] );
				} else {
					$this->export_theme( $_POST['theme'] );
				}
				add_action( 'admin_notices', array( 'Form_Messages', 'admin_notice_export_success' ) );
			}
		}
	}

	const ALLOWED_SCREENSHOT_TYPES = array(
		'png' => 'image/png',
	);

	function is_valid_screenshot( $file ) {
		$filetype = wp_check_filetype( $file['name'], self::ALLOWED_SCREENSHOT_TYPES );
		if ( is_uploaded_file( $file['tmp_name'] ) && in_array( $filetype['type'], self::ALLOWED_SCREENSHOT_TYPES, true ) && $file['size'] < 2097152 ) {
			return 1;
		}
		return 0;
	}
}
