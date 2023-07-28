<?php

class React_App {
	public static function bootstrap() {
		// Load the required WordPress packages.
		// Automatically load imported dependencies and assets version.
		$asset_file = include plugin_dir_path( __DIR__ ) . 'build/index.asset.php';

		// Enqueue CSS dependencies of the scripts included in the build.
		foreach ( $asset_file['dependencies'] as $style ) {
			wp_enqueue_style( $style );
		}

		// Enqueue CSS of the app
		wp_enqueue_style( 'create-block-theme-app', plugins_url( 'build/index.css', __DIR__ ), array(), $asset_file['version'] );

		// Load our app.js.
		array_push( $asset_file['dependencies'], 'wp-i18n' );
		wp_enqueue_script( 'create-block-theme-app', plugins_url( 'build/index.js', __DIR__ ), $asset_file['dependencies'], $asset_file['version'] );

		// Enable localization in the app.
		wp_set_script_translations( 'create-block-theme-app', 'create-block-theme' );

		// Set google fonts json file url.
		wp_localize_script(
			'create-block-theme-app',
			'createBlockTheme',
			array(
				'googleFontsDataUrl' => plugins_url( 'assets/google-fonts/fallback-fonts-list.json', __DIR__ ),
				'adminUrl'           => admin_url(),
				'themeUrl'           => get_stylesheet_directory_uri(),
			)
		);
	}
}
