<?php

require_once( __DIR__ . '/font-helpers.php' );
require_once( __DIR__ . '/manage-fonts/fonts-page.php' );
require_once( __DIR__ . '/manage-fonts/google-fonts-page.php' );
require_once( __DIR__ . '/manage-fonts/local-fonts-page.php' );
require_once( __DIR__ . '/manage-fonts/font-form-messages.php' );

class Manage_Fonts_Admin {

	public function __construct() {
		add_action( 'init', array( $this, 'save_manage_fonts_changes' ), 1 ); // <- High priority to run before the theme.json data is loaded
		add_action( 'admin_init', array( $this, 'save_google_fonts_to_theme' ) );
		add_action( 'admin_init', array( $this, 'save_local_fonts_to_theme' ) );
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
	}

	const ALLOWED_FONT_MIME_TYPES = array(
		'otf'   => 'font/otf',
		'ttf'   => 'font/ttf',
		'woff'  => 'font/woff',
		'woff2' => 'font/woff2',
	);

	function has_font_mime_type( $file ) {
		$filetype = wp_check_filetype( $file, self::ALLOWED_FONT_MIME_TYPES );
		return in_array( $filetype['type'], self::ALLOWED_FONT_MIME_TYPES, true );
	}

	function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$manage_fonts_page_title = _x( 'Manage Theme Fonts', 'UI String', 'create-block-theme' );
		$manage_fonts_menu_title = $manage_fonts_page_title;
		add_theme_page( $manage_fonts_page_title, $manage_fonts_menu_title, 'edit_theme_options', 'manage-fonts', array( 'Fonts_Page', 'manage_fonts_admin_page' ) );

		$google_fonts_page_title = _x( 'Embed Google font in the active theme', 'UI String', 'create-block-theme' );
		$google_fonts_menu_title = $google_fonts_page_title;
		add_submenu_page( null, $google_fonts_page_title, $google_fonts_menu_title, 'edit_theme_options', 'add-google-font-to-theme-json', array( 'Google_Fonts', 'google_fonts_admin_page' ) );

		$local_fonts_page_title = _x( 'Embed local font in the active theme', 'UI String', 'create-block-theme' );
		$local_fonts_menu_title = $local_fonts_page_title;
		add_submenu_page( null, $local_fonts_page_title, $local_fonts_menu_title, 'edit_theme_options', 'add-local-font-to-theme-json', array( 'Local_Fonts', 'local_fonts_admin_page' ) );
	}

	function has_file_and_user_permissions() {
		$has_user_permissions = $this->user_can_edit_themes();
		$has_file_permissions = $this->can_read_and_write_font_assets_directory();
		return $has_user_permissions && $has_file_permissions;
	}

	function user_can_edit_themes() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
			add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_file_edit_error' ) );
			return false;
		}

		if ( ! current_user_can( 'edit_themes' ) ) {
			add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_user_cant_edit_theme' ) );
			return false;
		}
		return true;
	}

	function can_read_and_write_font_assets_directory() {
		// Create the font assets folder if it doesn't exist
		$temp_dir         = get_temp_dir();
		$font_assets_path = get_stylesheet_directory() . '/assets/fonts/';
		if ( ! is_dir( $font_assets_path ) ) {
			wp_mkdir_p( $font_assets_path );
		}
		// If the font asset folder can't be written return an error
		if ( ! wp_is_writable( $font_assets_path ) || ! is_readable( $font_assets_path ) || ! wp_is_writable( $temp_dir ) ) {
			add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_manage_fonts_permission_error' ) );
			return false;
		}
		return true;
	}

	function delete_font_asset( $font_face ) {
		// if the font asset is a theme asset, delete it
		$theme_folder = get_stylesheet_directory();
		$font_path    = pathinfo( $font_face['src'][0] );
		$font_dir     = str_replace( 'file:./', '', $font_path['dirname'] );

		$font_asset_path = $theme_folder . DIRECTORY_SEPARATOR . $font_dir . DIRECTORY_SEPARATOR . $font_path['basename'];

		if ( ! wp_is_writable( $theme_folder . DIRECTORY_SEPARATOR . $font_dir ) ) {
			return add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_font_asset_removal_error' ) );
		}

		if ( file_exists( $font_asset_path ) ) {
			return unlink( $font_asset_path );
		}

		return false;
	}

	protected function prepare_font_families_for_database( $font_families ) {
		$prepared_font_families = array();

		foreach ( $font_families as $font_family ) {
			if ( isset( $font_family['fontFace'] ) ) {
				$new_font_faces = array();
				foreach ( $font_family['fontFace'] as $font_face ) {
					$updated_font_face = $font_face;
					// Remove font license from readme.txt if font family is removed
					if ( isset( $font_family['shouldBeRemoved'] ) ) {
						$this->manage_font_license( $font_face['fontFamily'], 'remove' );
					}
					if ( ! isset( $font_face['shouldBeRemoved'] ) && ! isset( $font_family['shouldBeRemoved'] ) ) {
						$new_font_faces[] = $updated_font_face;
					} else {
						$this->delete_font_asset( $font_face );
					}
				}

				$font_family['fontFace'] = $new_font_faces;
			}
			if ( ! isset( $font_family['shouldBeRemoved'] ) ) {
				$prepared_font_families[] = $font_family;
			}
		}

		return $prepared_font_families;
	}

	function save_manage_fonts_changes() {
		if (
			! empty( $_POST['nonce'] ) &&
			wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) &&
			! empty( $_POST['new-theme-fonts-json'] ) &&
			$this->has_file_and_user_permissions()
		) {
			// parse json from form
			$new_theme_fonts_json = json_decode( stripslashes( $_POST['new-theme-fonts-json'] ), true );
			$new_font_families    = $this->prepare_font_families_for_database( $new_theme_fonts_json );

			$this->replace_all_theme_font_families( $new_font_families );

			add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_delete_font_success' ) );
		}
	}

	function save_local_fonts_to_theme() {
		if (
			! empty( $_POST['nonce'] ) &&
			wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) &&
			! empty( $_FILES['font-file'] ) &&
			! empty( $_POST['font-name'] ) &&
			! empty( $_POST['font-weight'] ) &&
			! empty( $_POST['font-style'] ) &&
			$this->has_file_and_user_permissions()
		) {
			if (
				$this->has_font_mime_type( $_FILES['font-file']['name'] ) &&
				is_uploaded_file( $_FILES['font-file']['tmp_name'] )
			) {
				$font_slug      = sanitize_title( $_POST['font-name'] );
				$file_extension = pathinfo( $_FILES['font-file']['name'], PATHINFO_EXTENSION );
				$file_name      = sanitize_title( $font_slug . '_' . $_POST['font-style'] . '_' . $_POST['font-weight'] ) . '.' . $file_extension;

				move_uploaded_file( $_FILES['font-file']['tmp_name'], get_stylesheet_directory() . '/assets/fonts/' . $file_name );

				$uploaded_font_face = array(
					'fontFamily' => $_POST['font-name'],
					'fontWeight' => $_POST['font-weight'],
					'fontStyle'  => $_POST['font-style'],
					'src'        => array(
						'file:./assets/fonts/' . $file_name,
					),
				);

				if ( ! empty( $_POST['font-variation-settings'] ) ) {
					// replace escaped single quotes with single quotes
					$font_variation_settings                     = str_replace( "\\'", "'", $_POST['font-variation-settings'] );
					$uploaded_font_face['fontVariationSettings'] = $font_variation_settings;
				}

				$new_font_faces = array( $uploaded_font_face );

				$this->add_or_update_theme_font_faces( $_POST['font-name'], $font_slug, $new_font_faces );

				// Add font license to readme.txt
				$this->manage_font_license( $_POST['font-name'], $file_name );

				return add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_embed_font_success' ) );
			}
			return add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_embed_font_file_error' ) );
		}
	}

	function get_font_slug( $name ) {
		$slug = sanitize_title( $name );
		$slug = preg_replace( '/\s+/', '', $slug ); // Remove spaces
		return $slug;
	}

	function save_google_fonts_to_theme() {
		if (
			! empty( $_POST['nonce'] ) &&
			wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) &&
			! empty( $_POST['selection-data'] ) &&
			$this->has_file_and_user_permissions()
		) {
			// Gets data from the form
			$data = json_decode( stripslashes( $_POST['selection-data'] ), true );

			foreach ( $data as $font_family ) {
				$google_font_name = $font_family['family'];
				$font_slug        = $this->get_font_slug( $google_font_name );
				$variants         = $font_family['faces'];
				$new_font_faces   = array();
				foreach ( $variants as $variant ) {
					// variant name is $variant_and_url[0] and font asset url is $variant_and_url[1]
					$file_extension = pathinfo( $variant['src'], PATHINFO_EXTENSION );
					$file_name      = sanitize_title( $font_slug . '_' . $variant['style'] . '_' . $variant['weight'] ) . '.' . $file_extension;

					// Download font asset in temp folder
					$temp_file = download_url( $variant['src'] );

					if ( $this->has_font_mime_type( $variant['src'] ) ) {

						// Move font asset to theme assets folder
						rename( $temp_file, get_stylesheet_directory() . '/assets/fonts/' . $file_name );

						// Add each variant as one font face
						$new_font_faces[] = array(
							'fontFamily' => $google_font_name,
							'fontStyle'  => $variant['style'],
							'fontWeight' => $variant['weight'],
							'src'        => array(
								'file:./assets/fonts/' . $file_name,
							),
						);
					}
				}

				$this->add_or_update_theme_font_faces( $google_font_name, $font_slug, $new_font_faces );

				// Add font license to readme.txt
				$this->manage_font_license( $font_family['family'], $file_name );
			}

			add_action( 'admin_notices', array( 'Font_Form_Messages', 'admin_notice_embed_font_success' ) );
		}
	}

	function replace_all_theme_font_families( $font_families ) {
		// Construct updated theme.json.
		$theme_json_raw = json_decode( file_get_contents( get_stylesheet_directory() . '/theme.json' ), true );
		// Overwrite the previous fontFamilies with the new ones.
		$theme_json_raw['settings']['typography']['fontFamilies'] = $font_families;

		$theme_json        = wp_json_encode( $theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$theme_json_string = preg_replace( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

		// Write the new theme.json to the theme folder
		file_put_contents(
			get_stylesheet_directory() . '/theme.json',
			$theme_json_string
		);
	}

	function add_or_update_theme_font_faces( $font_name, $font_slug, $font_faces ) {
		// Get the current theme.json and fontFamilies defined (if any).
		$theme_json_raw      = json_decode( file_get_contents( get_stylesheet_directory() . '/theme.json' ), true );
		$theme_font_families = isset( $theme_json_raw['settings']['typography']['fontFamilies'] ) ? $theme_json_raw['settings']['typography']['fontFamilies'] : null;

		$existent_family = $theme_font_families ? array_values(
			array_filter(
				$theme_font_families,
				function ( $font_family ) use ( $font_slug ) {
					return $font_family['slug'] === $font_slug; }
			)
		) : null;

		// Add the new font faces.
		if ( empty( $existent_family ) ) { // If the new font family doesn't exist in the theme.json font families, add it to the exising font families
			$theme_font_families[] = array(
				'fontFamily' => $font_name,
				'slug'       => $font_slug,
				'fontFace'   => $font_faces,
			);
		} else { // If the new font family already exists in the theme.json font families, add the new font faces to the existing font family
			$theme_font_families            = array_values(
				array_filter(
					$theme_font_families,
					function ( $font_family ) use ( $font_slug ) {
						return $font_family['slug'] !== $font_slug; }
				)
			);
			$existent_family[0]['fontFace'] = array_merge( $existent_family[0]['fontFace'], $font_faces );
			$theme_font_families            = array_merge( $theme_font_families, $existent_family );
		}

		// Overwrite the previous fontFamilies with the new ones.
		$theme_json_raw['settings']['typography']['fontFamilies'] = $theme_font_families;

		$theme_json        = wp_json_encode( $theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$theme_json_string = preg_replace( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

		// Write the new theme.json to the theme folder
		file_put_contents(
			get_stylesheet_directory() . '/theme.json',
			$theme_json_string
		);
	}

	function manage_font_license( $font_name, $file_name ) {
		if ( ! $font_name ) {
			return;
		}

		// Build end of credits note
		$end_credits_note = '-- End of ' . $font_name . ' Font credits --';

		// Get theme readme.txt
		$readme_file          = get_stylesheet_directory() . '/readme.txt';
		$readme_file_contents = file_get_contents( $readme_file );

		if ( ! $readme_file ) {
			return;
		}

		// If file_name and font-credits exist, then add font license to readme.txt
		if ( 'remove' !== $file_name && is_string( $file_name ) && ! empty( $_POST['font-credits'] ) && isset( $_POST['font-credits'] ) ) {
			// Check that the font is not already credited in readme.txt
			if ( false === stripos( $readme_file_contents, $font_name ) ) {
				// Get font credits from font file metadata
				$font_credits = json_decode( stripslashes( $_POST['font-credits'] ), true );

				if ( ! is_array( $font_credits ) ) {
					return;
				}

				// Assign font credits to variables
				$copyright    = array_key_exists( 'copyright', $font_credits ) ? trim( $font_credits['copyright'] ) : '';
				$license_info = array_key_exists( 'license', $font_credits ) ? "\n" . trim( $font_credits['license'] ) : '';
				$license_url  = array_key_exists( 'licenseURL', $font_credits ) ? "\n" . 'License URL: ' . trim( $font_credits['licenseURL'] ) : '';
				$font_source  = array_key_exists( 'source', $font_credits ) ? "\n" . 'Source: ' . trim( $font_credits['source'] ) : '';

				// Handle longer, multi-line license info content
				if ( is_string( $license_info ) ) {
					// Split license info at first new line
					$license_info = "\n" . strtok( $license_info, "\n" );

					// Prevent license info from being over 200 characters
					if ( strlen( $license_info ) > 200 ) {
						$license_info = substr( $license_info, 0, strrpos( substr( $license_info, 0, 200 ), ' ' ) ) . '...';
					}
				}

				// Build the font credits string
				$font_credits = "
{$font_name} Font
{$copyright} {$license_info} {$license_url} {$font_source}
{$end_credits_note}
";

				// Check if readme.txt ends with a new line
				if ( "\n" !== $readme_file_contents[ strlen( $readme_file_contents ) - 1 ] ) {
					$font_credits = "\n" . $font_credits;
				}

				// Add font credits to the end of readme.txt
				file_put_contents(
					$readme_file,
					$font_credits,
					FILE_APPEND
				);
			}
		}

		// If file_name is set to 'remove', then remove font license from readme.txt
		if ( 'remove' === $file_name ) {
			// Check if font credits are in readme.txt
			if ( false !== stripos( $readme_file_contents, $font_name ) ) {
				// Calculate the start and end positions of the font credits
				$font_name_strlength   = strlen( $font_name . ' Font' ) + 1;
				$end_credits_strlength = strlen( $end_credits_note ) + 1;
				$font_start            = stripos( $readme_file_contents, "\n" . $font_name . ' Font' ) + $font_name_strlength;
				$font_end              = stripos( $readme_file_contents, $end_credits_note, $font_start );

				// Check if the start and end positions are valid
				if ( false === $font_start || false === $font_end ) {
					return;
				}

				// Remove the font credits from readme.txt
				$removed_font_credits = substr_replace( $readme_file_contents, '', $font_start - $font_name_strlength, $font_end + $end_credits_strlength - $font_start + $font_name_strlength );
				file_put_contents( $readme_file, $removed_font_credits );
			}
		}
	}
}


