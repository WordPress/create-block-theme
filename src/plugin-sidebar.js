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
import { SaveThemePanel } from './editor-sidebar/save-panel';
import { CreateVariationPanel } from './editor-sidebar/create-variation-panel';

import {
	tool,
	copy,
	download,
	edit,
	code,
	chevronRight,
	chevronLeft,
	addCard,
	blockMeta,
	archive,
} from '@wordpress/icons';
import { ThemeMetadataEditorModal } from './editor-sidebar/metadata-editor-modal';

const CreateBlockThemePlugin = () => {
	const [ isEditorOpen, setIsEditorOpen ] = useState( false );
	const [ isMetadataEditorOpen, setIsMetadataEditorOpen ] = useState( false );
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
								<NavigatorButton path="/save" icon={ copy }>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Save Changes to Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<NavigatorButton
									path="/create/variation"
									icon={ blockMeta }
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
								<Button
									icon={ edit }
									onClick={ () => setIsMetadataEditorOpen( true ) }
								>
									{ __(
										'MetaData',
										'create-block-theme'
									) }
								</Button>
								<Button
									icon={ code }
									onClick={ () => setIsEditorOpen( true ) }
								>
									{ __(
										'theme.json',
										'create-block-theme'
									) }
								</Button>
								<Button
									icon={ download }
									onClick={ () => setIsEditorOpen( true ) }
								>
									{ __(
										'Export Zip',
										'create-block-theme'
									) }
								</Button>
								<hr></hr>
								<NavigatorButton
									path="/create/blank"
									icon={ addCard }
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
								<NavigatorButton
									path="/clone"
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
							</VStack>
						</PanelBody>
					</NavigatorScreen>

					<NavigatorScreen path="/clone">
						<PanelBody>
							<Heading>
								<NavigatorToParentButton icon={ chevronLeft }>
									{ __(
										'Clone Theme',
										'create-block-theme'
									) }
								</NavigatorToParentButton>
							</Heading>
							<VStack>
								<Text>
									{ __(
										'Would you like to make a Regular Theme or a Child Theme?',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/clone/type"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create Regular Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a clone of this theme with a new name. The user changes will be preserved in the new theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/clone/type"
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
										'Create a child theme that uses this theme as a parent. This theme will remain unchanged and the user changes will be preserved in the new child theme.',
										'create-block-theme'
									) }
								</Text>
							</VStack>
						</PanelBody>
					</NavigatorScreen>

					<NavigatorScreen path="/clone/type">
						<PanelBody>
							<Heading>
								<NavigatorToParentButton icon={ chevronLeft }>
									{ __(
										'Clone Theme',
										'create-block-theme'
									) }
								</NavigatorToParentButton>
							</Heading>
							<VStack>
								<Text>
									{ __(
										'Would you like to create the theme on your server or export it as a zip file?',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/clone/theme"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create on Server',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
						<Text variant="muted">
							{ __(
								'Create the new theme on the server and activate it. The user changes will be preserved in the new theme.',
								'create-block-theme'
							) }
						</Text>
								<hr></hr>
								<NavigatorButton
									path="/clone/theme"
									icon={ copy }
								>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Export Zip',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
						<Text variant="muted">
							{ __(
								'Export a copy of this theme as a .zip file. The user changes will be preserved in the new theme.',
								'create-block-theme'
							) }
						</Text>
							</VStack>
						</PanelBody>
					</NavigatorScreen>
					<NavigatorScreen path="/create/blank">
						<CreateThemePanel createType={ 'createBlank' } />
					</NavigatorScreen>
					<NavigatorScreen path="/clone/theme">
						<CreateThemePanel createType={ 'createClone' } />
					</NavigatorScreen>
					<NavigatorScreen path="/create/variation">
						<CreateVariationPanel />
					</NavigatorScreen>
					<NavigatorScreen path="/save">
						<SaveThemePanel />
					</NavigatorScreen>
				</NavigatorProvider>
			</PluginSidebar>
			{ isEditorOpen && (
				<ThemeJsonEditorModal
					onRequestClose={ () => setIsEditorOpen( false ) }
				/>
			) }
			{ isMetadataEditorOpen && (
				<ThemeMetadataEditorModal
					onRequestClose={ () => setIsMetadataEditorOpen( false ) }
				/>
			) }
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
