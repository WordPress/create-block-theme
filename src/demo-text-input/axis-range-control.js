import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';

function AxisRangeControl( { axis, setAxisCurrentValue } ) {
	const handleChange = ( val ) => {
		setAxisCurrentValue( axis.tag, val );
	};

	return (
		<div>
			<RangeControl
				label={
					axis.tag + ' ' + __( 'font axis:', 'create-block-theme' )
				}
				name={ `font-axis-${ axis.tag }` }
				id={ `font-axis-${ axis.tag }` }
				min={ parseInt( axis.minValue ) }
				max={ parseInt( axis.maxValue ) }
				value={ parseInt( axis.currentValue ) }
				onChange={ handleChange }
				step={ 1 }
			/>
		</div>
	);
}

export default AxisRangeControl;
