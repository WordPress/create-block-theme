export function variableAxesToCss( axes ) {
	if ( ! axes || ! Object.keys( axes ).length ) {
		return '';
	}
	const fontVariationSettings = Object.keys( axes )
		.map(
			( key ) => `'${ axes[ key ].tag }' ${ axes[ key ].currentValue }`
		) // convert to CSS format
		.join( ', ' );
	return fontVariationSettings;
}
