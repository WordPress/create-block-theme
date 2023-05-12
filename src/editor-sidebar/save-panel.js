import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
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
	Button,
	PanelBody,
} from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';

export const SaveThemePanel = () => {
	const { createErrorNotice } = useDispatch( noticesStore );

	const handleSaveClick = () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/save',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			parse: false,
		};

		async function saveTheme() {
			try {
				const response = await apiFetch( fetchOptions );
				downloadFile( response );
			} catch ( error ) {
				const errorMessage =
					error.message && error.code !== 'unknown_error'
						? error.message
						: __(
								'An error occurred while attempting to save the theme.'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		saveTheme().then( () => {
			createInfoNotice(
				__(
					'Theme saved successfully. The editor will now reload.',
					'create-block-theme'
				),
				{
					onDismiss: () => {
						window.location.reload();
					},
				}
			);
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
