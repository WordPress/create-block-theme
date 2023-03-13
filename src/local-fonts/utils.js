export function variableAxesToCss( axes ) {
	if ( ! axes || ! Object.keys( axes ).length ) {
		return '';
	}
	const fontVariationSettings = Object.keys( axes )
		.filter(
			( key ) => axes[ key ].defaultValue !== axes[ key ].currentValue
		) // remove axes that have the default value
		.map(
			( key ) => `'${ axes[ key ].tag }' ${ axes[ key ].currentValue }`
		) // convert to CSS format
		.join( ', ' );
	return fontVariationSettings;
}
