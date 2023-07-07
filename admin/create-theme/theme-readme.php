<?php

class Theme_Readme {
	/**
	* Build a readme.txt file for CHILD/GRANDCHILD themes.
	*/
	public static function build_readme_txt( $theme ) {
		$slug                = $theme['slug'];
		$name                = $theme['name'];
		$description         = $theme['description'];
		$uri                 = $theme['uri'];
		$author              = $theme['author'];
		$author_uri          = $theme['author_uri'];
		$copy_year           = gmdate( 'Y' );
		$wp_version          = get_bloginfo( 'version' );
		$image_credits       = $theme['image_credits'] ?? '';
		$recommended_plugins = $theme['recommended_plugins'] ?? '';
		$is_parent_theme     = $theme['is_parent_theme'] ?? false;
		$original_theme      = $theme['original_theme'] ?? '';

		// Handle copyright section.
		$new_copyright_section  = $is_parent_theme || $original_theme ? true : false;
		$original_theme_credits = $new_copyright_section ? self::original_theme_credits( $name, $is_parent_theme ) : '';
		$copyright_section      = self::copyright_section( $new_copyright_section, $original_theme_credits, $name, $copy_year, $author, $image_credits );

		// Handle recommended plugins section.
		$recommended_plugins_section = self::recommended_plugins_section( $recommended_plugins ) ?? '';

		return "=== {$name} ===
Contributors: {$author}
Requires at least: 6.0
Tested up to: {$wp_version}
Requires PHP: 5.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

{$description}

== Changelog ==

= 1.0.0 =
* Initial release
{$recommended_plugins_section}
{$copyright_section}
";
	}

	/**
	 * Build string for original theme credits.
	 * Used in readme.txt of cloned themes.
	 *
	 * @param string $new_name New theme name.
	 * @return string
	 */
	static function original_theme_credits( $new_name, $is_parent_theme = false ) {
		if ( ! $new_name ) {
			return;
		}

		$original_name        = wp_get_theme()->get( 'Name' ) ?? '';
		$original_uri         = wp_get_theme()->get( 'ThemeURI' ) ?? '';
		$original_author      = wp_get_theme()->get( 'Author' ) ?? '';
		$original_readme      = get_stylesheet_directory() . '/readme.txt' ?? '';
		$original_license     = '';
		$original_license_uri = '';
		$readme_content       = file_exists( $original_readme ) ? file_get_contents( $original_readme ) : '';

		if ( ! $readme_content ) {
			return;
		}

		// Get license from original theme readme.txt
		if ( str_contains( $readme_content, 'License:' ) ) {
			$starts           = strpos( $readme_content, 'License:' ) + strlen( 'License:' );
			$ends             = strpos( $readme_content, 'License URI:', $starts );
			$original_license = trim( substr( $readme_content, $starts, $ends - $starts ) );
		}

		// Get license URI from original theme readme.txt
		if ( str_contains( $readme_content, 'License URI:' ) ) {
			$starts               = strpos( $readme_content, 'License URI:' ) + strlen( 'License URI:' );
			$ends                 = strpos( $readme_content, '== Description ==', $starts );
			$original_license_uri = trim( substr( $readme_content, $starts, $ends - $starts ) );
		}

		if ( empty( $original_license ) || empty( $original_license_uri ) ) {
			return;
		}

		$theme_credit_content = sprintf(
			/* translators: 1: New Theme name, 2: Original Theme Name. 3. Original Theme URI. 4. Original Theme Author. 5. Original Theme License. 6. Original Theme License URI. */
			__( '%1$s is based on %2$s (%3$s), (C) %4$s, [%5$s](%6$s)', 'create-block-theme' ),
			$new_name,
			$original_name,
			$original_uri,
			$original_author,
			$original_license,
			$original_license_uri
		);

		if ( $is_parent_theme ) {
			$theme_credit_content = sprintf(
				/* translators: 1: New Theme name, 2: Parent Theme Name. 3. Parent Theme URI. 4. Parent Theme Author. 5. Parent Theme License. 6. Parent Theme License URI. */
				__( '%1$s is a child theme of %2$s (%3$s), (C) %4$s, [%5$s](%6$s)', 'create-block-theme' ),
				$new_name,
				$original_name,
				$original_uri,
				$original_author,
				$original_license,
				$original_license_uri
			);
		}

		return $theme_credit_content;
	}

	/**
	 * Build copyright section.
	 * Used in readme.txt of cloned themes or child themes.
	 *
	 * @return string
	 */
	static function copyright_section( $new_copyright_section, $original_theme_credits, $name, $copy_year, $author, $image_credits ) {
		// Default copyright section.
		$copyright_section = "== Copyright ==

{$name} WordPress Theme, (C) {$copy_year} {$author}
{$name} is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.";

		// If a new copyright section is required, then build ones based on the current theme.
		if ( $new_copyright_section ) {
			$copyright_section_intro = '== Copyright ==';

			// Get current theme readme.txt
			$current_readme         = get_stylesheet_directory() . '/readme.txt' ?? '';
			$current_readme_content = file_exists( $current_readme ) ? file_get_contents( $current_readme ) : '';

			if ( ! $current_readme_content ) {
				return;
			}

			// Copy copyright section from current theme readme.txt
			if ( str_contains( $current_readme_content, $copyright_section_intro ) ) {
				$copyright_section_start = strpos( $current_readme_content, $copyright_section_intro );
				$copyright_section       = substr( $current_readme_content, $copyright_section_start );

				if ( $original_theme_credits ) {
					$new_copyright_section = str_replace( $copyright_section_intro . "\n", '', $copyright_section );
					$copyright_section     = $copyright_section_intro . "\n\n" . $original_theme_credits . "\n" . $new_copyright_section;
				}
			}
		}

		if ( $image_credits ) {
			$copyright_section = $copyright_section . "\n" . $image_credits;
		}

		return $copyright_section;
	}

	/**
	 * Build Recommended Plugins section.
	 *
	 * @return string
	 */
	static function recommended_plugins_section( $recommended_plugins, $updated_readme = '' ) {
		$recommended_plugins_section = '';

		if ( ! $recommended_plugins ) {
			return '';
		}

		$section_start = "\n== Recommended Plugins ==\n";

		// Remove existing Recommended Plugins section.
		if ( $updated_readme && str_contains( $updated_readme, $section_start ) ) {
			$pattern = '/\s+== Recommended Plugins ==\s+(.*?)(?=(\n\=\=)|$)/s';
			preg_match_all( $pattern, $updated_readme, $matches );
			$current_section = $matches[0][0];
			$updated_readme  = str_replace( $current_section, '', $updated_readme );
		}

		$recommended_plugins_section = $section_start . "\n" . $recommended_plugins . "\n";

		if ( $updated_readme ) {
			return $updated_readme . $recommended_plugins_section;
		}

		return $recommended_plugins_section;
	}

	/**
	 * Update current readme.txt file, rather than building a new one.
	 *
	 * @return string
	 */
	public static function update_readme_txt( $theme ) {
		$description         = $theme['description'];
		$author              = $theme['author'];
		$wp_version          = get_bloginfo( 'version' );
		$image_credits       = $theme['image_credits'] ?? '';
		$recommended_plugins = $theme['recommended_plugins'] ?? '';
		$updated_readme      = '';
		$current_readme      = get_stylesheet_directory() . '/readme.txt' ?? '';
		$readme_content      = file_exists( $current_readme ) ? file_get_contents( $current_readme ) : '';

		if ( ! $readme_content ) {
			return;
		}

		$updated_readme = $readme_content;

		// Update description.
		if ( $description ) {
			$pattern = '/(== Description ==)(.*?)(\n\n=|$)/s';
			preg_match_all( $pattern, $updated_readme, $matches );
			$current_description = $matches[0][0];
			$updated_readme      = str_replace( $current_description, "== Description ==\n\n{$description}\n\n=", $updated_readme );
		}

		// Update Author/Contributors.
		if ( $author ) {
			$pattern = '/(Contributors:)(.*?)(\n|$)/s';
			preg_match_all( $pattern, $updated_readme, $matches );
			$current_uri    = $matches[0][0];
			$updated_readme = str_replace( $current_uri, "Contributors: {$author}\n", $updated_readme );
		}

		// Update "Tested up to" version.
		if ( $wp_version ) {
			$pattern = '/(Tested up to:)(.*?)(\n|$)/s';
			preg_match_all( $pattern, $updated_readme, $matches );
			$current_uri    = $matches[0][0];
			$updated_readme = str_replace( $current_uri, "Tested up to: {$wp_version}\n", $updated_readme );
		}

		if ( $recommended_plugins ) {
			$updated_readme = self::recommended_plugins_section( $recommended_plugins, $updated_readme );
		}

		if ( $image_credits ) {
			$updated_readme = $updated_readme . "\n\n" . $image_credits;
		}

		return $updated_readme;
	}
}
