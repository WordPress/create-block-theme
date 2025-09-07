<?php

require_once( __DIR__ . '/theme-tags.php' );

class CBT_Theme_Styles {

	/**
	 * Update a style CSS file with given values
	 */
	public static function update_style_css( $style_css, $theme ) {

		$style_data = get_file_data(
			path_join( get_stylesheet_directory(), 'style.css' ),
			array(
				'License'    => 'License',
				'LicenseURI' => 'License URI',
			)
		);

		$current_theme = wp_get_theme();
		$css_contents  = trim( substr( $style_css, strpos( $style_css, '*/' ) + 2 ) );
		$name          = stripslashes( $theme['name'] );
		$description   = stripslashes( $theme['description'] );
		$uri           = $theme['uri'];
		$author        = stripslashes( $theme['author'] );
		$author_uri    = $theme['author_uri'];
		$wp_version    = CBT_Theme_Utils::get_current_wordpress_version();
		$requires_wp   = ( '' === $theme['requires_wp'] ) ? CBT_Theme_Utils::get_current_wordpress_version() : $theme['requires_wp'];
		$version       = $theme['version'];
		$requires_php  = $current_theme->get( 'RequiresPHP' );
		$text_domain   = $theme['slug'];
		$template      = $current_theme->get( 'Template' ) ? "\n" . 'Template: ' . $current_theme->get( 'Template' ) : '';
		$license       = $style_data['License'] ? $style_data['License'] : 'GNU General Public License v2 or later';
		$license_uri   = $style_data['LicenseURI'] ? $style_data['LicenseURI'] : 'http://www.gnu.org/licenses/gpl-2.0.html';
		$tags          = CBT_Theme_Tags::theme_tags_list( $theme );
		$css_contents  = $css_contents ? "\n\n" . $css_contents : '';
		$copyright     = '';
		preg_match( '/^\s*\n((?s).*?)\*\/\s*$/m', $style_css, $matches );
		if ( isset( $matches[1] ) ) {
			$copyright = "\n" . $matches[1];
		}

		return "/*
Theme Name: {$name}
Theme URI: {$uri}
Author: {$author}
Author URI: {$author_uri}
Description: {$description}
Requires at least: {$requires_wp}
Tested up to: {$wp_version}
Requires PHP: {$requires_php}
Version: {$version}
License: {$license}
License URI: {$license_uri}{$template}
Text Domain: {$text_domain}
Tags: {$tags}
{$copyright}*/{$css_contents}
";
	}

	/**
	 * Build a style.css file for CHILD/GRANDCHILD themes.
	 */
	public static function build_style_css( $theme ) {
		$name        = stripslashes( $theme['name'] );
		$description = stripslashes( $theme['description'] );
		$uri         = $theme['uri'];
		$author      = stripslashes( $theme['author'] );
		$author_uri  = $theme['author_uri'];
		$requires_wp = ( '' === $theme['requires_wp'] ) ? CBT_Theme_Utils::get_current_wordpress_version() : $theme['requires_wp'];
		$wp_version  = CBT_Theme_Utils::get_current_wordpress_version();
		$text_domain = sanitize_title( $name );
		if ( isset( $theme['template'] ) ) {
			$template = $theme['template'];
		}
		$version = '1.0';
		$tags    = CBT_Theme_Tags::theme_tags_list( $theme );

		if ( isset( $theme['version'] ) ) {
			$version = $theme['version'];
		}

		$style_css = "/*
Theme Name: {$name}
Theme URI: {$uri}
Author: {$author}
Author URI: {$author_uri}
Description: {$description}
Requires at least: {$requires_wp}
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
		$global_styles_controller->update_item( $update_request );
		delete_transient( 'global_styles' );
		delete_transient( 'global_styles_' . get_stylesheet() );
		delete_transient( 'gutenberg_global_styles' );
		delete_transient( 'gutenberg_global_styles_' . get_stylesheet() );
	}
}
