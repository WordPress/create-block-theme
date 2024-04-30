import { useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { __, _x } from '@wordpress/i18n';
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
import {
	tool,
	copy,
	download,
	edit,
	code,
	chevronRight,
	addCard,
	blockMeta,
} from '@wordpress/icons';

import { CreateThemePanel } from './editor-sidebar/create-panel';
import ThemeJsonEditorModal from './editor-sidebar/json-editor-modal';
import { SaveThemePanel } from './editor-sidebar/save-panel';
import { CreateVariationPanel } from './editor-sidebar/create-variation-panel';
import { ThemeMetadataEditorModal } from './editor-sidebar/metadata-editor-modal';
import ScreenHeader from './editor-sidebar/screen-header';
import { downloadExportedTheme } from './resolvers';
import { downloadFile } from './utils';

const CreateBlockThemePlugin = () => {
	const [ isEditorOpen, setIsEditorOpen ] = useState( false );

	const [ isMetadataEditorOpen, setIsMetadataEditorOpen ] = useState( false );

	const [ cloneCreateType, setCloneCreateType ] = useState( '' );

	const { createErrorNotice } = useDispatch( noticesStore );

	const handleExportClick = async () => {
		try {
			const response = await downloadExportedTheme();
			downloadFile( response );
		} catch ( errorResponse ) {
			const error = await errorResponse.json();
			const errorMessage =
				error.message && error.code !== 'unknown_error'
					? error.message
					: __(
							'An error occurred while attempting to export the theme.',
							'create-block-theme'
					  );
			createErrorNotice( errorMessage, { type: 'snackbar' } );
		}
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
									onClick={ () =>
										setIsMetadataEditorOpen( true )
									}
								>
									{ __(
										'Edit Theme Metadata',
										'create-block-theme'
									) }
								</Button>
								<Button
									icon={ code }
									onClick={ () => setIsEditorOpen( true ) }
								>
									{ __(
										'View theme.json',
										'create-block-theme'
									) }
								</Button>
								<Button
									icon={ download }
									onClick={ () => handleExportClick() }
								>
									{ __( 'Export Zip', 'create-block-theme' ) }
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
								<NavigatorButton path="/clone" icon={ copy }>
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
							</VStack>
						</PanelBody>
					</NavigatorScreen>

					<NavigatorScreen path="/clone">
						<PanelBody>
							<ScreenHeader
								title={ __(
									'Create Block Theme',
									'create-block-theme'
								) }
							/>
							<VStack>
								<Text>
									{ __(
										'Would you like to clone this Theme or create a Child Theme?',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/clone/create"
									icon={ copy }
									onClick={ () => {
										setCloneCreateType( 'createClone' );
									} }
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
										'Create a clone of this theme with a new name. The user changes will be preserved in the new theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton
									path="/clone/create"
									icon={ copy }
									onClick={ () => {
										setCloneCreateType( 'createChild' );
									} }
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

					<NavigatorScreen path="/create/blank">
						<CreateThemePanel createType={ 'createBlank' } />
					</NavigatorScreen>

					<NavigatorScreen path="/clone/create">
						<CreateThemePanel createType={ cloneCreateType } />
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
