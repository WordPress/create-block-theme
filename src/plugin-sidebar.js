import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { downloadFile } from './utils';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalNavigatorProvider as NavigatorProvider,
	// eslint-disable-next-line
	__experimentalNavigatorScreen as NavigatorScreen,
	// eslint-disable-next-line
	__experimentalNavigatorButton as NavigatorButton,
	// eslint-disable-next-line
	__experimentalHStack as HStack,
	// eslint-disable-next-line
	__experimentalText as Text,
	Button,
	Icon,
	FlexItem,
	PanelBody,
} from '@wordpress/components';

import { UpdateThemePanel } from './editor-sidebar/update-panel';
import { CreateThemePanel } from './editor-sidebar/create-panel';
import {
	tool,
	copy,
	download,
	edit,
	chevronRight,
	archive,
} from '@wordpress/icons';

const CreateBlockThemePlugin = () => {
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
		<>
			<PluginSidebarMoreMenuItem
				target="create-block-theme-sidebar"
				icon={ tool }
			>
				{ __( 'Create Block Theme' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name="create-block-theme-sidebar"
				icon={ tool }
				title={ __( 'Create Block Theme' ) }
			>
				<NavigatorProvider initialPath="/">
					<NavigatorScreen path="/">
						<PanelBody>
							<VStack>
								<Button
									icon={ archive }
									onClick={ handleSaveClick }
								>
									{ __( 'Save Changes' ) }
								</Button>
								<Text variant="muted">
									{ __(
										'Save user changes (including Templates and Global Styles) to the theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<Button
									icon={ download }
									onClick={ handleExportClick }
								>
									{ __( 'Export Zip' ) }
								</Button>
								<Text variant="muted">
									{ __(
										'Export your theme as a zip file. Note: You may want to save your user changes to the theme first.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/update" icon={ edit }>
									<Spacer />
									<HStack justify="space-between">
										<FlexItem>
											{ __( 'Theme Info' ) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Edit Metadata properties of your current theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/create" icon={ copy }>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __( 'Create Theme' ) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a new theme based on your current theme and either save it or export it.',
										'create-block-theme'
									) }
								</Text>
							</VStack>
						</PanelBody>
					</NavigatorScreen>

					<NavigatorScreen path="/update">
						<UpdateThemePanel />
					</NavigatorScreen>

					<NavigatorScreen path="/create">
						<CreateThemePanel />
					</NavigatorScreen>
				</NavigatorProvider>
			</PluginSidebar>
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
