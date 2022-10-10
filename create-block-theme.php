<?php

/**
 * @wordpress-plugin
 * Plugin Name: Create Block Theme
 * Plugin URI: https://wordpress.org/plugins/create-block-theme
 * Description: Generates a block theme
 * Version: 1.2.3
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

function get_custom_template() {
	$plugin = 'plugin-name';
	$slug = 'custom-template';
	$template_id = $plugin . '//' . $slug;
	$title = 'template-title';
	$description = 'description';
	$content = '<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"layout":{"inherit":true}} --><div class="wp-block-group"><!-- wp:woocommerce/legacy-template {"template":"single-product"} /--></div><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer","className":"site-footer-container"} /-->';

	$template                 = new WP_Block_Template();
	$template->id             = $template_id;
	$template->theme          = $plugin;
	$template->content        = $content;
	$template->slug           = $slug;
	$template->source         = 'plugin';
	$template->type           = 'wp_template';
	$template->title          = $title;
	$template->status         = 'publish';
	$template->has_theme_file = false;
	$template->is_custom      = true;
	$template->description    = $description;
	return $template;
}

function add_block_templates( $query_result, $query, $template_type ) {
	if ( empty( $query ) && $template_type === 'wp_template' ) {
		$query_result[] = get_custom_template();
	}
	return $query_result;
}
add_filter( 'get_block_templates', 'add_block_templates', 10, 3 );

function add_block_template( $block_template, $id, $template_type ) {
	$custom_template = get_custom_template();

	if ( empty( $block_template ) && $template_type === 'wp_template' && $id === $custom_template->id ) {
		$block_template = $custom_template;
	}

	return $block_template;
}

add_filter( 'get_block_template', 'add_block_template', 10, 3 );
