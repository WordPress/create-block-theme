import { __ } from '@wordpress/i18n';
import { useEffect, useContext } from '@wordpress/element';
import { ManageFontsContext } from '../fonts-context';
import Demo from '../demo-text-input/demo';

function FontVariant( { font, variant, isSelected, handleToggle } ) {
	const style = variant.includes( 'italic' ) ? 'italic' : 'normal';
	const weight =
		variant === 'regular' || variant === 'italic'
			? '400'
			: variant.replace( 'italic', '' );
	// Force https because sometimes Google Fonts API returns http instead of https
	const variantUrl = font.files[ variant ].replace( 'http://', 'https://' );
	const previewStyles = {
		fontFamily: font.family,
		fontStyle: style,
		fontWeight: weight,
	};

	useEffect( () => {
		const newFont = new FontFace( font.family, `url( ${ variantUrl } )`, {
			style,
			weight,
		} );
		newFont
			.load()
			.then( function ( loaded_face ) {
				document.fonts.add( loaded_face );
			} )
			.catch( function ( error ) {
				console.error( error );
			} );
	}, [ font, variant ] );

	return (
		<tr>
			<td className="">
				<input
					type="checkbox"
					name="google-font-variant"
					value={ variant }
					checked={ isSelected }
					onClick={ handleToggle }
				/>
			</td>
			<td className="">{ weight }</td>
			<td className="">{ style }</td>
			<td className="demo-cell">
				<Demo style={ previewStyles } />
			</td>
		</tr>
	);
}

export default FontVariant;
