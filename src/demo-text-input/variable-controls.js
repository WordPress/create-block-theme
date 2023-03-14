import AxisRangeControl from './axis-range-control';

function VariableControls( { axes, setAxes } ) {
	const handleAxisCurrentValueChange = ( axisTag, value ) => {
		setAxes( {
			...axes,
			[ axisTag ]: {
				...axes[ axisTag ],
				currentValue: value,
			},
		} );
	};

	return (
		<>
			{ axes && Object.keys( axes ).length && (
				<>
					{ Object.keys( axes ).map( ( key ) => (
						<AxisRangeControl
							axis={ axes[ key ] }
							key={ `axis-range-${ key }` }
							setAxisCurrentValue={ handleAxisCurrentValueChange }
						/>
					) ) }
				</>
			) }
		</>
	);
}

export default VariableControls;
