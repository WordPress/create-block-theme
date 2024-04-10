import { useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { __, _x } from '@wordpress/i18n';
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
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
	// eslint-disable-next-line
	__experimentalHStack as HStack,
	// eslint-disable-next-line
	__experimentalText as Text,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	Button,
	Icon,
	FlexItem,
	PanelBody,
} from '@wordpress/components';

import { UpdateThemePanel } from './editor-sidebar/update-panel';
import { CreateThemePanel } from './editor-sidebar/create-panel';
import ThemeJsonEditorModal from './editor-sidebar/json-editor-modal';

import {
	tool,
	copy,
	download,
	edit,
	chevronRight,
	chevronLeft,
	archive,
} from '@wordpress/icons';

const CreateBlockThemePlugin = () => {
	const [ isEditorOpen, setIsEditorOpen ] = useState( false );
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
								'An error occurred while attempting to export the theme.',
								'create-block-theme'
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
				{ _x(
					'Create Block Theme',
					'UI String',
					'create-block-theme'
				) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name="create-block-theme-sidebar"
				icon={ tool }
				title={ _x(
					'Create Block Theme',
					'UI String',
					'create-block-theme'
				) }
			>
				<NavigatorProvider initialPath="/">
					<NavigatorScreen path="/">
						<PanelBody>
							<VStack spacing={ 0 }>
								<Button
									icon={ archive }
									onClick={ handleSaveClick }
								>
									{ __(
										'Save Changes',
										'create-block-theme'
									) }
								</Button>
								<Text variant="muted">
									{ __(
										'Save user changes (including Templates and Global Styles) to the theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/create" icon={ copy }>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a new theme based on your current theme or create a new blank theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/create/variation"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create Theme Variation',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Save the Global Styles changes as a theme variation.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/export" icon={ copy }>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Export Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Export your theme as a zip file.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/update" icon={ edit }>
									<Spacer />
									<HStack justify="space-between">
										<FlexItem>
											{ __(
												'Theme Info',
												'create-block-theme'
											) }
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
								<Button
									icon={ edit }
									onClick={ () => setIsEditorOpen( true ) }
								>
									{ __(
										'Inspect Theme JSON',
										'create-block-theme'
									) }
								</Button>
								<Text variant="muted">
									{ __(
										'Open the theme.json file to inspect theme data.',
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
						<PanelBody>
							<Heading>
								<NavigatorToParentButton icon={ chevronLeft }>
									{ __(
										'Create Theme',
										'create-block-theme'
									) }
								</NavigatorToParentButton>
							</Heading>
							<VStack>
								<NavigatorButton
									path="/create/blank"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create Blank Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a blank theme with no styles or templates.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/create/clone"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Clone Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a copy of this theme on the server and activate it. The user changes will be preserved in the new theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/create/child"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create Child Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a child theme on the server and activate it. The user changes will be preserved in the new theme.',
										'create-block-theme'
									) }
								</Text>
							</VStack>
						</PanelBody>
					</NavigatorScreen>
					<NavigatorScreen path="/create/blank">
						<CreateThemePanel createType={ 'createBlank' } />
					</NavigatorScreen>
					<NavigatorScreen path="/create/clone">
						<CreateThemePanel createType={ 'createClone' } />
					</NavigatorScreen>
					<NavigatorScreen path="/create/child">
						<CreateThemePanel createType={ 'createChild' } />
					</NavigatorScreen>
					<NavigatorScreen path="/create/variation">
						<CreateThemePanel createType={ 'createVariation' } />
					</NavigatorScreen>
					<NavigatorScreen path="/export">
						<PanelBody>
							<Heading>
								<NavigatorToParentButton icon={ chevronLeft }>
									{ __(
										'Export Theme',
										'create-block-theme'
									) }
								</NavigatorToParentButton>
							</Heading>
							<VStack>
								<Button
									icon={ download }
									onClick={ handleExportClick }
								>
									{ __( 'Export Zip', 'create-block-theme' ) }
								</Button>
								<Text variant="muted">
									{ __(
										'Export your theme as a zip file. The user changes will NOT be preserved in the new theme. To include those save the theme first.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/export/clone"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Export Clone',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Export a copy of this theme with new MetaData as a .zip file. The user changes will be preserved in the new theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/export/child"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Export Child',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Export a child of this theme as a .zip file. The user changes will be preserved in the new theme.',
										'create-block-theme'
									) }
								</Text>
							</VStack>
						</PanelBody>
					</NavigatorScreen>
					<NavigatorScreen path="/export/clone">
						<CreateThemePanel createType={ 'exportClone' } />
					</NavigatorScreen>
					<NavigatorScreen path="/export/child">
						<CreateThemePanel createType={ 'exportChild' } />
					</NavigatorScreen>
				</NavigatorProvider>
			</PluginSidebar>
			{ isEditorOpen && (
				<ThemeJsonEditorModal
					onRequestClose={ () => setIsEditorOpen( false ) }
				/>
			) }
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
