<?php

class Theme_Readme {
	/**
	* Build a readme.txt file for CHILD/GRANDCHILD themes.
	*/
	public static function build_readme_txt( $theme ) {
		$slug                   = $theme['slug'];
		$name                   = $theme['name'];
		$description            = $theme['description'];
		$uri                    = $theme['uri'];
		$author                 = $theme['author'];
		$author_uri             = $theme['author_uri'];
		$copy_year              = gmdate( 'Y' );
		$wp_version             = get_bloginfo( 'version' );
		$image_credits          = $theme['image_credits'] ?? '';
		$original_theme         = $theme['original_theme'] ?? '';
		$original_theme_credits = $original_theme ? self::original_theme_credits( $name ) : '';

		if ( $original_theme_credits ) {
			// Add a new line to the original theme credits
			$original_theme_credits = $original_theme_credits . "\n";
		}

		if ( $image_credits ) {
			// Add new lines around the image credits
			$image_credits = "\n" . $image_credits;
		}

		$copyright_section = self::copyright_section( $original_theme_credits, $image_credits );

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

= 0.0.1 =
* Initial release

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
	static function original_theme_credits( $new_name ) {
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

		return $theme_credit_content;
	}

	/**
	 * Build copyright section.
	 * Used in readme.txt of cloned themes.
	 *
	 * @return string
	 */
	static function copyright_section( $original_theme_credits, $image_credits ) {
		$copyright_section       = '';
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
				$copyright_section     = $copyright_section_intro . "\n\n" . $original_theme_credits . $new_copyright_section;
			}

			if ( $image_credits ) {
				$copyright_section = $copyright_section . $image_credits;
			}
		}

		return $copyright_section;
	}
}
