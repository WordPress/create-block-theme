<?php

class Theme_Json {
	public static function add_theme_json_to_local( $export_type ) {
		file_put_contents(
			get_stylesheet_directory() . '/theme.json',
			MY_Theme_JSON_Resolver::export_theme_data( $export_type )
		);
	}

	public static function add_theme_json_variation_to_local( $export_type, $theme ) {
		$variation_slug = sanitize_title( $theme['variation'] );
		$variation_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;
		$file_counter   = 0;

		if ( ! file_exists( $variation_path ) ) {
			wp_mkdir_p( $variation_path );
		}

		if ( file_exists( $variation_path . $variation_slug . '.json' ) ) {
			$file_counter++;
			while ( file_exists( $variation_path . $variation_slug . '_' . $file_counter . '.json' ) ) {
				$file_counter++;
			}
			$variation_slug = $variation_slug . '_' . $file_counter;
		}

		$_POST['theme']['variation_slug'] = $variation_slug;

		$extra_theme_data = array(
			'version' => WP_Theme_JSON::LATEST_SCHEMA,
			'title'   => $theme['variation'],
		);

		$variation_theme_json = MY_Theme_JSON_Resolver::export_theme_data( $export_type, $extra_theme_data );

		file_put_contents(
			$variation_path . $variation_slug . '.json',
			$variation_theme_json
		);
	}
}
