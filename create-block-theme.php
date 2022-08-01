<?php

/**
 * @wordpress-plugin
 * Plugin Name: Create Block Theme
 * Plugin URI: https://wordpress.org/plugins/create-block-theme
 * Description: Generates a block theme
 * Version: 1.13.1
 * Author: WordPress.org
 * Author URI: https://wordpress.org/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: create-block-theme
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include file with download_url() if function doesn't exist.
if ( ! function_exists( 'download_url' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

/**
 * The core plugin class.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-create-block-theme.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.2
 */
function run_create_block_theme() {
	$plugin = new Create_Block_Theme();
	$plugin->run();

}
run_create_block_theme();

function create_variation( $data ) {
	$plugin_admin = new Create_Block_Theme_Admin();
	if ( is_child_theme() ) {
		$plugin_admin->save_variation( 'current', $data );
	}
	else {
		$plugin_admin->save_variation( 'all', $data );
	}
	$plugin_admin->clear_user_customizations();

	$result = new stdClass();
	$result->variation = $data['variation'];
	$res = new WP_REST_Response( $result );
	$res->set_status( 200 );
	return [ 'req' => $res ];
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'create-block-theme/v1', '/variation/(?P<variation>[a-z0-9-]++)', array(
		'methods' => 'GET', // TODO - should be POST
		'callback' => 'create_variation',
		'permission_callback' => function () {
			return current_user_can( 'edit_others_posts' );
		},
		'args' => array(
			'variation' => array(
			  'validate_callback' => function($param, $request, $key) {;
				return is_string( $param );
			  }
			),
		),
	) );
} );

function create_blank_theme( $data ) {
	$plugin_admin = new Create_Block_Theme_Admin();
	$plugin_admin->create_blank_theme( $data );
	$result = new stdClass();
	$result->name = $data['name'];
	$res = new WP_REST_Response( $result );
	$res->set_status( 200 );
	return [ 'req' => $res ];

}

add_action( 'rest_api_init', function () {
	register_rest_route( 'create-block-theme/v1', '/blank-theme', array(
		'methods' => 'POST',
		'callback' => 'create_blank_theme',
		'permission_callback' => function () {
			return current_user_can( 'edit_others_posts' );
		},
		'args' => array(
			'name' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			),
			'description' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			),
			'uri' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			),
			'author' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			),
			'author_uri' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_string( $param );
				}
			),
		),
	) );
} );




function create_block_theme_enqueue() {
	$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php');

	wp_register_script(
		'create-block-theme-slot-fill',
		plugins_url( 'build/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version']
	);
	wp_enqueue_script(
		'create-block-theme-slot-fill',
	);
}
add_action( 'enqueue_block_editor_assets', 'create_block_theme_enqueue' );
