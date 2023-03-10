import { __ } from '@wordpress/i18n';
import { RangeControl } from '@wordpress/components';

function AxisRangeControl( { axis, formData, setFormData } ) {
	const handleOnChange = ( val ) => {
		const newAxis = {
			...axis,
			currentValue: val,
		};
		const newAxes = {
			...formData.axes,
			[ axis.tag ]: newAxis,
		};
		setFormData( {
			...formData,
			axes: newAxes,
		} );
	};

	return (
		<div className="form-group">
			<label htmlFor={ `font-axis-${ axis.tag }` }>
				{ axis.tag } { __( 'font axis:', 'create-block-theme' ) }
			</label>
			<RangeControl
				name={ `font-axis-${ axis.tag }` }
				id={ `font-axis-${ axis.tag }` }
				min={ parseInt( axis.minValue ) }
				max={ parseInt( axis.maxValue ) }
				value={ parseInt( axis.currentValue ) }
				onChange={ handleOnChange }
				step={ 1 }
			/>
		</div>
	);
}

export default AxisRangeControl;
