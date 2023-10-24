const fs = require( 'fs' );
const crypto = require( 'crypto' );

const API_URL = 'https://www.googleapis.com/webfonts/v1/webfonts?key=';
const API_KEY = process.env.GOOGLE_FONTS_API_KEY;

function calculateHash( somestring ) {
	return crypto
		.createHash( 'md5' )
		.update( somestring )
		.digest( 'hex' )
		.toString();
}

async function updateFiles() {
	let newApiData;
	let newData;

	try {
		newApiData = await fetch( `${ API_URL }${ API_KEY }` );
		newData = await newApiData.json();
	} catch ( error ) {
		// TODO: show in UI and remove console statement
		// eslint-disable-next-line
		console.error( '❎  Error fetching the Google Fonts API:', error );
		process.exit( 1 );
	}

	if ( newData.items ) {
		try {
			const newDataString = JSON.stringify( newData, null, 2 );
			const oldFileData = fs.readFileSync(
				'./assets/google-fonts/fallback-fonts-list.json',
				'utf8'
			);
			const oldData = JSON.parse( oldFileData );
			const oldDataString = JSON.stringify( oldData, null, 2 );

			if (
				calculateHash( newDataString ) !==
				calculateHash( oldDataString )
			) {
				fs.writeFileSync(
					'./assets/google-fonts/fallback-fonts-list.json',
					newDataString
				);
				// TODO: show in UI and remove console statement
				// eslint-disable-next-line
				console.info( '✅  Google Fonts JSON file updated' );
			} else {
				// TODO: show in UI and remove console statement
				// eslint-disable-next-line
				console.info( 'ℹ️  Google Fonts JSON file is up to date' );
			}
		} catch ( error ) {
			// eslint-disable-next-line
			console.error( '❎  Error stringifying the new JSON data:', error );
			process.exit( 1 );
		}
	} else {
		// TODO: show in UI and remove console statement
		// eslint-disable-next-line
		console.error(
			'❎  No new data to check. Check the Google Fonts API key.'
		);
		process.exit( 1 );
	}
}

updateFiles();
