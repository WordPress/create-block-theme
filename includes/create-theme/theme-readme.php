<?php

class CBT_Theme_Readme {

	/**
	 * Get the path to the readme.txt file.
	 *
	 * @return string
	 */
	public static function file_path() {
		return path_join( get_stylesheet_directory(), 'readme.txt' );
	}

	/**
	 * Get the content of the readme.txt file.
	 *
	 * @return string
	 */
	public static function get_content() {
		$path = self::file_path();
		if ( ! file_exists( $path ) ) {
			return '';
		}
		return file_get_contents( $path );
	}

	/**
	* Creates readme.txt text content from theme data.
	*
	* @param array $theme The theme data.
	* {
	*     @type string $name The theme name.
	*     @type string $description The theme description.
	*     @type string $uri The theme URI.
	*     @type string $author The theme author.
	*     @type string $author_uri The theme author URI.
	*     @type string $copyright_year The copyright year.
	*     @type string $image_credits The image credits.
	*     @type string $recommended_plugins The recommended plugins.
	*     @type bool $is_child_theme Whether the theme is a child theme.
	* }
	*
	* @return string The readme content.
	*/
	public static function create( $theme ) {
		$name                 = $theme['name'];
		$description          = $theme['description'] ?? '';
		$uri                  = $theme['uri'] ?? '';
		$author               = $theme['author'] ?? '';
		$author_uri           = $theme['author_uri'] ?? '';
		$copy_year            = $theme['copyright_year'] ?? gmdate( 'Y' );
		$wp_version           = $theme['wp_version'] ?? CBT_Theme_Utils::get_current_wordpress_version();
		$requires_wp          = ( '' === $theme['requires_wp'] ) ? CBT_Theme_Utils::get_current_wordpress_version() : $theme['requires_wp'];
		$required_php_version = $theme['required_php_version'] ?? '5.7';
		$license              = $theme['license'] ?? 'GPLv2 or later';
		$license_uri          = $theme['license_uri'] ?? 'http://www.gnu.org/licenses/gpl-2.0.html';
		$image_credits        = $theme['image_credits'] ?? '';
		$recommended_plugins  = $theme['recommended_plugins'] ?? '';
		$font_credits         = $theme['font_credits'] ?? '';
		$is_child_theme       = $theme['is_child_theme'] ?? false;

		// Generates the copyright section text.
		$copyright_section_content = self::get_copyright_text( $theme );

		// Create empty readme content
		$readme_content = '';

		// Adds the Theme section.
		$theme_section_content = "
Contributors: {$author}
Requires at least: {$requires_wp}
Tested up to: {$wp_version}
Requires PHP: {$required_php_version}
License: {$license}
License URI: {$license_uri}
";
		$readme_content        = self::add_or_update_section( $name, $theme_section_content, $readme_content );

		// Adds the Decription section
		$readme_content = self::add_or_update_section( 'Description', $description, $readme_content );

		// Adds the Changelog section
		$initial_changelog = '
= 1.0.0 =
* Initial release
';
		$readme_content    = self::add_or_update_section( 'Changelog', $initial_changelog, $readme_content );

		// Adds the recommended plugins section
		$readme_content = self::add_or_update_section( 'Recommended Plugins', $recommended_plugins, $readme_content );

		// Adds the font credits section
		$readme_content = self::add_or_update_section( 'Fonts', $font_credits, $readme_content );

		// Adds the Copyright section
		$readme_content = self::add_or_update_section( 'Copyright', $copyright_section_content, $readme_content );

		// Adds the Images section
		$readme_content = self::add_or_update_section( 'Images', $image_credits, $readme_content );

		// Sanitize the readme content
		$readme_content = self::sanitize( $readme_content );

		return $readme_content;
	}

	/**
	 * Get the theme data from the installed theme.
	 *
	 * @param string $new_name New theme name.
	 * @return array The theme data.
	 * {
	 *    @type string $name The theme name.
	 *    @type string $uri The theme URI.
	 *    @type string $author The theme author.
	 *    @type string $license The theme license.
	 *    @type string $license_uri The theme license URI.
	 * }
	 */
	private static function get_active_theme_data() {
		$original_name        = wp_get_theme()->get( 'Name' ) ?? '';
		$original_uri         = wp_get_theme()->get( 'ThemeURI' ) ?? '';
		$original_author      = wp_get_theme()->get( 'Author' ) ?? '';
		$original_license     = self::get_prop( 'License' );
		$original_license_uri = self::get_prop( 'License URI' );

		return array(
			'name'        => $original_name,
			'uri'         => $original_uri,
			'author'      => $original_author,
			'license'     => $original_license,
			'license_uri' => $original_license_uri,
		);
	}

	/**
	 * Build default copyright text for a theme.
	 *
	 * @param string $name The theme name.
	 * @param string $copy_year The current year.
	 * @param string $author The theme author.
	 * @return string The default copyright text.
	 */
	private static function get_copyright_text( $theme ) {
		$name   = $theme['name'];
		$year   = $theme['copy_year'] ?? gmdate( 'Y' );
		$author = $theme['author'] ?? '';

		$text = "
{$name} WordPress Theme, (C) {$year} {$author}
{$name} is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
";

		$is_child_theme  = $theme['is_child_theme'] ?? false;
		$is_cloned_theme = $theme['is_cloned_theme'] ?? false;

		/*
		 * If the theme is a child theme or a cloned theme, add a reference to the parent theme.
		 *
		 * Example: "My Child Theme is a child theme of My Parent Theme (https://example.org/themes/my-parent-theme), (C) the WordPress team, [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)"
		 */
		if ( $is_child_theme || $is_cloned_theme ) {
			$original_theme = self::get_active_theme_data();

			$reference_string = $is_child_theme
				? '%1$s is a child theme of %2$s (%3$s), (C) %4$s, [%5$s](%6$s)'
				: '%1$s is based on %2$s (%3$s), (C) %4$s, [%5$s](%6$s)';

			$reference = sprintf(
				$reference_string,
				$name,
				$original_theme['name'],
				$original_theme['uri'],
				$original_theme['author'],
				$original_theme['license'],
				$original_theme['license_uri']
			);

			$text .= "\n\n" . $reference;
		}

		return $text;
	}

	/**
	 * Update current readme.txt file, rather than building a new one.
	 *
	 * @param array $theme The theme data.
	 * {
	 *   @type string $description The theme description.
	 *   @type string $author The theme author.
	 *   @type string $image_credits The image credits.
	 *   @type string $recommended_plugins The recommended plugins.
	 *   @type string $font_credits The font credits.
	 * }
	 * @param string $readme_content readme.txt content.
	 * @return string
	 */
	public static function update( $theme, $readme_content = '' ) {
		// Theme data.
		$description         = $theme['description'] ?? '';
		$author              = $theme['author'] ?? '';
		$wp_version          = $theme['wp_version'] ?? CBT_Theme_Utils::get_current_wordpress_version();
		$requires_wp         = ( '' === $theme['requires_wp'] ) ? CBT_Theme_Utils::get_current_wordpress_version() : $theme['requires_wp'];
		$image_credits       = $theme['image_credits'] ?? '';
		$recommended_plugins = $theme['recommended_plugins'] ?? '';
		$font_credits        = $theme['font_credits'] ?? '';

		// Update description.
		$readme_content = self::add_or_update_section( 'Description', $description, $readme_content );

		// Update Author/Contributors.
		$readme_content = self::add_or_update_prop( 'Contributors', $author, $readme_content );

		// Update Required WordPress version.
		$readme_content = self::add_or_update_prop( 'Requires at least', $requires_wp, $readme_content );

		// Update "Tested up to" version.
		$readme_content = self::add_or_update_prop( 'Tested up to', $wp_version, $readme_content );

		// Update recommended plugins section.
		$readme_content = self::add_or_update_section( 'Recommended Plugins', $recommended_plugins, $readme_content );

		// Update font credits section.
		$readme_content = self::add_or_update_section( 'Fonts', $font_credits, $readme_content );

		// Update image credits section.
		$readme_content = self::add_or_update_section( 'Images', $image_credits, $readme_content );

		// Sanitize the readme content.
		$readme_content = self::sanitize( $readme_content );

		return $readme_content;
	}

	/**
	 * Write a section to the readme.txt file.
	 *
	 * @param string $section_title    Section to write.
	 * @param string $section_content  New content to write.
	 * @param string $current_content  Current content to manipulate.
	 *
	 * @return void
	 */
	public static function add_or_update_section( $section_title, $section_content, $readme_content = '' ) {
		// If the section content is empty, return the current content. This avoids adding empty sections.
		if ( empty( $section_content ) ) {
			return $readme_content;
		}

			$section_content = trim( $section_content, "\r" );
			$section_content = trim( $section_content, "\n" );

		// Regular expression to find the section, handling both '==' and '==='
		$pattern     = '/(={2,3}\s*' . preg_quote( $section_title, '/' ) . '\s*={2,3})(.*?)(?=(={2,3}|$))/s';
		$replacement = "== $section_title ==\n\n$section_content\n\n";

		// Check if the section exists
		if ( preg_match( $pattern, $readme_content ) ) {
			// Replace the existing section content
			$updated_content = preg_replace( $pattern, $replacement, $readme_content );
		} else {
			// Remove any trailing whitespace, newlines or carriage returns from current content
			$readme_content = rtrim( $readme_content );

			// Ensure two newlines before appending new section
			if ( ! empty( $readme_content ) ) {
				$readme_content .= "\n\n\n";
			}

			// Append new section if not found
			$updated_content = $readme_content . $replacement;
		}

		return $updated_content;
	}

	/**
	 * Adds or updates a property in the readme content.
	 *
	 * @param string $prop_name The name of the property.
	 * @param string $prop_value The value of the property.
	 * @param string $readme_content The content of the readme file.
	 * @return string The updated readme content.
	 */
	private static function add_or_update_prop( $prop_name, $prop_value, $readme_content ) {
		if ( empty( $prop_value ) ) {
			return $readme_content;
		}
		$pattern = '/(' . preg_quote( $prop_name, '/' ) . ')(.*?)(\n|$)/s';
		preg_match_all( $pattern, $readme_content, $matches );
		$current_uri    = $matches[0][0];
		$updated_readme = str_replace( $current_uri, "{$prop_name}: {$prop_value}\n", $readme_content );
		return $updated_readme;
	}

	/**
	 * Get property value from the readme content.
	 *
	 * @return string The property value
	 */
	private static function get_prop( $property, $readme_content = '' ) {
		if ( empty( $readme_content ) ) {
			$readme_content = self::get_content();
		}

		// Build the regular expression pattern to match the line
		$pattern = '/^' . preg_quote( $property, '/' ) . ': (.*)$/m';

		// Use preg_match to find a matching line
		if ( preg_match( $pattern, $readme_content, $matches ) ) {
			// Return the capturing group which contains the value after the colon
			return trim( $matches[1] );
		} else {
			// Return null if no match is found
			return null;
		}
	}

	public static function get_sections() {

		$readme_content = self::get_content();
		$sections       = array();

		// Regular expression to find the section, handling both '==' and '==='
		$pattern = '/(={2,3}\s*(.*?)\s*={2,3})(.*?)(?=(={2,3}|$))/s';

		// Find all sections
		preg_match_all( $pattern, $readme_content, $matches, PREG_SET_ORDER );

		// Loop through the matches
		foreach ( $matches as $match ) {
			$section_title   = str_replace( '-', '_', sanitize_title( $match[2] ) );
			$section_content = trim( $match[3] );

			// Add the section to the sections array
			$sections[ $section_title ] = $section_content;
		}

		return $sections;
	}

	/**
	 * Sanitize the readme content.
	 *
	 * @param string $readme_content The readme content.
	 * @return string The sanitized readme content.
	 */
	private static function sanitize( $readme_content ) {
		// Replaces DOS line endings with Unix line endings
		$readme_content = str_replace( "\r\n", '', $readme_content );
		return $readme_content;
	}

}
