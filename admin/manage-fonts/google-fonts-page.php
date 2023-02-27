<?php

require_once (__DIR__ . '/fonts-page.php');

class Google_Fonts {
    public static function google_fonts_admin_page() {
        Fonts_Page::load_fonts_react_app();
        ?>
        <input id="nonce" type="hidden" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
        <div id="fonts-app"></div>

	    <?php
	}
}
