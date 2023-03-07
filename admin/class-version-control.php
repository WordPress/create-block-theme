<?php

require_once (__DIR__ . '/class-create-block-theme-admin.php');

class Version_Control_Admin extends Create_Block_Theme_Admin {
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
        $version = '1';
        $namespace = 'create-block-theme/v' . $version;

		register_rest_route( $namespace, '/themes', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_all_themes'),
		));

		register_rest_route( $namespace, '/theme-status', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_changes'),
		));

		register_rest_route( $namespace, '/pullrequest', array(
			'methods' => 'POST',
			'callback' => array($this, 'create_pull_request'),
		));
	}

	function get_all_themes() {
		$themes = wp_get_themes();
        $current_theme = get_current_theme();
		$theme_names = array();

		foreach ($themes as $theme) {
            $is_current = $current_theme === $theme->name ? true : false;
			$theme_names[] = array( 
				'name' => $theme->name,
                'slug' => $theme->slug,
                'template' => $theme->template,
				'slug' => str_replace( ' ', '-', strtolower( $theme->name )),
                'isCurrent' => $is_current
			);
		}
		return rest_ensure_response($theme_names);
	}

    function get_changes() {
        // Save any changes to the theme itself.
        $this->save_theme_locally( 'all' );

        $current_theme = get_current_theme();
        // Report if changes exist.
        $cmd = sprintf(
            'cd ~/Local\ Sites/gutenbergtest/app/public/wp-content/themes/themes/%1$s && 
            git status', $current_theme );
        exec( $cmd, $output );

        // Restore to previous state.
        $cmd = sprintf(
            'cd ~/Local\ Sites/gutenbergtest/app/public/wp-content/themes/themes/%1$s && 
            git stash', $current_theme );
        exec( $cmd );

        return rest_ensure_response( array(
            'status' => $output,
            'current_theme' => $current_theme
        ));
    }

    function create_pull_request( $request ) {
        $parameters = $request->get_json_params();
        // TODO: Use the commit message from the form in the branch name.
        $commit_message = 'My test commit';
        $random_id = wp_rand();
        $branch = 'update/' . $parameters->theme_slug . $random_id;

        // Check out the new branch and create a commit.
        $cmd = sprintf( 
            'cd ~/Local\ Sites/gutenbergtest/app/public/wp-content/themes/themes/%1$s &&
            git checkout -b \'%2$s\'
            git stash apply &&
            git add . &&
            git commit -m \'%3$s\' &&
            git push origin \'%2$s\' > /dev/null &&
            gh pr create --repo=automattic/themes --web > /dev/null &&
            git checkout trunk', $theme_slug, $branch, $commit_message );
        exec( $cmd, $output, $ret_val );

        // Return the URL to the PR.
        return rest_ensure_response(array(
            'slug' => $theme_slug,
            'template' => $template,
            'output' => $output,
            'return' => $ret_val,
            'pr' => sprintf( 'https://github.com/automattic/themes/compare/trunk...%s?body=&expand=1', urlencode( $branch ) )
        ));
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