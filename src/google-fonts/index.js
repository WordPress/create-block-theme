import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { SelectControl, Spinner, Button } from '@wordpress/components';
import { Font } from 'lib-font';

import FontsSidebar from '../fonts-sidebar';
import FontVariant from './font-variant';
import {
	getWeightFromGoogleVariant,
	getStyleFromGoogleVariant,
	forceHttps,
	getGoogleVariantFromStyleAndWeight,
} from '../utils';
import DemoTextInput from '../demo-text-input';
import FontsPageLayout from '../fonts-page-layout';
import './google-fonts.css';
import BackButton from '../manage-fonts/back-button';

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
	const [ selectedFontCredits, setSelectedFontCredits ] = useState( {} );
	const [ selectionData, setSelectionData ] =
		useState( EMPTY_SELECTION_DATA );

	// pickup the nonce from the input printed in the server
	const nonce = document.querySelector( '#nonce' ).value;

	const handleToggleAllVariants = ( family ) => {
		const existingFamily = selectionData[ family ];
		if ( existingFamily && !! existingFamily?.faces?.length ) {
			const { [ family ]: removedFamily, ...rest } = selectionData;
			setSelectionData( rest );
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
			const { [ family ]: removedFamily, ...rest } = selectionData;
			setSelectionData( rest );
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

	const getFontCredits = ( selectedFontObj ) => {
		const fontObj = new Font( selectedFontObj.family );
		let fontError = false;

		// Load font file
		fontObj.src = Object.values( selectedFontObj.files )[ 0 ];
		fontObj.onerror = ( event ) => {
			// eslint-disable-next-line no-console
			console.error( event );
			fontError = true;
		};

		if ( ! fontError ) {
			fontObj.onload = ( event ) => getFontData( event );

			function getFontData( event ) {
				const font = event.detail.font;
				const nameTable = font.opentype.tables.name;

				const fontCredits = {
					copyright: nameTable.get( 0 ),
					source: nameTable.get( 11 ),
					license: nameTable.get( 13 ),
					licenseURL: nameTable.get( 14 ),
				};

				setSelectedFontCredits( fontCredits );
			}
		}
	};

	const handleSelectChange = ( value ) => {
		setSelectedFont( googleFontsData.items[ value ] );
		getFontCredits( googleFontsData.items[ value ] );
	};

	let selectedFontFamilyId = '';
	if ( selectedFont ) {
		selectedFontFamilyId = selectedFont.family
			.toLowerCase()
			.replace( ' ', '-' );
	}

	return (
		<FontsPageLayout>
			<main>
				<header>
					<BackButton />
					<h1 className="wp-heading-inline">
						{ __(
							'Add Google fonts to your theme',
							'create-block-theme'
						) }
					</h1>
					<p>
						{ __(
							'Add Google fonts assets and font face definitions to your currently active theme',
							'create-block-theme'
						) }{ ' ' }
						({ theme?.name.rendered })
					</p>
				</header>
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
												id={ `select-all-${ selectedFontFamilyId }` }
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
											<label
												htmlFor={ `select-all-${ selectedFontFamilyId }` }
											>
												{ __(
													'Weight',
													'create-block-theme'
												) }
											</label>
										</td>
										<td className="">
											<label
												htmlFor={ `select-all-${ selectedFontFamilyId }` }
											>
												{ __(
													'Style',
													'create-block-theme'
												) }
											</label>
										</td>
										<td className="">
											<label
												htmlFor={ `select-all-${ selectedFontFamilyId }` }
											>
												{ __(
													'Preview',
													'create-block-theme'
												) }
											</label>
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
								type="hidden"
								name="font-credits"
								value={ JSON.stringify( selectedFontCredits ) }
							/>
							<Button
								variant="primary"
								type="submit"
								disabled={
									Object.values( selectionData ).length === 0
								}
							>
								{ __(
									'Add Google fonts to your theme',
									'create-block-theme'
								) }
							</Button>
							<input type="hidden" name="nonce" value={ nonce } />
						</form>
					</>
				) }
			</main>

			<FontsSidebar
				title={ __( 'Selected Variants', 'create-block-theme' ) }
				fontsOutline={ selectionData }
				handleDeleteFontFace={ handleToggleVariant }
				handleDeleteFontFamily={ handleToggleAllVariants }
			/>
		</FontsPageLayout>
	);
}

export default GoogleFonts;
