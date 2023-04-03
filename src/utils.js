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

export function localFileAsThemeAssetUrl( url ) {
	if ( ! url ) {
		return url;
	}
	return url.replace( 'file:./', createBlockTheme.themeUrl + '/' );
}

export async function downloadFile( response ) {
	const blob = await response.blob();
	const filename = response.headers
		.get( 'Content-Disposition' )
		.split( 'filename=' )[ 1 ];

	// Check if the browser supports navigator.msSaveBlob or navigator.saveBlob
	if ( navigator.msSaveBlob || navigator.saveBlob ) {
		const saveBlob = navigator.msSaveBlob || navigator.saveBlob;
		saveBlob.call( navigator, blob, filename );
	} else {
		// Fall back to creating an object URL and triggering a download using an anchor element
		const url = URL.createObjectURL( blob );

		const a = document.createElement( 'a' );
		a.href = url;
		a.download = filename;
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );

		setTimeout( () => {
			URL.revokeObjectURL( url );
		}, 100 );
	}
}
