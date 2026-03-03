/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { __, _x, isRTL } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNavigatorProvider as NavigatorProvider,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNavigatorScreen as NavigatorScreen,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNavigatorButton as NavigatorButton,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	Button,
	Card,
	CardBody,
	CardDivider,
	Icon,
	FlexItem,
} from '@wordpress/components';
import {
	tool,
	copy,
	download,
	edit,
	code,
	chevronLeft,
	chevronRight,
	addCard,
	blockMeta,
	help,
	trash,
} from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CreateThemePanel } from './editor-sidebar/create-panel';
import ThemeJsonEditorModal from './editor-sidebar/json-editor-modal';
import GlobalStylesJsonEditorModal from './editor-sidebar/global-styles-json-editor-modal';
import { SaveThemePanel } from './editor-sidebar/save-panel';
import { CreateVariationPanel } from './editor-sidebar/create-variation-panel';
import { ThemeMetadataEditorModal } from './editor-sidebar/metadata-editor-modal';
import ScreenHeader from './editor-sidebar/screen-header';
import { downloadExportedTheme } from './resolvers';
import downloadFile from './utils/download-file';
import AboutPlugin from './editor-sidebar/about';
import ResetTheme from './editor-sidebar/reset-theme';
import './plugin-styles.scss';

function PluginSidebarItem( { icon, path, children, onClick } ) {
	const ItemWrapper = path ? NavigatorButton : Button;
	return (
		<ItemWrapper onClick={ onClick } path={ path }>
			<HStack justify="flex-start">
				<HStack justify="flex-start">
					<Icon icon={ icon } />
					<FlexItem>{ children }</FlexItem>
				</HStack>
				{ path && (
					<Icon icon={ isRTL() ? chevronLeft : chevronRight } />
				) }
			</HStack>
		</ItemWrapper>
	);
}

const CreateBlockThemePlugin = () => {
	const [ isEditorOpen, setIsEditorOpen ] = useState( false );
	const [ isGlobalStylesEditorOpen, setIsGlobalStylesEditorOpen ] =
		useState( false );

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
				className="create-block-theme-sidebar"
			>
				<NavigatorProvider initialPath="/">
					<NavigatorScreen path="/">
						<Card size="small" isBorderless>
							<CardBody>
								<VStack spacing={ 0 }>
									<PluginSidebarItem
										path="/save"
										icon={ copy }
									>
										{ __(
											'Save Changes to Theme',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<PluginSidebarItem
										path="/create/variation"
										icon={ blockMeta }
									>
										{ __(
											'Create Theme Variation',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<PluginSidebarItem
										icon={ edit }
										onClick={ () =>
											setIsMetadataEditorOpen( true )
										}
									>
										{ __(
											'Edit Theme Metadata',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<PluginSidebarItem
										icon={ code }
										onClick={ () =>
											setIsEditorOpen( true )
										}
									>
										{ __(
											'View theme.json',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<PluginSidebarItem
										icon={ code }
										onClick={ () =>
											setIsGlobalStylesEditorOpen( true )
										}
									>
										{ __(
											'View Custom Styles',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<PluginSidebarItem
										icon={ download }
										onClick={ () => handleExportClick() }
									>
										{ __(
											'Export Zip',
											'create-block-theme'
										) }
									</PluginSidebarItem>
								</VStack>
							</CardBody>
							<CardDivider />
							<CardBody>
								<VStack spacing={ 0 }>
									<PluginSidebarItem
										path="/create/blank"
										icon={ addCard }
									>
										{ __(
											'Create Blank Theme',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<PluginSidebarItem
										path="/clone"
										icon={ copy }
									>
										{ __(
											'Create Theme',
											'create-block-theme'
										) }
									</PluginSidebarItem>
								</VStack>
							</CardBody>
							<CardDivider />
							<CardBody>
								<VStack spacing={ 0 }>
									<PluginSidebarItem
										path="/reset"
										icon={ trash }
									>
										{ __(
											'Reset Theme',
											'create-block-theme'
										) }
									</PluginSidebarItem>
								</VStack>
							</CardBody>
							<CardDivider />
							<CardBody>
								<VStack spacing={ 0 }>
									<PluginSidebarItem
										path="/about"
										icon={ help }
									>
										{ __( 'Help', 'create-block-theme' ) }
									</PluginSidebarItem>
								</VStack>
							</CardBody>
						</Card>
					</NavigatorScreen>

					<NavigatorScreen path="/clone">
						<ScreenHeader
							title={ __(
								'Create Block Theme',
								'create-block-theme'
							) }
							description={
								<Text>
									{ __(
										'Would you like to clone this Theme or create a Child Theme?',
										'create-block-theme'
									) }
								</Text>
							}
						/>
						<Card size="small" isBorderless>
							<CardBody>
								<VStack>
									<PluginSidebarItem
										path="/clone/create"
										icon={ copy }
										onClick={ () => {
											setCloneCreateType( 'createClone' );
										} }
									>
										{ __(
											'Clone Theme',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<Text variant="muted">
										{ __(
											'Create a clone of this theme with a new name. The user changes will be preserved in the new theme.',
											'create-block-theme'
										) }
									</Text>
								</VStack>
							</CardBody>
							<CardDivider />
							<CardBody>
								<VStack>
									<PluginSidebarItem
										path="/clone/create"
										icon={ copy }
										onClick={ () => {
											setCloneCreateType( 'createChild' );
										} }
									>
										{ __(
											'Create Child Theme',
											'create-block-theme'
										) }
									</PluginSidebarItem>
									<Text variant="muted">
										{ __(
											'Create a child theme that uses this theme as a parent. This theme will remain unchanged and the user changes will be preserved in the new child theme.',
											'create-block-theme'
										) }
									</Text>
								</VStack>
							</CardBody>
						</Card>
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

					<NavigatorScreen path="/about">
						<AboutPlugin />
					</NavigatorScreen>

					<NavigatorScreen path="/reset">
						<ResetTheme />
					</NavigatorScreen>
				</NavigatorProvider>
			</PluginSidebar>

			{ isEditorOpen && (
				<ThemeJsonEditorModal
					onRequestClose={ () => setIsEditorOpen( false ) }
				/>
			) }

			{ isGlobalStylesEditorOpen && (
				<GlobalStylesJsonEditorModal
					onRequestClose={ () =>
						setIsGlobalStylesEditorOpen( false )
					}
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
