export function getStyleFromGoogleVariant( variant ) {
	return variant.includes( 'italic' ) ? 'italic' : 'normal';
}

export function getWeightFromGoogleVariant( variant ) {
	return variant === 'regular' || variant === 'italic'
		? '400'
		: variant.replace( 'italic', '' );
}

export function getGoogleVariantFromStyleAndWeight( style, weight ) {
	if ( weight === '400' ) {
		if ( style === 'italic' ) {
			return 'italic';
		}
		return 'regular';
	}
	if ( style === 'normal' ) {
		return weight;
	}
	return weight + style;
}

export function forceHttps( url ) {
	return url.replace( 'http://', 'https://' );
}

export function bytesToSize( bytes ) {
	const sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB' ];
	if ( bytes === 0 ) return 'n/a';
	const i = parseInt( Math.floor( Math.log( bytes ) / Math.log( 1024 ) ) );
	if ( i === 0 ) return bytes + ' ' + sizes[ i ];
	return ( bytes / Math.pow( 1024, i ) ).toFixed( 1 ) + ' ' + sizes[ i ];
}
