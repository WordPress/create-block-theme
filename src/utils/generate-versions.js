export function generateWpVersions( versionString ) {
	const version = versionString.split( '-' )[ 0 ];
	let [ major, minor ] = version.split( '.' ).slice( 0, 2 ).map( Number );

	const versions = [];

	// Iterate through the versions from current to 5.9
	while ( major > 5 || ( major === 5 && minor >= 9 ) ) {
		versions.push( `${ major }.${ minor }` );

		// Decrement minor version
		if ( minor === 0 ) {
			minor = 9; // Wrap around if minor is 0, decrement the major version
			major--;
		} else {
			minor--;
		}
	}

	return versions;
}
