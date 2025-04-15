/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalView as View,
	PanelBody,
	Button,
	TextControl,
	CheckboxControl,
} from '@wordpress/components';
import { copy } from '@wordpress/icons';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { postCreateThemeVariation } from '../resolvers';
import ScreenHeader from './screen-header';

const PREFERENCE_SCOPE = 'create-block-theme';
const PREFERENCE_KEY = 'create-variation';

export const CreateVariationPanel = () => {
	const { createErrorNotice } = useDispatch( noticesStore );

	const [ theme, setTheme ] = useState( {
		name: '',
	} );

	const preference = useSelect( ( select ) => {
		const _preference = select( preferencesStore ).get(
			PREFERENCE_SCOPE,
			PREFERENCE_KEY
		);
		return {
			saveFonts: _preference?.saveFonts ?? true,
		};
	}, [] );

	const handleTogglePreference = ( key ) => {
		setPreference( PREFERENCE_SCOPE, PREFERENCE_KEY, {
			...preference,
			[ key ]: ! preference[ key ],
		} );
	};

	const { set: setPreference } = useDispatch( preferencesStore );

	const handleCreateVariationClick = () => {
		const variationPreferences = {
			name: theme.name,
			...preference,
		};

		postCreateThemeVariation( variationPreferences )
			.then( () => {
				// eslint-disable-next-line no-alert
				window.alert(
					__(
						'Theme variation created successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to create the theme variation.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	return (
		<PanelBody>
			<ScreenHeader
				title={ __( 'Create Variation', 'create-block-theme' ) }
			/>

			<VStack>
				<Text as="p">
					{ __(
						'Save the Global Styles changes as a theme variation.',
						'create-block-theme'
					) }
				</Text>

				<View>
					<Spacer paddingY={ 4 }>
						<VStack spacing={ 4 }>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __(
									'Variation name',
									'create-block-theme'
								) }
								value={ theme.name }
								onChange={ ( value ) =>
									setTheme( { ...theme, name: value } )
								}
							/>

							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __(
									'Save Fonts',
									'create-block-theme'
								) }
								help={ __(
									'Copy the font assets to the theme folder.',
									'create-block-theme'
								) }
								checked={ preference.saveFonts }
								onChange={ () =>
									handleTogglePreference( 'saveFonts' )
								}
							/>

							<Button
								icon={ copy }
								variant="primary"
								onClick={ handleCreateVariationClick }
							>
								{ __(
									'Create Theme Variation',
									'create-block-theme'
								) }
							</Button>
						</VStack>
					</Spacer>
				</View>
			</VStack>
		</PanelBody>
	);
};
