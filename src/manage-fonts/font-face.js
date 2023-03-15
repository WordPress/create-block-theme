import { Button } from '@wordpress/components';
import Demo from '../demo-text-input/demo';
const { __ } = wp.i18n;

function FontFace( { face, deleteFont, shouldBeRemoved, isFamilyOpen } ) {
	const demoStyles = {
		fontFamily: face.fontFamily,
		fontStyle: face.fontStyle,
		// Handle cases like fontWeight is a number instead of a string or when the fontweight is a 'range', a string like "800 900".
		fontWeight: face.fontWeight
			? String( face.fontWeight ).split( ' ' )[ 0 ]
			: 'normal',
		...( face.fontVariationSettings
			? { fontVariationSettings: face.fontVariationSettings }
			: {} ),
	};

	if ( shouldBeRemoved ) {
		return null;
	}

	return (
		<tr className="font-face">
			<td>{ face.fontStyle }</td>
			<td>{ face.fontWeight }</td>
			<td className="demo-cell">
				<Demo style={ demoStyles } />
			</td>
			{ deleteFont && (
				<td>
					<Button
						variant="tertiary"
						onClick={ deleteFont }
						tabindex={ isFamilyOpen ? 0 : -1 }
					>
						{ __( 'Remove', 'create-block-theme' ) }
					</Button>
				</td>
			) }
		</tr>
	);
}

export default FontFace;
