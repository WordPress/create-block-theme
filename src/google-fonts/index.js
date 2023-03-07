import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { SelectControl, Spinner } from '@wordpress/components';

import SelectedVariantsOutline from './selected-variants-outline';
import FontVariant from './font-variant';
import {
	getWeightFromGoogleVariant,
	getStyleFromGoogleVariant,
	forceHttps,
	getGoogleVariantFromStyleAndWeight,
} from './utils';
import DemoTextInput from '../demo-text-input';
import FontsPageLayout from '../fonts-page-layout';
import './google-fonts.css';

const EMPTY_SELECTION_DATA = {};

// const selectionDataExample = {
// 	"Abel sans": {
// 		"family": "Abel sans",
// 		"faces": [
// 			{
// 				"weight": "400",
// 				"style": "normal",
// 				"src": "https://fonts.gstatic.com/s/abel/v11/MwQ5bhbm2POE6VhLPJp6qGI.ttf"
// 			}
// 		]
// 	}
// };

function GoogleFonts() {
	const [ googleFontsData, setGoogleFontsData ] = useState( {} );
	const [ selectedFont, setSelectedFont ] = useState( null );
	const [ selectionData, setSelectionData ] =
		useState( EMPTY_SELECTION_DATA );

	// pickup the nonce from the input printed in the server
	const nonce = document.querySelector( '#nonce' ).value;

	const handleToggleAllVariants = ( family ) => {
		const existingFamily = selectionData[ family ];
		if ( existingFamily && !! existingFamily?.faces?.length ) {
			setSelectionData( {
				...selectionData,
				[ family ]: {
					faces: [],
				},
			} );
		} else {
			const newFamily = {
				family,
				faces: selectedFont.variants.map( ( variant ) => {
					return {
						weight: getWeightFromGoogleVariant( variant ),
						style: getStyleFromGoogleVariant( variant ),
						src: forceHttps( selectedFont.files[ variant ] ),
					};
				} ),
			};
			setSelectionData( {
				...selectionData,
				[ family ]: newFamily,
			} );
		}
	};

	const isVariantSelected = ( family, weight, style ) => {
		const existingFamily = selectionData[ family ];
		if ( existingFamily ) {
			const existingFace = existingFamily.faces.find( ( face ) => {
				return face.weight === weight && face.style === style;
			} );
			return !! existingFace;
		}
		return false;
	};

	const addVariant = ( family, weight, style ) => {
		const existingFamily = selectionData[ family ];
		const variant = getGoogleVariantFromStyleAndWeight( style, weight );
		if ( existingFamily ) {
			setSelectionData( {
				...selectionData,
				[ family ]: {
					...existingFamily,
					faces: [
						...( existingFamily?.faces || [] ),
						{
							weight,
							style,
							src: forceHttps( selectedFont.files[ variant ] ),
						},
					],
				},
			} );
		} else {
			setSelectionData( {
				...selectionData,
				[ family ]: {
					family,
					faces: [
						{
							weight,
							style,
							src: forceHttps( selectedFont.files[ variant ] ),
						},
					],
				},
			} );
		}
	};

	const removeVariant = ( family, weight, style ) => {
		const existingFamily = selectionData[ family ];
		const newFaces = existingFamily.faces.filter(
			( face ) => ! ( face.weight === weight && face.style === style )
		);
		if ( ! newFaces.length ) {
			const { [ family ]: removedFamily, ...newSelectionData } =
				selectionData;
			setSelectionData( newSelectionData );
		} else {
			setSelectionData( {
				...selectionData,
				[ family ]: {
					...existingFamily,
					faces: newFaces,
				},
			} );
		}
	};

	const handleToggleVariant = ( family, weight, style ) => {
		if ( isVariantSelected( family, weight, style ) ) {
			removeVariant( family, weight, style );
		} else {
			addVariant( family, weight, style );
		}
	};

	// Load google fonts data
	useEffect( () => {
		( async () => {
			const responseData = await fetch(
				createBlockTheme.googleFontsDataUrl
			);
			const parsedData = await responseData.json();
			setGoogleFontsData( parsedData );
		} )();
	}, [] );

	const theme = useSelect( ( select ) => {
		return select( coreDataStore ).getCurrentTheme();
	}, null );

	const handleSelectChange = ( value ) => {
		setSelectedFont( googleFontsData.items[ value ] );
	};

	return (
		<FontsPageLayout>
			<main>
				<h1 className="wp-heading-inline">
					{ __(
						'Add Google fonts to your theme',
						'create-block-theme'
					) }
				</h1>
				<h3>
					{ __(
						'Add Google fonts assets and font face definitions to your currently active theme',
						'create-block-theme'
					) }{ ' ' }
					({ theme?.name.rendered })
				</h3>
				{ ! googleFontsData?.items && (
					<p>
						<Spinner />
						<span>
							{ __(
								'Loading Google fonts data…',
								'create-block-theme'
							) }
						</span>
					</p>
				) }
				{ googleFontsData?.items && (
					<>
						<div className="select-font">
							<SelectControl
								label={ __(
									'Select Font',
									'create-block-theme'
								) }
								name="google-font"
								onChange={ handleSelectChange }
								size="__unstable-large"
							>
								<option value={ null }>
									{ __(
										'Select a font…',
										'create-block-theme'
									) }
								</option>
								{ googleFontsData.items.map(
									( font, index ) => (
										<option
											value={ index }
											key={ `option${ index }` }
										>
											{ font.family }
										</option>
									)
								) }
							</SelectControl>
						</div>
						<DemoTextInput />
						{ selectedFont && (
							<p>
								{ __(
									'Select the font variants you want to include:',
									'create-block-theme'
								) }
							</p>
						) }
						{ selectedFont && (
							<table
								className="wp-list-table widefat striped table-view-list"
								id="google-fonts-table"
							>
								<thead>
									<tr>
										<td className="">
											<input
												type="checkbox"
												onClick={ () =>
													handleToggleAllVariants(
														selectedFont.family
													)
												}
												checked={
													selectedFont.variants
														.length ===
													selectionData[
														selectedFont.family
													]?.faces?.length
												}
											/>
										</td>
										<td className="">
											{ __(
												'Weight',
												'create-block-theme'
											) }
										</td>
										<td className="">
											{ __(
												'Style',
												'create-block-theme'
											) }
										</td>
										<td className="">
											{ __(
												'Preview',
												'create-block-theme'
											) }
										</td>
									</tr>
								</thead>
								<tbody>
									{ selectedFont.variants.map(
										( variant, i ) => (
											<FontVariant
												font={ selectedFont }
												variant={ variant }
												key={ `font-variant-${ i }` }
												isSelected={ isVariantSelected(
													selectedFont.family,
													getWeightFromGoogleVariant(
														variant
													),
													getStyleFromGoogleVariant(
														variant
													)
												) }
												handleToggle={ () =>
													handleToggleVariant(
														selectedFont.family,
														getWeightFromGoogleVariant(
															variant
														),
														getStyleFromGoogleVariant(
															variant
														)
													)
												}
											/>
										)
									) }
								</tbody>
							</table>
						) }
						<form
							encType="multipart/form-data"
							action=""
							method="POST"
						>
							<input
								type="hidden"
								name="selection-data"
								value={ JSON.stringify(
									Object.values( selectionData )
								) }
							/>
							<input
								type="submit"
								value={ __(
									'Add google fonts to your theme',
									'create-block-theme'
								) }
								className="button button-primary"
								disabled={ false }
							/>
							<input type="hidden" name="nonce" value={ nonce } />
						</form>
					</>
				) }
			</main>

			<div className="sidebar">
				<div className="sidebar-container">
					<SelectedVariantsOutline
						selectionData={ selectionData }
						removeVariant={ handleToggleVariant }
					/>
				</div>
			</div>
		</FontsPageLayout>
	);
}

export default GoogleFonts;
