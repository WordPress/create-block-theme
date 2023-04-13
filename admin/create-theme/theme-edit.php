<?php

class Theme_Edit {

	public static function register_theme_edit_endpoint() {

		register_rest_route(
			'create-block-theme/v1',
			'/theme-data',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'Theme_Edit', 'get_theme_data' ),
				'permission_callback' => function () {
					return true; //current_user_can( 'edit_theme_options' );
				},
			)
		);

		register_rest_route(
			'create-block-theme/v1',
			'/theme-data',
			array(
				'methods'             => 'PUT',
				'callback'            => array( 'Theme_Edit', 'update_theme_data' ),
				'permission_callback' => function () {
					return true; //current_user_can( 'edit_theme_options' );
				},
			)
		);

	}

	static function get_user_json() {
		$theme     = MY_Theme_JSON_Resolver::new_theme_json();
		$user_data = MY_Theme_JSON_Resolver::get_custom_user_data();
		$theme->merge( $user_data );
		$user_json = $theme->get_data();
		return $user_json;
	}

	static function get_templates() {
		$templates = get_block_templates();
		return json_encode( $templates );
	}

	static function get_template_parts() {
		$template_parts = get_block_templates( array(), 'wp_template_part' );
		return json_encode( $template_parts );
	}

	static function get_theme_data() {
		$debug_data = array(
			// style.css data
			'description'    => wp_get_theme()->get( 'Description' ),
			'theme_uri'      => wp_get_theme()->get( 'ThemeURI' ),
			'thumbnail'      => wp_get_theme()->get_screenshot(),
			'version'        => wp_get_theme()->get( 'Version' ),
			'author'         => wp_get_theme()->get( 'Author' ),
			'author_uri'     => wp_get_theme()->get( 'AuthorURI' ),

			'user_json'      => static::get_user_json(),
			'templates'      => static::get_templates(),
			'template_parts' => static::get_template_parts(),
		);

		return rest_ensure_response( $debug_data );
	}

	static function update_theme_data() {
		$theme     = MY_Theme_JSON_Resolver::new_theme_json();
		$user_data = MY_Theme_JSON_Resolver::get_custom_user_data();
		$theme->merge( $user_data );
		$user_json = $theme->get_data();
		return $user_json;
	}


}


