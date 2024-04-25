import { __ } from '@wordpress/i18n';

export function forceHttps( url ) {
	return url.replace( 'http://', 'https://' );
}

export function bytesToSize( bytes ) {
	const sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB' ];
	if ( bytes === 0 ) return __( 'n/a', 'create-block-theme' );
	const i = parseInt( Math.floor( Math.log( bytes ) / Math.log( 1024 ) ) );
	if ( i === 0 ) return bytes + ' ' + sizes[ i ];
	return ( bytes / Math.pow( 1024, i ) ).toFixed( 1 ) + ' ' + sizes[ i ];
}

export function localFileAsThemeAssetUrl( url ) {
	if ( ! url ) {
		return url;
	}
	return url.replace( 'file:./', window.createBlockTheme.themeUrl + '/' );
}

export async function downloadFile( response ) {
	const blob = await response.blob();
	const filename = response.headers
		.get( 'Content-Disposition' )
		.split( 'filename=' )[ 1 ];

	// Check if the browser supports navigator.msSaveBlob or navigator.saveBlob
	if ( window.navigator.msSaveBlob || window.navigator.saveBlob ) {
		const saveBlob =
			window.navigator.msSaveBlob || window.navigator.saveBlob;
		saveBlob.call( window.navigator, blob, filename );
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

/*
 * Add quotes to font name.
 * @param {string} familyName The font family name.
 * @return {string} The font family name with quotes.
 */

export function addQuotesToName( familyName ) {
	return `'${ familyName }'`.trim();
}
