<?php

require_once( dirname( __DIR__ ) . '/class-react-app.php' );

class Google_Fonts {
	public static function google_fonts_admin_page() {
		React_App::bootstrap();
		?>
		<input id="nonce" type="hidden" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
		<div id="create-block-theme-app"></div>

		<?php
	}
}
