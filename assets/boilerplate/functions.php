<?php
/**
 * Enqueue the theme's main stylesheet.
 *
 * This function checks if the `style.css` file exists in the theme directory
 * before attempting to enqueue it. It ensures that missing files do not
 * generate PHP warnings and also applies the theme version for cache busting.
 *
 * Usage: Hooked into 'wp_enqueue_scripts'.
 *
 * @since 1.0.0
 *
 * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
 * @see https://developer.wordpress.org/reference/functions/get_stylesheet_directory/
 * @see https://developer.wordpress.org/reference/functions/get_stylesheet_uri/
 * @see https://developer.wordpress.org/reference/functions/wp_get_theme/
 *
 * @return void
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		$stylesheet_path = get_stylesheet_directory() . '/style.css';

		if ( file_exists( $stylesheet_path ) ) {

			wp_enqueue_style(
				'theme-style',
				get_stylesheet_uri(),
				array(),
				wp_get_theme()->get( 'Version' )
			);

		} else {
			error_log( 'Stylesheet not found: ' . $stylesheet_path );
		}
	}
);
