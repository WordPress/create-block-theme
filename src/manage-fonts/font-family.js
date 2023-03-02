import { useContext } from '@wordpress/element';
import { Button, Icon } from '@wordpress/components';
import FontFace from './font-face';
import { ManageFontsContext } from '../fonts-context';

const { __, _n } = wp.i18n;
function FontFamily( {
	fontFamily,
	fontFamilyIndex,
	deleteFontFamily,
	deleteFontFace,
} ) {
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
									( { fontFamily.fontFace.length }{ ' ' }
									{ _n(
										'Variant',
										'Variants',
										fontFamily.fontFace.length,
										'create-block-theme'
									) }{ ' ' }
									)
								</span>
							) }
						</div>
						<div>
							<Button
								variant="tertiary"
								onClick={ ( e ) => {
									e.stopPropagation();
									deleteFontFamily( fontFamilyIndex );
								} }
							>
								{ __(
									'Remove Font Family',
									'create-block-theme'
								) }
							</Button>
							<Button onClick={ toggleIsOpen }>
								<Icon
									icon={
										isOpen
											? 'arrow-up-alt2'
											: 'arrow-down-alt2'
									}
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
													{ ...fontFace }
													fontFamilyIndex={
														fontFamilyIndex
													}
													fontFaceIndex={ i }
													key={ `fontface${ i }` }
													deleteFontFace={ () =>
														deleteFontFace(
															fontFamilyIndex,
															i
														)
													}
													isFamilyOpen={ isOpen }
												/>
											);
										}
									) }
								{ ! hasFontFaces && fontFamily.fontFamily && (
									<FontFace
										{ ...fontFamily }
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
