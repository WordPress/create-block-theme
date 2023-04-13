import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { tool } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	Button,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	Panel,
	PanelBody,
	PanelRow,
	TextControl,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
	__experimentalNavigatorBackButton as NavigatorBackButton,
	__experimentalNavigatorButton as NavigatorButton,
} from '@wordpress/components';
import ExportTheme from './editor/export-theme';
import EditTheme from './editor/edit-theme';

function SidebarMenu() {
	return (
		<PanelBody>
			<VStack>
				<Spacer />
				<Text variant="muted">{ __( 'Create a new theme' ) }</Text>
				<NavigatorButton path="/clone">
					{ __( 'Clone Theme' ) }
				</NavigatorButton>
				<Spacer />
				<Text variant="muted">{ __( 'Debug current theme' ) }</Text>
				<NavigatorButton path="/debug">
					{ __( 'Debug Theme' ) }
				</NavigatorButton>
			</VStack>
		</PanelBody>
	);
}

function SidebarNavigator() {
	return (
		<NavigatorProvider initialPath="/">
			<NavigatorScreen path="/">
				<SidebarMenu />
			</NavigatorScreen>
			<NavigatorScreen path="/clone">
				<ExportTheme />
			</NavigatorScreen>
			<NavigatorScreen path="/debug">
				<EditTheme />
			</NavigatorScreen>
		</NavigatorProvider>
	);
}

const CreateBlockThemePlugin = () => {
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
				<SidebarNavigator />
			</PluginSidebar>
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
