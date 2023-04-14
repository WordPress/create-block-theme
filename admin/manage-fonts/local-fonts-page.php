<?php

require_once( dirname( __DIR__ ) . '/class-react-app.php' );

class Local_Fonts {
	public static function local_fonts_admin_page() {
		// JS dependencies needed to read the file data from .woff and .woff2 files. (no needed for .ttf files)
		wp_enqueue_script( 'inflate', plugin_dir_url( dirname( __FILE__ ) ) . 'js/lib/inflate.js', array(), '', false );
		wp_enqueue_script( 'unbrotli', plugin_dir_url( dirname( __FILE__ ) ) . 'js/lib/unbrotli.js', array(), '', false );

		React_App::bootstrap();

		?>
		<input id="nonce" type="hidden" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
		<div id="create-block-theme-app"></div>

		<?php
	}
}
