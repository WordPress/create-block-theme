<?php

class CBT_Theme_JSON {

	public static function add_theme_json_to_local( $export_type ) {
		file_put_contents(
			get_stylesheet_directory() . '/theme.json',
			CBT_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
	}

	public static function add_theme_json_variation_to_local( $export_type, $theme ) {
		$variation_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;

		if ( ! file_exists( $variation_path ) ) {
			wp_mkdir_p( $variation_path );
		}

		if ( file_exists( $variation_path . $theme['slug'] . '.json' ) ) {
			return new WP_Error( 'variation_already_exists', __( 'Variation already exists.', 'create-block-theme' ) );
		}

		$_POST['theme']['variation_slug'] = $theme['slug'];

		$extra_theme_data = array(
			'version' => WP_Theme_JSON::LATEST_SCHEMA,
			'title'   => $theme['name'],
		);

		$variation_theme_json = CBT_Theme_JSON_Resolver::export_theme_data( $export_type, $extra_theme_data );

		file_put_contents(
			$variation_path . $theme['slug'] . '.json',
			$variation_theme_json
		);
	}
}
