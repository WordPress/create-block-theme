import { useContext } from '@wordpress/element';
import { Button, Icon } from '@wordpress/components';
import FontFace from './font-face';
import { ManageFontsContext } from '../fonts-context';
import { __, _n } from '@wordpress/i18n';
import { chevronUp, chevronDown } from '@wordpress/icons';

function FontFamily( { fontFamily, deleteFont } ) {
	const { familiesOpen, handleToggleFamily } =
		useContext( ManageFontsContext );
	const isOpen =
		familiesOpen.includes( fontFamily.name ) ||
		familiesOpen.includes( fontFamily.fontFamily );

	const toggleIsOpen = () => {
		handleToggleFamily( fontFamily.name || fontFamily.fontFamily );
	};

	if ( fontFamily.shouldBeRemoved ) {
		return null;
	}

	const hasFontFaces =
		!! fontFamily.fontFace && !! fontFamily.fontFace.length;

	return (
		<table className="wp-list-table widefat table-view-list">
			{ /* TODO: Add keyboard event to fix accessibility issue */ }
			{ /* eslint-disable-next-line */ }
			<thead onClick={ toggleIsOpen }>
				<tr>
					<td className="font-family-head">
						<div>
							<strong>
								{ fontFamily.name || fontFamily.fontFamily }
							</strong>
							{ hasFontFaces && (
								<span className="variants-count">
									{ ' ' }
									{ sprintf(
										// translators: %s: Variants information.
										__( '( %s )', 'create-block-theme' ),
										sprintf(
											// translators: %d: Number of variants.
											_n(
												'%d Variant',
												'%d Variants',
												fontFamily.fontFace.length,
												'create-block-theme'
											),
											fontFamily.fontFace.length
										)
									) }
								</span>
							) }
						</div>
						<div>
							<Button
								variant="tertiary"
								onClick={ ( e ) => {
									e.stopPropagation();
									deleteFont(
										fontFamily.name || fontFamily.fontFamily
									);
								} }
							>
								{ __(
									'Remove Font Family',
									'create-block-theme'
								) }
							</Button>
							<Button onClick={ toggleIsOpen }>
								<Icon
									icon={ isOpen ? chevronUp : chevronDown }
								/>
							</Button>
						</div>
					</td>
				</tr>
			</thead>
			<tbody className="font-family-contents">
				<tr className="container">
					<td className={ ` slide ${ isOpen ? 'open' : 'close' }` }>
						<table className="wp-list-table widefat striped table-view-list">
							<thead>
								<tr>
									<td>
										{ __( 'Style', 'create-block-theme' ) }
									</td>
									<td>
										{ __( 'Weight', 'create-block-theme' ) }
									</td>
									<td className="preview-head">
										{ __(
											'Preview',
											'create-block-theme'
										) }
									</td>
									{ hasFontFaces && <td></td> }
								</tr>
							</thead>
							<tbody>
								{ hasFontFaces &&
									fontFamily.fontFace.map(
										( fontFace, i ) => {
											if ( fontFace.shouldBeRemoved ) {
												return null;
											}
											return (
												<FontFace
													face={ fontFace }
													key={ `fontface${ i }` }
													deleteFont={ () =>
														deleteFont(
															fontFamily.name ||
																fontFamily.fontFamily,
															fontFace.fontWeight,
															fontFace.fontStyle
														)
													}
													isFamilyOpen={ isOpen }
												/>
											);
										}
									) }
								{ ! hasFontFaces && fontFamily.fontFamily && (
									<FontFace
										face={ fontFamily }
										isFamilyOpen={ isOpen }
									/>
								) }
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
	);
}

export default FontFamily;
