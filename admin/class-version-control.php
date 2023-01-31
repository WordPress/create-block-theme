<?php

class Version_Control_Admin {
	public function __construct() {
        add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ));
	}

    function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		$version_control_page_title = _x('Submit Theme | Create Block Theme', 'ui string', 'create-block-theme');
        $version_control_menu_title = _x('Submit Theme', 'ui string', 'create-block-theme');
        add_theme_page( $version_control_page_title, $version_control_menu_title, 'edit_theme_options', 'version-control', [ $this, 'version_control_admin_page' ] );
	}
	
	function register_routes(){
		register_rest_route('create-block-theme/v1', '/themes', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_all_themes'),
		));
	}

	function get_all_themes() {
		$themes = wp_get_themes();
		$theme_names = array();

		foreach ($themes as $theme) {
			$theme_names[] = array( 
				'name' => $theme->name,
				'slug' => $theme->template,
			);
		}
		return rest_ensure_response($theme_names);
	}


    function version_control_admin_page () {
        // Load the required WordPress packages.
        // Automatically load imported dependencies and assets version.
        $asset_file = include plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
     
        // Enqueue CSS dependencies.
        foreach ( $asset_file['dependencies'] as $style ) {
            wp_enqueue_style( $style );
        }
     
        // Load our app.js.
        array_push( $asset_file['dependencies'], 'wp-i18n' );
        wp_enqueue_script( 'create-block-theme-app', plugins_url( 'build/index.js', __DIR__ ), $asset_file['dependencies'], $asset_file['version'] );

        // wp_enqueue_style( 'manage-fonts-styles',  plugin_dir_url( __DIR__ ) . '/css/manage-fonts.css', array(), '1.0', false );

        $theme_name = wp_get_theme()->get( 'Name' );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Submit Theme', 'create-block-theme'); ?></h1>
        <form method="POST"  id="manage-fonts-form">
            <div id="app-container">
			</div>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
        </form>
    </div>
    <?php
    }

}