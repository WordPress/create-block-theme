/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalConfirmDialog as ConfirmDialog,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Button,
	CheckboxControl,
	Card,
	CardBody,
} from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { useState } from '@wordpress/element';
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
	const { createErrorNotice, createSuccessNotice } =
		useDispatch( noticesStore );
	const [ isConfirmDialogOpen, setIsConfirmDialogOpen ] = useState( false );

	const handleTogglePreference = ( key ) => {
		setPreferences( PREFERENCE_SCOPE, PREFERENCE_KEY, {
			...preferences,
			[ key ]: ! preferences[ key ],
		} );
	};

	const toggleConfirmDialog = () => {
		setIsConfirmDialogOpen( ! isConfirmDialogOpen );
	};

	const handleResetTheme = async () => {
		try {
			await resetTheme( preferences );
			toggleConfirmDialog();
			createSuccessNotice(
				__(
					'Theme reset successfully. The editor will now reload.',
					'create-block-theme'
				),
				{ type: 'snackbar' }
			);
			setTimeout( () => {
				window.location.reload();
			}, 1000 );
		} catch ( error ) {
			createErrorNotice(
				__(
					'An error occurred while resetting the theme.',
					'create-block-theme'
				),
				{ type: 'snackbar' }
			);
		}
	};

	return (
		<>
			<ConfirmDialog
				isOpen={ isConfirmDialogOpen }
				confirmButtonText={ __( 'Reset', 'create-block-theme' ) }
				onCancel={ toggleConfirmDialog }
				onConfirm={ handleResetTheme }
				size="medium"
			>
				{ __(
					'Are you sure you want to reset the theme? This action cannot be undone.',
					'create-block-theme'
				) }
			</ConfirmDialog>
			<ScreenHeader title={ __( 'Reset Theme', 'create-block-theme' ) } />
			<Card size="small" isBorderless>
				<CardBody>
					<VStack spacing={ 4 }>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __(
								'Reset theme styles',
								'create-block-theme'
							) }
							help={ __(
								'Reset customizations to theme styles and settings.',
								'create-block-theme'
							) }
							checked={ preferences.resetStyles }
							onChange={ () =>
								handleTogglePreference( 'resetStyles' )
							}
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
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
							__nextHasNoMarginBottom
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
							variant="primary"
							icon={ trash }
							disabled={
								! preferences.resetStyles &&
								! preferences.resetTemplates &&
								! preferences.resetTemplateParts
							}
							onClick={ toggleConfirmDialog }
						>
							{ __( 'Reset Theme', 'create-block-theme' ) }
						</Button>
					</VStack>
				</CardBody>
			</Card>
		</>
	);
}

export default ResetTheme;
