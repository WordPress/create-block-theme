<?php

function augment_gutenberg_with_utilities() {

	//Ultimately it is desireable for Gutenberg to have this functionality natively.
	// In the meantime we are patching the functionality we are expecting into the Theme JSON Resolver here

	class MY_Theme_JSON_Resolver extends WP_Theme_JSON_Resolver_Gutenberg {

		/**
		 * 'Flatten' theme data that expresses both theme and user data.
		 * change property.[custom|theme].value to property.value
		 * Uses custom value if available, otherwise theme value
		 * I feel like there should be a function to do this in Gutenberg but I couldn't find it
		 */
		private static function flatten_theme_json( $data, $name ) {
			if ( is_array( $data ) ) {

				// 'settings' can have a 'custom' object that is different and should not be processed in the same way
				// as values that could be represented as both theme and user (user data being 'custom')
				if ( $name !== 'settings' ) {

					// When there is BOTH custom AND THEME combine the two
					if ( array_key_exists( 'custom', $data ) && array_key_exists( 'theme', $data ) ) {
						$merged = array_merge( $data['custom'], $data['theme'] );
						// eliminate values with duplicate slugs
						// TODO: This could probably be done better...
						$filtered = array();
						$sluglist = array();
						foreach ( $merged as $item ) {
							if( array_key_exists('slug', $item) ) {
								if( ! in_array($item['slug'], $sluglist) ) {
									$sluglist[] = $item['slug'];
									$filtered[] = $item;
								}
							} 
							else {
								$filtered[] = $item;
							}
						}
						return MY_Theme_JSON_Resolver::flatten_theme_json($filtered, $name);
					}

					// When there is CUSTOM but no THEME return custom
					if ( array_key_exists( 'custom', $data ) ) {
						return MY_Theme_JSON_Resolver::flatten_theme_json($data['custom'], $name);
					}

					// When there is THEME but no CUSTOM return theme 
					if ( array_key_exists( 'theme', $data ) ) {
						return MY_Theme_JSON_Resolver::flatten_theme_json($data['theme'], $name);
					}

				}

				foreach( $data as $node_name => $node_value  ) {
					$data[ $node_name ] = MY_Theme_JSON_Resolver::flatten_theme_json( $node_value, $node_name );
				}
			}

			return $data;
		}

		/**
		 * Export the combined (and flattened) THEME and CUSTOM data.
		 * 
		 * @param string $content ['all', 'current', 'user'] Determines which settings content to include in the export.
		 * All options include user settings.  
		 * 'current' will include settings from the currently installed theme but NOT from the parent theme.
		 * 'all' will include settings from the current theme as well as the parent theme (if it has one)
		 */
		public static function export_theme_data( $content ) {

	        	$theme = new WP_Theme_JSON_Gutenberg();

			if ( $content === 'all' && wp_get_theme()->parent() ) {
				// Get parent theme.json.
				$parent_theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json', true ) );
				$parent_theme_json_data = static::translate( $parent_theme_json_data, wp_get_theme()->parent()->get( 'TextDomain' ) );
				$parent_theme           = new WP_Theme_JSON_Gutenberg( $parent_theme_json_data );
				$theme->merge ($parent_theme);
			}

			if ( $content === 'all' || $content === 'current' ) {
	        		$theme_json_data = static::read_json_file( static::get_file_path_from_theme( 'theme.json' ) );
	        		$theme_json_data = static::translate( $theme_json_data, wp_get_theme()->get( 'TextDomain' ) );
				$theme_theme     = new WP_Theme_JSON_Gutenberg( $theme_json_data );
 				$theme->merge( $theme_theme );
			}

	        	$theme->merge( static::get_user_data() );

			$data = MY_Theme_JSON_Resolver::flatten_theme_json($theme->get_raw_data(), null);

			// die(json_encode($data));

			return $data;

		}

	}
}

add_action( 'plugins_loaded', 'augment_gutenberg_with_utilities' );