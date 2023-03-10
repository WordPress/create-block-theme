export function variableAxesToCss( axes ) {
	if ( ! axes || ! Object.keys( axes ).length ) {
		return '';
	}
	const fontVariationSettings = Object.keys( axes )
		.map( ( key ) => {
			return `'${ axes[ key ].tag }' ${ axes[ key ].currentValue }`;
		} )
		.join( ', ' );
	return fontVariationSettings;
}
