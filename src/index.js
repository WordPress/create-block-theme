import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { blockDefault } from '@wordpress/icons';
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, MenuGroup, MenuItem, TextControl } from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const GlobalStylesProvider = wp.editSite.GlobalStylesProvider;

const StyleVariations = () => {
	const [ variationName, setVariationName ] = useState( '' );
	const [ canReset, onReset ] = wp.editSite.useGlobalStylesReset();
	const { createErrorNotice } = useDispatch( noticesStore );

	async function createVariation() {
		try {
			const response = await apiFetch( {
				path: '/create-block-theme/v1/variation/' + variationName,
			} );

			// Clear global styles customizations
			onReset();

			// Reload variations
			// We need to invalidate and maybe refetch __experimentalGetCurrentThemeGlobalStylesVariations(),

		} catch ( errorResponse ) {
			const errorMessage =
				error.message && error.code !== 'unknown_error'
					? error.message
					: __( 'An error occurred while creating the site export.' );
			createErrorNotice( errorMessage, { type: 'snackbar' } );
		}
	}

	return (
		<MenuGroup label={ __( 'Create style variation' ) }>
			<MenuItem>
				<TextControl onChange={ ( value ) => setVariationName( value ) } placeholder={ __( 'Variation name' ) }></TextControl>
				<Button onClick={ () => createVariation() }>{ __( 'Create variation' ) }</Button>
			</MenuItem>
		</MenuGroup>
	);
};

const CreateBlockThemePlugin = () => (
	<Fragment>
		<PluginSidebarMoreMenuItem target="create-block-theme-sidebar" icon={ blockDefault }>
			{ __( 'Create Block Theme' ) }
		</PluginSidebarMoreMenuItem>
		<PluginSidebar name="create-block-theme-sidebar" icon={ blockDefault } title={ __( 'Create Block Theme' ) }>
			<GlobalStylesProvider>
				<StyleVariations />
			</GlobalStylesProvider>
		</PluginSidebar>
	</Fragment>
);

registerPlugin( 'plugin-sidebar-expanded-test', {
	render: CreateBlockThemePlugin,
} );
