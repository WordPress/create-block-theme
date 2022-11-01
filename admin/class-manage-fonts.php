<?php

require_once (__DIR__ . '/font-helpers.php');

class Manage_Fonts_Admin {

	public function __construct() {
        add_action( 'admin_menu', [ $this, 'create_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'save_google_fonts_to_theme' ] );
        add_action( 'admin_init', [ $this, 'save_local_fonts_to_theme' ] );
        add_action( 'admin_init', [ $this, 'save_manage_fonts_changes' ] );
	}

    const ALLOWED_FONT_MIME_TYPES = array(
        'ttf'   => 'font/ttf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
    );

    function has_font_mime_type( $file ) {
        $filetype = wp_check_filetype( $file, self::ALLOWED_FONT_MIME_TYPES );
        return in_array( $filetype['type'], self::ALLOWED_FONT_MIME_TYPES );
    }    

    function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$manage_fonts_page_title=_x('Manage theme fonts', 'UI String', 'create-block-theme');
        $manage_fonts_menu_title=_x('Manage theme fonts', 'UI String', 'create-block-theme');
        add_theme_page( $manage_fonts_page_title, $manage_fonts_menu_title, 'edit_theme_options', 'manage-fonts', [ $this, 'manage_fonts_admin_page' ] );

        $google_fonts_page_title=_x('Embed Google font in current Theme', 'UI String', 'create-block-theme');
		$google_fonts_menu_title=_x('Embed Google font in current Theme', 'UI String', 'create-block-theme');
        add_submenu_page(null, $google_fonts_page_title, $google_fonts_menu_title, 'edit_theme_options', 'add-google-font-to-theme-json', [ $this, 'google_fonts_admin_page' ] );

		$local_fonts_page_title=_x('Embed local font in current Theme', 'UI String', 'create-block-theme');
		$local_fonts_menu_title=_x('Embed local font in current Theme', 'UI String', 'create-block-theme');
		add_submenu_page(null, $local_fonts_page_title, $local_fonts_menu_title, 'edit_theme_options', 'add-local-font-to-theme-json', [ $this, 'local_fonts_admin_page' ] );
	}

    function can_read_and_write_font_assets_directory () {
		// Create the font assets folder if it doesn't exist
        $temp_dir = get_temp_dir();
		$assets_path = get_stylesheet_directory() . '/assets';
		$font_assets_path = $assets_path . '/fonts';
		if ( ! is_dir( $assets_path ) ) {
			mkdir( $assets_path, 0755 );
		}
		if ( ! is_dir( $font_assets_path ) ) {
			mkdir( $font_assets_path, 0755 );
		}

		// If the font asset folder can't be written return an error
		if ( ! is_writable( $font_assets_path ) || ! is_readable( $font_assets_path ) || !is_writable  ( $temp_dir ) ) {
            return false;
		}
        return true;
	}


    function manage_fonts_admin_page () {
        // Load the required WordPress packages.
        // Automatically load imported dependencies and assets version.
        $asset_file = include plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
     
        // Enqueue CSS dependencies.
        foreach ( $asset_file['dependencies'] as $style ) {
            wp_enqueue_style( $style );
        }
     
        // Load our app.js.
        wp_enqueue_script( 'create-block-theme-app', plugins_url( 'build/index.js', __DIR__ ), $asset_file['dependencies'], $asset_file['version'] );

        wp_enqueue_style( 'manage-fonts-styles',  plugin_dir_url( __DIR__ ) . '/css/manage-fonts.css', array(), '1.0', false );

        $theme_name = wp_get_theme()->get( 'Name' );

        $theme_data = WP_Theme_JSON_Resolver::get_theme_data();
        $theme_settings = $theme_data->get_settings();
        $theme_font_families = $theme_settings['typography']['fontFamilies']['theme'];

        // This is only run when Gutenberg is not active because WordPress core does not include WP_Webfonts class yet. So we can't use it to load the font asset styles.
        // See the comments here: https://github.com/WordPress/WordPress/blob/88cee0d359f743f94597c586febcc5e09830e780/wp-includes/script-loader.php#L3160-L3186
        // TODO: remove this when WordPress core includes WP_Webfonts class.
        if ( ! class_exists( 'WP_Webfonts' ) ) {
            $font_assets_stylesheet = render_font_styles($theme_font_families);
            wp_register_style( 'theme-font-families', false );
            wp_add_inline_style( 'theme-font-families', $font_assets_stylesheet );
            wp_enqueue_style( 'theme-font-families' );
        }

        if ( ! empty( $_POST['new-theme-fonts-json'] ) ) {
            $theme_font_families = json_decode( stripslashes( $_POST['new-theme-fonts-json'] ), true );
        }

        $fonts_json = wp_json_encode( $theme_font_families );
        $fonts_json_string = preg_replace ( '~(?:^|\G)\h{4}~m', "\t", $fonts_json );
        
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Manage Theme Fonts', 'create-block-theme'); ?></h1>
        <a href="<?php echo admin_url( 'themes.php?page=add-google-font-to-theme-json' ); ?>" class="page-title-action"><?php _e('Add Google Font', 'create-block-theme'); ?></a>
        <a href="<?php echo admin_url( 'themes.php?page=add-local-font-to-theme-json' ); ?>" class="page-title-action"><?php _e('Add Local Font', 'create-block-theme'); ?></a>
        <hr class="wp-header-end" />
        <p name="theme-fonts-json" id="theme-fonts-json" class="hidden"><?php echo $fonts_json_string;  ?></p>
        
        <form method="POST"  id="manage-fonts-form">
            <div id="manage-fonts"></div>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
        </form>

    </div>
    <?php
    }

    function local_fonts_admin_page () {
        wp_enqueue_script('inflate', plugin_dir_url(__FILE__) . 'js/lib/inflate.js', array( ), '1.0', false );
        wp_enqueue_script('unbrotli', plugin_dir_url(__FILE__) . 'js/lib/unbrotli.js', array( ), '1.0', false );
        wp_enqueue_script('lib-font-browser', plugin_dir_url(__FILE__) . 'js/lib/lib-font.browser.js', array( ), '1.0', false );
        wp_enqueue_script('embed-local-font', plugin_dir_url(__FILE__) . 'js/embed-local-font.js', array( ), '1.0', false );


        function add_type_attribute($tag, $handle, $src) {
            // if not your script, do nothing and return original $tag
            if ( 'embed-local-font' !== $handle && 'lib-font-browser' !== $handle ) {
                return $tag;
            }
            // change the script tag by adding type="module" and return it.
            $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
            return $tag;
        }

        add_filter('script_loader_tag', 'add_type_attribute', 10, 3);
        ?>
        <div class="wrap local-fonts-page">
            <h2><?php _ex('Add local fonts to your theme', 'UI String', 'create-block-theme'); ?></h2>
            <h3><?php printf( esc_html__('Add local fonts assets and font face definitions to your current active theme (%1$s)', 'create-block-theme'),  esc_html( wp_get_theme()->get('Name') ) ); ?></h3>
            <form enctype="multipart/form-data" action="" method="POST">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="font-file"><?php _e('Font file', 'create-block-theme'); ?></label>
                                <br>
                                <small style="font-weight:normal;"><?php _e('.ttf, .woff, .woff2 file extensions supported', 'create-block-theme'); ?></small>
                            </th>
                            <td>
                                <input type="file" accept=".ttf, .woff, .woff2"  name="font-file" id="font-file" class="upload" required/>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Font face definition for this font file:', 'create-block-theme'); ?></th>
                            <td>
                                <hr/>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="font-name"><?php _e('Font name', 'create-block-theme'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="font-name" id="font-name" placeholder="<?php _e('Font name', 'create-block-theme'); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="font-style"><?php _e('Font style', 'create-block-theme'); ?></label>
                            </th>
                            <td>
                                <select name="font-style" id="font-style" required>
                                    <option value="normal">Normal</option>
                                    <option value="italic">Italic</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="font-weight"><?php _e('Font weight', 'create-block-theme'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="font-weight" id="font-weight" placeholder="<?php _e('Font weight', 'create-block-theme'); ?>" required>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <input type="submit" value="<?php _e('Upload local fonts to your theme', 'create-block-theme'); ?>" class="button button-primary" />
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
            </form>
        </div>

<?php
    }

    function google_fonts_admin_page() {
		wp_enqueue_script('google-fonts-script', plugin_dir_url(__FILE__) . 'js/google-fonts.js', array( ), '1.0', false );
		wp_enqueue_style('google-fonts-styles',  plugin_dir_url( __DIR__ ) . '/css/google-fonts.css', array(), '1.0', false );
?>
		<div class="wrap google-fonts-page">
			<h2><?php _ex('Add Google fonts to your theme', 'UI String', 'create-block-theme'); ?></h2>
			<form enctype="multipart/form-data" action="" method="POST">
				<h3><?php printf( esc_html__('Add Google fonts assets and font face definitions to your current active theme (%1$s)', 'create-block-theme'),  esc_html( wp_get_theme()->get('Name') ) ); ?></h3>
				<label for="google-font-id"><?php printf( esc_html__('Select Font', 'create-block-theme')); ?></label>
				<select name="google-font" id="google-font-id">
                    <option value=""><?php _e('Select a font...', 'create-block-theme'); ?></option>
				</select>
				<br /><br />
				<p class="hint"><?php _e('Select the font variants you want to include:', 'create-block-theme'); ?></p>
				<table class="wp-list-table widefat fixed striped table-view-list" id="google-fonts-table">
					<thead>
						<tr>
							<td class=""><input type="checkbox" id="select-all-variants" /></td>
							<td class=""><?php printf( esc_html__('Variant', 'create-block-theme')); ?></td>
							<td class=""><?php printf( esc_html__('Preview', 'create-block-theme')); ?></td>
						</tr>
					</thead>
					<tbody id="font-options">
					</tbody>
				</table>
				<br /><br />
				<input type="hidden" name="font-name" id="font-name" value="" />
				<input type="hidden" name="google-font-variants" id="google-font-variants" value="" />
				<input type="submit" value="<?php _e('Add google fonts to your theme', 'create-block-theme'); ?>" class="button button-primary" id="google-fonts-submit" disabled=true />
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
			</form>
		</div>
	<?php
	}

    function save_manage_fonts_changes () {
        if (
            current_user_can( 'edit_themes' ) &&
            ! empty( $_POST['nonce'] ) &&
            wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) &&
            ! empty( $_POST['new-theme-fonts-json'] )
        ) {
            if( ! $this->can_read_and_write_font_assets_directory() ) {
                return add_action( 'admin_notices', [ $this, 'admin_notice_manage_fonts_permission_error' ] );
            }

            // parse json from form 
            $new_theme_fonts_json = json_decode( stripslashes( $_POST['new-theme-fonts-json'] ), true );

            $this->replace_all_theme_font_families( $new_theme_fonts_json );

            add_action( 'admin_notices', [ $this, 'admin_notice_delete_font_success' ] );
        }
    }

    function save_local_fonts_to_theme () {
        if (
            current_user_can( 'edit_themes' ) &&
            ! empty( $_POST['nonce'] ) &&
            wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) &&
            ! empty( $_FILES['font-file'] ) &&
            ! empty( $_POST['font-name'] ) &&
            ! empty( $_POST['font-style'] ) &&
            ! empty( $_POST['font-weight'] )
        ) {
            if (
                $this->has_font_mime_type( $_FILES['font-file']['name'] ) &&
                is_uploaded_file($_FILES['font-file']['tmp_name'])
            ) {
                $font_slug = sanitize_title( $_POST['font-name'] );
                $file_extension = pathinfo( $_FILES['font-file']['name'], PATHINFO_EXTENSION );
                $file_name = $font_slug . '_' . $_POST['font-style'] . '_' . $_POST['font-weight'] . '.' . $file_extension;

                if( ! $this->can_read_and_write_font_assets_directory() ) {
                    return add_action( 'admin_notices', [ $this, 'admin_notice_embed_font_permission_error' ] );
                }

                move_uploaded_file( $_FILES['font-file']['tmp_name'], get_stylesheet_directory() . '/assets/fonts/' . $file_name );

                $new_font_faces = array();
                $new_font_faces[] = array (
                    'fontFamily' => $_POST['font-name'],
                    'fontStyle'  => $_POST['font-style'],
                    'fontWeight' => $_POST['font-weight'],
                    'src' => array (
                        'file:./assets/fonts/'.$file_name
                    ),
                );

                $this->add_or_update_theme_font_faces ( $_POST['font-name'], $font_slug, $new_font_faces );
                return add_action( 'admin_notices', [ $this, 'admin_notice_embed_font_success' ] );
            }
            return add_action( 'admin_notices', [ $this, 'admin_notice_embed_font_file_error' ] );
        }
    }

    function save_google_fonts_to_theme () {
        if (
            current_user_can( 'edit_themes' ) &&
            ! empty( $_POST['nonce'] ) &&
            wp_verify_nonce( $_POST['nonce'], 'create_block_theme' ) &&
            ! empty( $_POST['google-font-variants'] ) &&
            ! empty( $_POST['font-name'] )
        ) {
            if( ! $this->can_read_and_write_font_assets_directory() ) {
                return add_action( 'admin_notices', [ $this, 'admin_notice_embed_font_permission_error' ] );
            }

            // Gets data from the form
            $google_font_name = $_POST['font-name'];
            $font_slug = sanitize_title( $google_font_name );
            $google_font_variants = $_POST['google-font-variants'];
            $variants = explode(',', $google_font_variants);

            $new_font_faces = array();
            foreach ($variants as $variant) {
                // variant name is $variant_and_url[0] and font asset url is $variant_and_url[1]
                $variant_and_url = explode ('::', $variant);
                $file_extension = pathinfo($variant_and_url[1], PATHINFO_EXTENSION);
                $file_name = $font_slug.'_'.$variant_and_url[0].'.'.$file_extension;

                // Download font asset in temp folder
                $temp_file = download_url( $variant_and_url[1] );

                if ( $this->has_font_mime_type( $variant_and_url[1] ) ) {

                    // Move font asset to theme assets folder
                    rename($temp_file, get_stylesheet_directory() . '/assets/fonts/' . $file_name);

                    // Get the font style and weight
                    $variant_style  = str_contains($variant_and_url[0], 'italic') ? 'italic' : 'normal';
                    $variant_weight = ($variant_and_url[0] === 'regular' || $variant_and_url[0] === 'italic') ? '400' : $variant_and_url[0];
                    $variant_weight = str_replace('italic', '', $variant_weight);

                    // Add each variant as one font face
                    $new_font_faces[] = array(
                        'fontFamily' => $google_font_name,
                        'fontStyle'  => $variant_style,
                        'fontWeight' => $variant_weight,
                        'src' => array(
                            'file:./assets/fonts/'.$file_name
                        ),
                    );
                }
            }

            $this->add_or_update_theme_font_faces ( $google_font_name, $font_slug, $new_font_faces );

            add_action( 'admin_notices', [ $this, 'admin_notice_embed_font_success' ] );
        }
    }

    function replace_all_theme_font_families ( $font_families ) {
        // Get the current Theme.json data
        $theme_data = WP_Theme_JSON_Resolver::get_theme_data();
        $theme_settings = $theme_data->get_settings();
        $theme_font_families = $theme_settings['typography']['fontFamilies']['theme'];

        $new_theme_json_content = array(
            'version'  => class_exists( 'WP_Theme_JSON_Gutenberg' ) ? WP_Theme_JSON_Gutenberg::LATEST_SCHEMA : WP_Theme_JSON::LATEST_SCHEMA,
            'settings' => array(
                'typography' => array (
                    'fontFamilies' => $font_families
                )
            )
        );


        // Creates the new theme.json file
        if ( class_exists( 'WP_Theme_JSON_Gutenberg' ) ) {
            $new_json = new WP_Theme_JSON_Gutenberg( $new_theme_json_content );
            $result = new WP_Theme_JSON_Gutenberg();
        } else {
            $new_json = new WP_Theme_JSON( $new_theme_json_content );
            $result = new WP_Theme_JSON();
        }
        $result->merge( $theme_data );
        $result->merge( $new_json );

        $data = $result->get_data();
        $theme_json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        $theme_json_string = preg_replace ( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

        // Write the new theme.json to the theme folder
        file_put_contents(
            get_stylesheet_directory() . '/theme.json',
            $theme_json_string
        );
    }

    function add_or_update_theme_font_faces ( $font_name, $font_slug, $font_faces ) {
        // Get the current Theme.json data
        $theme_data = WP_Theme_JSON_Resolver::get_theme_data();
        $theme_settings = $theme_data->get_settings();
        $theme_font_families = $theme_settings['typography']['fontFamilies']['theme'];

        $existent_family =  array_values(
            array_filter (
                $theme_font_families ,
                function ($font_family) use ($font_slug) { return $font_family['slug']  === $font_slug; }
            )
        );

        // Add the new font faces to the theme.json
        if ( empty( $existent_family ) ) { // If the new font family doesn't exist in the theme.json font families, add it to the exising font families
            $theme_font_families[] = array(
                'fontFamily' => $font_name,
                'slug' => $font_slug,
                'fontFace' => $font_faces,
            );
        } else { // If the new font family already exists in the theme.json font families, add the new font faces to the existing font family
            $theme_font_families = array_values(
                array_filter (
                    $theme_font_families,
                    function ($font_family)  use ($font_slug) { return $font_family['slug']  !== $font_slug; }
                )
            );
            $existent_family[0]['fontFace'] = array_merge($existent_family[0]['fontFace'], $font_faces);
            $theme_font_families = array_merge($theme_font_families, $existent_family);
        }

        $new_theme_json_content = array(
            'version'  => class_exists( 'WP_Theme_JSON_Gutenberg' ) ? WP_Theme_JSON_Gutenberg::LATEST_SCHEMA : WP_Theme_JSON::LATEST_SCHEMA,
            'settings' => array(
                'typography' => array (
                    'fontFamilies' => $theme_font_families
                )
            )
        );

        // Creates the new theme.json file
        if ( class_exists( 'WP_Theme_JSON_Gutenberg' ) ) {
            $new_json = new WP_Theme_JSON_Gutenberg( $new_theme_json_content );
            $result = new WP_Theme_JSON_Gutenberg();
        } else {
            $new_json = new WP_Theme_JSON( $new_theme_json_content );
            $result = new WP_Theme_JSON();
        }
        $result->merge( $theme_data );
        $result->merge( $new_json );

        $data = $result->get_data();
        $theme_json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        $theme_json_string = preg_replace ( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

        // Write the new theme.json to the theme folder
        file_put_contents(
            get_stylesheet_directory() . '/theme.json',
            $theme_json_string
        );

    }

    function admin_notice_embed_font_success () {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-success is-dismissible">
				<p>
                    <?php printf( esc_html__( '%1$s font added to %2$s theme. ', 'create-block-theme' ), esc_html( $_POST['font-name'] ), esc_html( $theme_name ) ); ?>
                    <a href="themes.php?page=manage-fonts"><?php printf( esc_html__( "Manage Fonts", "create-block-theme" ) ); ?></a>
                </p>
			</div>
		<?php
	}

	function admin_notice_embed_font_permission_error () {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error adding %1$s font to %2$s theme. WordPress lack permissions to write the font assets.', 'create-block-theme' ), esc_html( $_POST['font-name'] ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

    function admin_notice_embed_font_file_error () {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error adding %1$s font to %2$s theme. The uploaded file is not valid.', 'create-block-theme' ), esc_html( $_POST['font-name'] ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

    function admin_notice_manage_fonts_permission_error () {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error handling font changes. WordPress lack permissions to manage the theme font assets.', 'create-block-theme' ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

    function admin_notice_delete_font_success () {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php printf( esc_html__( 'Font removed from your theme (%1$s).', 'create-block-theme' ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}
}

?>
