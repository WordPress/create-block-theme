<?php

class CBT_Theme_Fluid_Typography {

	/**
	 * Sync the size property with fluid.max in font sizes.
	 * This ensures that when fluid typography is edited, the size property
	 * stays in sync with the maximum fluid size.
	 *
	 * @param array $data The theme.json data to process.
	 * @return array The processed data with synced sizes.
	 */
	public static function sync_fluid_typography_sizes( $data ) {
		if ( ! isset( $data['settings']['typography']['fontSizes'] ) ) {
			return $data;
		}

		$font_sizes = $data['settings']['typography']['fontSizes'];

		foreach ( $font_sizes as $key => $font_size ) {
			// Check if this font size has fluid settings
			if ( isset( $font_size['fluid'] ) && is_array( $font_size['fluid'] ) ) {
				// If fluid.max is set, sync it with size
				if ( isset( $font_size['fluid']['max'] ) ) {
					$font_sizes[ $key ]['size'] = $font_size['fluid']['max'];
				}
			}
		}

		$data['settings']['typography']['fontSizes'] = $font_sizes;

		return $data;
	}

	/**
	 * Hook into the global styles REST API to sync fluid typography sizes.
	 */
	public static function init() {
		add_filter( 'rest_pre_insert_wp_global_styles', array( __CLASS__, 'sync_global_styles_fluid_typography' ), 10, 2 );
	}

	/**
	 * Sync fluid typography in global styles before saving.
	 *
	 * @param stdClass $prepared_post The prepared post object.
	 * @param WP_REST_Request $request The REST request object.
	 * @return stdClass The modified prepared post object.
	 */
	public static function sync_global_styles_fluid_typography( $prepared_post, $request ) {
		if ( ! isset( $prepared_post->post_content ) ) {
			return $prepared_post;
		}

		$data = json_decode( $prepared_post->post_content, true );

		if ( ! is_array( $data ) ) {
			return $prepared_post;
		}

		$data = self::sync_fluid_typography_sizes( $data );

		$prepared_post->post_content = wp_json_encode( $data );

		return $prepared_post;
	}
}

// Initialize the fluid typography sync on plugins_loaded
add_action( 'plugins_loaded', array( 'CBT_Theme_Fluid_Typography', 'init' ) );
