import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { downloadFile } from '../utils';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	// eslint-disable-next-line
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
	PanelBody,
	Button,
	TextControl,
	TextareaControl,
	CheckboxControl,
} from '@wordpress/components';
import { chevronLeft, archive, addCard, download, copy } from '@wordpress/icons';



export const SaveThemePanel = ( ) => {

const [ saveOptions, setSaveOptions ] = useState( {
	saveStyle: true,
	saveTemplates: true,
	saveFonts: true,
	removeNavRefs: true,
	localizeText: true,
} );

const handleSaveClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/save',
			method: 'POST',
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
						{__( 'Save Changes', 'create-block-theme' )  }
				</NavigatorToParentButton>
			</Heading>

			<VStack>
				<CheckboxControl
					label="Save Style Changes"
					help="Save Global Styles values set in the Editor to the theme."
					checked={saveOptions.saveStyle}
					onChange={ ()=>{ setSaveOptions({ ...saveOptions, saveStyle: !saveOptions.saveStyle }) } }
				/>
				<CheckboxControl
					label="Save Template Changes"
					help="Save Template and Template Part changes made in the Editor to the theme."
					checked={saveOptions.saveTemplates}
					onChange={ ()=>{ setSaveOptions({ ...saveOptions, saveTemplates: !saveOptions.saveTemplates }) } }
				/>
				<CheckboxControl
					label="Localize Text"
					help="Any text in a template will be copied to a pattern and localized."
					checked={saveOptions.localizeText}
					onChange={ ()=>{ setSaveOptions({ ...saveOptions, localizeText: !saveOptions.localizeText }) } }
				/>
				<CheckboxControl
					label="Save Fonts"
					help="Save activated fonts in the Font Library to the theme. Remove deactivated theme fonts from the theme."
					checked={saveOptions.saveFonts}
					onChange={ ()=>{ setSaveOptions({ ...saveOptions, saveFonts: !saveOptions.saveFonts }) } }
				/>
				<CheckboxControl
					label="Remove Navigation Refs"
					help="Remove Navigation Refs from the theme returning your navigation to the default state."
					checked={saveOptions.removeNavRefs}
					onChange={ ()=>{ setSaveOptions({ ...saveOptions, removeNavRefs: !saveOptions.removeNavRefs }) } }
				/>
				<Button
				variant='primary'
									icon={ archive }
									onClick={ handleSaveClick }
								>
									{ __(
										'Save Changes',
										'create-block-theme'
									) }
								</Button>
			</VStack>
		</PanelBody>
	);
};
