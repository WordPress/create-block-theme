import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
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
	Button,
	PanelBody,
} from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';

export const SaveThemePanel = () => {
	const { createErrorNotice } = useDispatch( noticesStore );

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
				<NavigatorToParentButton icon={ chevronLeft } isSmall>
					{ __( 'Back', 'create-block-theme' ) }
				</NavigatorToParentButton>
			</Heading>
			<Text variant="muted">
				{ __(
					'Save user changes (including Templates and Global Styles) to the theme.',
					'create-block-theme'
				) }
			</Text>
			<Spacer />
			<Button variant="secondary" onClick={ handleSaveClick }>
				{ __( 'Save Theme', 'create-block-theme' ) }
			</Button>
		</PanelBody>
	);
};
