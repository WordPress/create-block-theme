import { Button } from '@wordpress/components';
import Demo from '../demo-text-input/demo';
const { __ } = wp.i18n;

function FontFace( {
	fontFamily,
	fontWeight,
	fontStyle,
	deleteFontFace,
	shouldBeRemoved,
	isFamilyOpen,
} ) {
	const demoStyles = {
		fontFamily,
		fontStyle,
		// Handle cases like fontWeight is a number instead of a string or when the fontweight is a 'range', a string like "800 900".
		fontWeight: fontWeight
			? String( fontWeight ).split( ' ' )[ 0 ]
			: 'normal',
	};

	if ( shouldBeRemoved ) {
		return null;
	}

	return (
		<tr className="font-face">
			<td>{ fontStyle }</td>
			<td>{ fontWeight }</td>
			<td className="demo-cell">
				<Demo style={ demoStyles } />
			</td>
			{ deleteFontFace && (
				<td>
					<Button
						variant="tertiary"
						onClick={ deleteFontFace }
						tabindex={ isFamilyOpen ? 0 : -1 }
					>
						{ __( 'Remove', 'create-block-theme' ) }
					</Button>
				</td>
			) }
		</tr>
	);
}

FontFace.defaultProps = {
	fontWeight: 'normal',
	fontStyle: 'normal',
};

export default FontFace;
