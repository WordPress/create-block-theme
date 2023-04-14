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
import ThemeVariations from './editor/theme-variations';
import { edit, typography, brush, addCard, copy, download, file, cog } from '@wordpress/icons';

function SidebarMenu() {
	return (
		<PanelBody>
			<VStack>
				<Spacer />
				<Text variant="muted">{ __( 'Create a New Theme' ) }</Text>
				<NavigatorButton path="/clone" icon={copy}>
					{ __( 'Export' ) }
				</NavigatorButton>
				<NavigatorButton path="/clone" icon={addCard}>
					{ __( 'Blank' ) }
				</NavigatorButton>
				<Spacer />
				<Text variant="muted">{ __( 'Edit Theme' ) }</Text>
				<NavigatorButton path="/edit" icon={edit}>
					{ __( 'Save Changes' ) }
				</NavigatorButton>
				<NavigatorButton path="/edit" icon={cog}>
					{ __( 'Edit Info' ) }
				</NavigatorButton>
				<NavigatorButton path="/variations" icon={brush}>
					{ __( 'Style Variations' ) }
				</NavigatorButton>
				<NavigatorButton path="/fonts" icon={typography}>
					{ __( 'Manage Fonts' ) }
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
			<NavigatorScreen path="/edit">
				<EditTheme />
			</NavigatorScreen>
			<NavigatorScreen path="/variations">
				<ThemeVariations />
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
