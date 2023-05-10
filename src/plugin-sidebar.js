import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { tool } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	__experimentalVStack as VStack,
	__experimentalText as Text,
	__experimentalSpacer as Spacer,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
	__experimentalNavigatorButton as NavigatorButton,
	PanelBody,
} from '@wordpress/components';

import { UpdateThemePanel } from './sidebar/update-panel';
import { ExportThemePanel } from './sidebar/export-panel';
import { CreateThemePanel } from './sidebar/create-panel';

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
				<PanelBody>
				<NavigatorProvider initialPath="/">
					<NavigatorScreen path="/">
						<VStack>

						<Text>Export the current theme as a .zip file with local changes as a new theme.</Text>
						<NavigatorButton variant="link" path="/export" >
							{ __('Export Theme ...') }
						</NavigatorButton>

						<Spacer />

						<Text>Update the current theme with local changes or make changes to the current theme metadata.</Text>
						<NavigatorButton variant="link" path="/update" >
							{ __('Update Theme ...') }
						</NavigatorButton>

						<Spacer />

						<Text>Create a new theme, either as a clone of the current theme with user changes or a completely blank one.</Text>
						<NavigatorButton variant="link" path="/create" >
							{ __('Create Theme ...') }
						</NavigatorButton>


						</VStack>
					</NavigatorScreen>
					<NavigatorScreen path="/export">
						<ExportThemePanel />
					</NavigatorScreen>
					<NavigatorScreen path="/update">
						<UpdateThemePanel />
					</NavigatorScreen>
					<NavigatorScreen path="/create">
						<CreateThemePanel />
					</NavigatorScreen>


				</NavigatorProvider>
				</PanelBody>

			</PluginSidebar>
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
