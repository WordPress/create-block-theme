<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'theme_enqueue_style' ) ) {
	/**
	 * Enqueue the theme's main stylesheet on the frontend.
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
	 * @see https://developer.wordpress.org/reference/functions/get_stylesheet_directory_uri/
	 * @see https://developer.wordpress.org/reference/functions/wp_get_theme/
	 *
	 * @return void
	 */
	function theme_enqueue_style() {
		wp_enqueue_style(
			'theme-style',
			get_template_directory_uri() . '/style.css',
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
	add_action( 'wp_enqueue_scripts', 'theme_enqueue_style' );
}

if ( ! function_exists( 'theme_enqueue_editor_style' ) ) {
	/**
	 * Enqueue the theme's main stylesheet in the block editor.
	 *
	 * This function enqueues the `style.css` file in the block editor to ensure
	 * that the editor styles match the frontend styles. It uses the
	 * `add_editor_style` function which is specifically designed for this purpose.
	 *
	 * Usage: Hooked into 'after_setup_theme'.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_editor_style/
	 *
	 * @return void
	 */
	function theme_enqueue_editor_style() {
		add_editor_style( 'style.css' );
	}
	add_action( 'after_setup_theme', 'theme_enqueue_editor_style' );
}
