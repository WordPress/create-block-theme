<?php

// Received an array of font families from theme.json and outputs the CSS to load those fonts.
// This is only necesary when Gutenberg is not active.
// TODO: Remove this function when WordPress Core includes the WP_Webfonts class.
function render_font_styles( $font_families ) {
	$styles = '';
	foreach ( $font_families as $font_family ) {
		if ( isset( $font_family['fontFace'] ) && is_array( $font_family['fontFace'] ) ) {
			foreach ( $font_family['fontFace'] as $font_face ) {
				$font_face_url = substr( $font_face['src'][0], 0, 7 ) === 'file:./'
					? get_template_directory_uri() . str_replace( 'file:./', '/', $font_face['src'][0] )
					: $font_face['src'][0];

				$font_face_weight = ! empty( $font_face['fontWeight'] ) ? $font_face['fontWeight'] : 'normal';
				$font_face_style  = ! empty( $font_face['fontStyle'] ) ? $font_face['fontStyle'] : 'normal';

				$styles .= '@font-face {';
				$styles .= "font-family: '" . $font_face['fontFamily'] . "';";
				$styles .= 'src: url(' . $font_face_url . ');';
				$styles .= 'font-weight: ' . $font_face_weight . ';';
				$styles .= 'font-style: ' . $font_face_style . ';';
				$styles .= '}';
			}
		}
	}
	return $styles;
}


