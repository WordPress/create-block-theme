import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line
	__experimentalHStack as HStack,
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
} from '@wordpress/components';
import { chevronLeft, archive } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

export const SaveUserChangesPanel = () => {
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
					{ __( 'Save Changes', 'create-block-theme' ) }
				</NavigatorToParentButton>
			</Heading>

			<VStack>
				<Text>
					{ __(
						'Save user changes to the theme.',
						'create-block-theme'
					) }
				</Text>
				<Spacer />
				<div style={ { display: 'flex', alignItems: 'center' } }>
					<input type="checkbox" id="save-global-styles" />
					<label htmlFor={ `save-global-styles` }>
						{ __(
							'Save global style changes',
							'create-block-theme'
						) }
					</label>
				</div>
				<div style={ { display: 'flex', alignItems: 'center' } }>
					<input type="checkbox" id="save-global-styles" />
					<label htmlFor={ `save-global-styles` }>
						{ __(
							"Remove ref's from navigation menus",
							'create-block-theme'
						) }
					</label>
				</div>
				<Spacer />
				<Button
					icon={ archive }
					variant="secondary"
					onClick={ handleSaveClick }
				>
					{ __( 'Save Changes', 'create-block-theme' ) }
				</Button>
			</VStack>
		</PanelBody>
	);
};
