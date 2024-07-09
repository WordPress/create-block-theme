/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	PanelBody,
	Button,
	CheckboxControl,
} from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';
import { resetTheme } from '../resolvers';

const PREFERENCE_SCOPE = 'create-block-theme';
const PREFERENCE_KEY = 'reset-theme';

function ResetTheme() {
	const preferences = useSelect( ( select ) => {
		const _preference = select( preferencesStore ).get(
			PREFERENCE_SCOPE,
			PREFERENCE_KEY
		);
		return {
			resetStyles: _preference?.resetStyles ?? true,
			resetTemplates: _preference?.resetTemplates ?? true,
			resetTemplateParts: _preference?.resetTemplateParts ?? true,
		};
	}, [] );

	const { set: setPreferences } = useDispatch( preferencesStore );
	const { createErrorNotice } = useDispatch( noticesStore );

	const handleTogglePreference = ( key ) => {
		setPreferences( PREFERENCE_SCOPE, PREFERENCE_KEY, {
			...preferences,
			[ key ]: ! preferences[ key ],
		} );
	};

	const handleResetTheme = async () => {
		try {
			await resetTheme( preferences );
			// eslint-disable-next-line no-alert
			window.alert(
				__(
					'Theme reseted successfully. The editor will now reload.',
					'create-block-theme'
				)
			);
			window.location.reload();
		} catch ( error ) {
			createErrorNotice(
				__(
					'An error occurred while resetting theme.',
					'create-block-theme'
				)
			);
		}
	};

	return (
		<PanelBody>
			<ScreenHeader title={ __( 'Reset Theme', 'create-block-theme' ) } />
			<VStack>
				<CheckboxControl
					label={ __( 'Reset theme styles', 'create-block-theme' ) }
					help={ __(
						'Reset customizations to theme styles and settings.',
						'create-block-theme'
					) }
					checked={ preferences.resetStyles }
					onChange={ () => handleTogglePreference( 'resetStyles' ) }
				/>

				<CheckboxControl
					label={ __(
						'Reset theme templates',
						'create-block-theme'
					) }
					help={ __(
						'Reset customizations to theme templates.',
						'create-block-theme'
					) }
					checked={ preferences.resetTemplates }
					onChange={ () =>
						handleTogglePreference( 'resetTemplates' )
					}
				/>

				<CheckboxControl
					label={ __(
						'Reset theme template-parts',
						'create-block-theme'
					) }
					help={ __(
						'Reset customizations to theme template-parts.',
						'create-block-theme'
					) }
					checked={ preferences.resetTemplateParts }
					onChange={ () =>
						handleTogglePreference( 'resetTemplateParts' )
					}
				/>

				<Button
					text={ __( 'Reset theme', 'create-block-theme' ) }
					variant="primary"
					icon={ trash }
					disabled={
						! preferences.resetStyles &&
						! preferences.resetTemplates &&
						! preferences.resetTemplateParts
					}
					onClick={ handleResetTheme }
				/>
			</VStack>
		</PanelBody>
	);
}

export default ResetTheme;
