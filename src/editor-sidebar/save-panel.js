import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';
import {
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	// eslint-disable-next-line
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
	PanelBody,
	Button,
	CheckboxControl,
} from '@wordpress/components';
import { chevronLeft, archive } from '@wordpress/icons';

export const SaveThemePanel = () => {
	const [ saveOptions, setSaveOptions ] = useState( {
		saveStyle: true,
		saveTemplates: true,
		processOnlySavedTemplates: true,
		saveFonts: true,
		removeNavRefs: false,
		localizeText: false,
		localizeImages: false,
	} );

	const { createErrorNotice } = useDispatch( noticesStore );

	const handleSaveClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/save',
			method: 'POST',
			data: saveOptions,
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( () => {
				// eslint-disable-next-line
				alert(
					__(
						'Theme saved successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to save the theme.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	return (
		<PanelBody>
			<Heading>
				<NavigatorToParentButton icon={ chevronLeft }>
					{ __( 'Save Changes', 'create-block-theme' ) }
				</NavigatorToParentButton>
			</Heading>

			<VStack>
				<CheckboxControl
					label={ __( 'Save Fonts', 'create-block-theme' ) }
					help={ __(
						'Save activated fonts in the Font Library to the theme. Remove deactivated theme fonts from the theme.',
						'create-block-theme'
					) }
					checked={ saveOptions.saveFonts }
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							saveFonts: ! saveOptions.saveFonts,
						} );
					} }
				/>
				<CheckboxControl
					label={ __( 'Save Style Changes', 'create-block-theme' ) }
					help={ __(
						'Save Global Styles values set in the Editor to the theme.',
						'create-block-theme'
					) }
					checked={ saveOptions.saveStyle }
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							saveStyle: ! saveOptions.saveStyle,
						} );
					} }
				/>
				<CheckboxControl
					label={ __(
						'Save Template Changes',
						'create-block-theme'
					) }
					help={ __(
						'Save Template and Template Part changes made in the Editor to the theme.',
						'create-block-theme'
					) }
					checked={ saveOptions.saveTemplates }
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							saveTemplates: ! saveOptions.saveTemplates,
						} );
					} }
				/>
				<CheckboxControl
					label="Process Only Modified Templates"
					help="Process only templates you have modified in the Editor. Any templates you have not modified will be left as is."
					disabled={ ! saveOptions.saveTemplates }
					checked={
						saveOptions.saveTemplates &&
						saveOptions.processOnlySavedTemplates
					}
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							processOnlySavedTemplates:
								! saveOptions.processOnlySavedTemplates,
						} );
					} }
				/>
				<CheckboxControl
					label={ __( 'Localize Text', 'create-block-theme' ) }
					help={ __(
						'Any text in a template will be copied to a pattern and localized.',
						'create-block-theme'
					) }
					disabled={ ! saveOptions.saveTemplates }
					checked={
						saveOptions.saveTemplates && saveOptions.localizeText
					}
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							localizeText: ! saveOptions.localizeText,
						} );
					} }
				/>
				<CheckboxControl
					label={ __( 'Localize Images', 'create-block-theme' ) }
					help={ __(
						'Any images in a template will be copied to a local /assets folder and referenced from there via a pattern.',
						'create-block-theme'
					) }
					disabled={ ! saveOptions.saveTemplates }
					checked={
						saveOptions.saveTemplates && saveOptions.localizeImages
					}
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							localizeImages: ! saveOptions.localizeImages,
						} );
					} }
				/>
				<CheckboxControl
					label={ __(
						'Remove Navigation Refs',
						'create-block-theme'
					) }
					help={ __(
						'Remove Navigation Refs from the theme returning your navigation to the default state.',
						'create-block-theme'
					) }
					disabled={ ! saveOptions.saveTemplates }
					checked={
						saveOptions.saveTemplates && saveOptions.removeNavRefs
					}
					onChange={ () => {
						setSaveOptions( {
							...saveOptions,
							removeNavRefs: ! saveOptions.removeNavRefs,
						} );
					} }
				/>
				<Button
					variant="primary"
					icon={ archive }
					onClick={ handleSaveClick }
				>
					{ __( 'Save Changes', 'create-block-theme' ) }
				</Button>
			</VStack>
		</PanelBody>
	);
};
