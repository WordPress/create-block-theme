<?php

class Fonts_Page {
    public static function manage_fonts_admin_page () {
        self::load_fonts_react_app();

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

        $fonts_json = wp_json_encode( $theme_font_families );
        $fonts_json_string = preg_replace ( '~(?:^|\G)\h{4}~m', "\t", $fonts_json );
        
    ?>
    <div class="wrap">
        <div class="manage-fonts-header-flex">
            <h1 class="wp-heading-inline"><?php _e('Manage Theme Fonts', 'create-block-theme'); ?></h1>
            <a href="<?php echo admin_url( 'themes.php?page=add-google-font-to-theme-json' ); ?>" class="components-button page-title-action"><?php _e('Add Google Font', 'create-block-theme'); ?></a>
            <a href="<?php echo admin_url( 'themes.php?page=add-local-font-to-theme-json' ); ?>" class="components-button page-title-action"><?php _e('Add Local Font', 'create-block-theme'); ?></a>
        </div>
        <hr class="wp-header-end" />
        <p name="theme-fonts-json" id="theme-fonts-json" class="hidden"><?php echo $fonts_json_string;  ?></p>
        
        <form method="POST"  id="manage-fonts-form">
            <div id="fonts-app"></div>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
        </form>

    </div>
    <?php
    }

    public static function load_fonts_react_app () {
        // Load the required WordPress packages.
        // Automatically load imported dependencies and assets version.
        $asset_file = include plugin_dir_path( dirname(__DIR__) ) . 'build/index.asset.php';
     
        // Enqueue CSS dependencies of the scripts included in the build.
        foreach ( $asset_file['dependencies'] as $style ) {
            wp_enqueue_style( $style );
        }

        // Enqueue CSS of the app
        wp_enqueue_style( 'fonts-app', plugins_url( 'build/index.css', dirname(__DIR__) ), array(), $asset_file['version'] );

        // Load our app.js.
        array_push( $asset_file['dependencies'], 'wp-i18n' );
        wp_enqueue_script( 'create-block-theme-app', plugins_url( 'build/index.js', dirname(__DIR__) ), $asset_file['dependencies'], $asset_file['version'] );

        // Set google fonts json file url.
        wp_localize_script('create-block-theme-app', 'createBlockTheme', array(
            'googleFontsDataUrl' => plugins_url( 'assets/google-fonts/fallback-fonts-list.json', dirname(__DIR__) ),
        ));
    }
}
