/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	PanelBody,
	Button,
	CheckboxControl,
} from '@wordpress/components';
import { archive } from '@wordpress/icons';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';

const PREFERENCE_SCOPE = 'create-block-theme';
const PREFERENCE_KEY = 'save-changes';

export const SaveThemePanel = () => {
	const preference = useSelect( ( select ) => {
		const _preference = select( preferencesStore ).get(
			PREFERENCE_SCOPE,
			PREFERENCE_KEY
		);
		return {
			saveStyle: _preference?.saveStyle ?? true,
			saveTemplates: _preference?.saveTemplates ?? true,
			savePatterns: _preference?.savePatterns ?? true,
			processOnlySavedTemplates:
				_preference?.processOnlySavedTemplates ?? true,
			saveFonts: _preference?.saveFonts ?? true,
			removeNavRefs: _preference?.removeNavRefs ?? false,
			localizeText: _preference?.localizeText ?? false,
			localizeImages: _preference?.localizeImages ?? false,
		};
	}, [] );

	const { createErrorNotice } = useDispatch( noticesStore );
	const { set: setPreference } = useDispatch( preferencesStore );

	const handleTogglePreference = ( key ) => {
		setPreference( PREFERENCE_SCOPE, PREFERENCE_KEY, {
			...preference,
			[ key ]: ! preference[ key ],
		} );
	};

	const handleSaveClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/save',
			method: 'POST',
			data: preference,
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( () => {
				// eslint-disable-next-line no-alert
				window.alert(
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
			<ScreenHeader
				title={ __( 'Save Changes', 'create-block-theme' ) }
			/>
			<VStack>
				<CheckboxControl
					label={ __( 'Save Fonts', 'create-block-theme' ) }
					help={ __(
						'Save activated fonts in the Font Library to the theme. Remove deactivated theme fonts from the theme.',
						'create-block-theme'
					) }
					checked={ preference.saveFonts }
					onChange={ () => handleTogglePreference( 'saveFonts' ) }
				/>
				<CheckboxControl
					label={ __( 'Save Style Changes', 'create-block-theme' ) }
					help={ __(
						'Save Global Styles values set in the Editor to the theme.',
						'create-block-theme'
					) }
					checked={ preference.saveStyle }
					onChange={ () => handleTogglePreference( 'saveStyle' ) }
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
					checked={ preference.saveTemplates }
					onChange={ () => handleTogglePreference( 'saveTemplates' ) }
				/>
				<CheckboxControl
					label={ __( 'Save My Patterns', 'create-block-theme' ) }
					help={ __(
						'Save custom patterns created in the Editor, listed under "My patterns", to the theme.',
						'create-block-theme'
					) }
					checked={ preference.savePatterns }
					onChange={ () => handleTogglePreference( 'savePatterns' ) }
				/>
				<CheckboxControl
					label={ __(
						'Process Only Modified Templates',
						'create-block-theme'
					) }
					help={ __(
						'Process only templates you have modified in the Editor. Any templates you have not modified will be left as is.',
						'create-block-theme'
					) }
					disabled={ ! preference.saveTemplates }
					checked={
						preference.saveTemplates &&
						preference.processOnlySavedTemplates
					}
					onChange={ () =>
						handleTogglePreference( 'processOnlySavedTemplates' )
					}
				/>
				<CheckboxControl
					label={ __( 'Localize Text', 'create-block-theme' ) }
					help={ __(
						'Any text in a template will be copied to a pattern and localized.',
						'create-block-theme'
					) }
					disabled={ ! preference.saveTemplates }
					checked={
						preference.saveTemplates && preference.localizeText
					}
					onChange={ () => handleTogglePreference( 'localizeText' ) }
				/>
				<CheckboxControl
					label={ __( 'Localize Images', 'create-block-theme' ) }
					help={ __(
						'Any images in a template will be copied to a local /assets folder and referenced from there via a pattern.',
						'create-block-theme'
					) }
					disabled={ ! preference.saveTemplates }
					checked={
						preference.saveTemplates && preference.localizeImages
					}
					onChange={ () =>
						handleTogglePreference( 'localizeImages' )
					}
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
					disabled={ ! preference.saveTemplates }
					checked={
						preference.saveTemplates && preference.removeNavRefs
					}
					onChange={ () => handleTogglePreference( 'removeNavRefs' ) }
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
