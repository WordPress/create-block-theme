/**
 * WordPress dependencies
 */
import { downloadBlob } from '@wordpress/blob';

/*
 * Download a file from in a browser.
 *
 * @param {Response} response The response object from a fetch request.
 * @return {void}
 */
export default async function downloadFile( response ) {
	const blob = await response.blob();
	const filename = response.headers
		.get( 'Content-Disposition' )
		.split( 'filename=' )[ 1 ];
	downloadBlob( filename, blob, 'application/zip' );
}
