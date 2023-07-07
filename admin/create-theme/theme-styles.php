<?php

require_once( __DIR__ . '/theme-tags.php' );

class Theme_Styles {

	/**
	 * Update a style CSS file with given values
	 */
	public static function update_style_css( $style_css, $theme ) {

		$current_theme = wp_get_theme();
		$css_contents  = trim( substr( $style_css, strpos( $style_css, '*/' ) + 2 ) );
		$name          = $current_theme->get( 'Name' );
		$description   = stripslashes( $theme['description'] );
		$uri           = $theme['uri'];
		$author        = stripslashes( $theme['author'] );
		$author_uri    = $theme['author_uri'];
		$wp_version    = get_bloginfo( 'version' );
		$version       = $theme['version'];
		$requires_php  = $current_theme->get( 'RequiresPHP' );
		$template      = $current_theme->get( 'Template' );
		$text_domain   = $current_theme->get( 'TextDomain' );

		//TODO: These items don't seem to be available via ->get('License') calls
		$license      = 'GNU General Public License v2 or later';
		$license_uri  = 'http://www.gnu.org/licenses/gpl-2.0.html';
		$tags         = Theme_Tags::theme_tags_list( $theme );
		$css_metadata = "/*
Theme Name: {$name}
Theme URI: {$uri}
Author: {$author}
Author URI: {$author_uri}
Description: {$description}
Requires at least: 6.0
Tested up to: {$wp_version}
Requires PHP: {$requires_php}
Version: {$version}
License: {$license}
License URI: {$license_uri}
";

		if ( ! empty( $template ) ) {
			$css_metadata .= "Template: {$template}\n";
		}

		$css_metadata .= "Text Domain: {$text_domain}
Tags: {$tags}
*/

";
		return $css_metadata . $css_contents;
	}

	/**
	 * Build a style.css file for CHILD/GRANDCHILD themes.
	 */
	public static function build_child_style_css( $theme ) {
		$name        = stripslashes( $theme['name'] );
		$description = stripslashes( $theme['description'] );
		$uri         = $theme['uri'];
		$author      = stripslashes( $theme['author'] );
		$author_uri  = $theme['author_uri'];
		$wp_version  = get_bloginfo( 'version' );
		$template    = $theme['template'];
		$text_domain = $theme['text_domain'];
		$version     = '1.0.0';
		$tags        = Theme_Tags::theme_tags_list( $theme );

		if ( isset( $theme['version'] ) ) {
			$version = $theme['version'];
		}

		$style_css = "/*
Theme Name: {$name}
Theme URI: {$uri}
Author: {$author}
Author URI: {$author_uri}
Description: {$description}
Requires at least: 6.0
Tested up to: {$wp_version}
Requires PHP: 5.7
Version: {$version}
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
";

		if ( ! empty( $template ) ) {
			$style_css .= "Template: {$template}\n";
		}

		$style_css .= "Text Domain: {$text_domain}
Tags: {$tags}
*/

";

		return $style_css;
	}

	public static function clear_user_styles_customizations() {
		// Clear all values in the user theme.json
		$user_custom_post_type_id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$global_styles_controller = new WP_REST_Global_Styles_Controller();
		$update_request           = new WP_REST_Request( 'PUT', '/wp/v2/global-styles/' );
		$update_request->set_param( 'id', $user_custom_post_type_id );
		$update_request->set_param( 'settings', array() );
		$update_request->set_param( 'styles', array() );
		$updated_global_styles = $global_styles_controller->update_item( $update_request );
		delete_transient( 'global_styles' );
		delete_transient( 'global_styles_' . get_stylesheet() );
		delete_transient( 'gutenberg_global_styles' );
		delete_transient( 'gutenberg_global_styles_' . get_stylesheet() );
	}
}
