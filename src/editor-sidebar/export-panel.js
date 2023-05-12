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

export const ExportThemePanel = () => {
	const { createErrorNotice } = useDispatch( noticesStore );

	const handleExportClick = () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/export',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			parse: false,
		};

		async function exportTheme() {
			try {
				const response = await apiFetch( fetchOptions );
				downloadFile( response );
			} catch ( error ) {
				const errorMessage =
					error.message && error.code !== 'unknown_error'
						? error.message
						: __(
								'An error occurred while attempting to export the theme.'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		exportTheme();
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
					'Export your theme as a zip file. Note: You may want to save your user changes to the theme first.',
					'create-block-theme'
				) }
			</Text>
			<Spacer />
			<Button variant="secondary" onClick={ handleExportClick }>
				{ __( 'Export', 'create-block-theme' ) }
			</Button>
		</PanelBody>
	);
};
