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
