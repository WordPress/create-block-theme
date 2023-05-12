import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { __ } from '@wordpress/i18n';
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
	PanelBody,
} from '@wordpress/components';

import { ExportThemePanel } from './editor-sidebar/export-panel';
import { UpdateThemePanel } from './editor-sidebar/update-panel';
import { tool, copy, cog } from '@wordpress/icons';

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
				<NavigatorProvider initialPath="/">
					<NavigatorScreen path="/">
						<PanelBody>
							<VStack>
								<NavigatorButton path="/update" icon={ cog }>
									{ __( 'Edit Info…' ) }
								</NavigatorButton>
								<NavigatorButton path="/export" icon={ copy }>
									{ __( 'Export Zip…' ) }
								</NavigatorButton>
							</VStack>
						</PanelBody>
					</NavigatorScreen>

					<NavigatorScreen path="/export">
						<ExportThemePanel />
					</NavigatorScreen>

					<NavigatorScreen path="/update">
						<UpdateThemePanel />
					</NavigatorScreen>
				</NavigatorProvider>
			</PluginSidebar>
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
