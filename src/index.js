import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { blockDefault } from '@wordpress/icons';
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, MenuGroup, MenuItem, PanelBody, TextControl } from '@wordpress/components';
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
			if ( response.req.status === 200 ) {
				createErrorNotice( __( 'Variation created successfully' ), { type: 'snackbar' } );
			}

		} catch ( error ) {
			const errorMessage =
				error.message && error.code !== 'unknown_error'
					? error.message
					: __( 'An error occurred while creating the site export.' );
			createErrorNotice( errorMessage, { type: 'snackbar' } );
		}
	}

	return (
		<PanelBody>
			<MenuGroup label={ __( 'Create style variation' ) }>
				<TextControl onChange={ ( value ) => setVariationName( value ) } placeholder={ __( 'Variation name' ) }></TextControl>
				<Button variant="secondary" onClick={ () => createVariation() }>{ __( 'Create variation' ) }</Button>
			</MenuGroup>
		</PanelBody>
	);
};

const BlankTheme = () => {
	const { createErrorNotice } = useDispatch( noticesStore );
	const [ theme, setTheme ] = useState( {
		"name": "",
		"description": "",
		"uri": "",
		"author": "",
		"author_uri": "",
	} );
	async function createBlankTheme() {
		try {
			const response = await apiFetch( {
				path: '/create-block-theme/v1/blank-theme',
				method: 'POST',
				data: theme,
			} );

			if ( response.req.status === 200 ) {
				createErrorNotice( __( 'New theme created' ), { type: 'snackbar' } );
			}
		} catch ( error ) {
			const errorMessage =
				error.message && error.code !== 'unknown_error'
					? error.message
					: __( 'An error occurred while creating the site export.' );
			createErrorNotice( errorMessage, { type: 'snackbar' } );
		}
	}

	return (
		<PanelBody>
			<MenuGroup label={ __( 'Create a blank theme' ) }>
				<TextControl label={ __( 'Theme name' ) } onChange={ ( value ) => setTheme( { ...theme, "name": value } ) } placeholder={ __( 'Theme name' ) }></TextControl>
				<TextControl label={ __( 'Theme description' ) } onChange={ ( value ) => setTheme( { ...theme, "description": value } ) } placeholder={ __( 'A short description of the theme' ) }></TextControl>
				<TextControl label={ __( 'Theme URI' ) } onChange={ ( value ) => setTheme( { ...theme, "uri": value } ) } placeholder={ __( 'https://github.com/wordpress/twentytwentytwo/' ) }></TextControl>
				<TextControl label={ __( 'Author' ) } onChange={ ( value ) => setTheme( { ...theme, "author": value } ) } placeholder={ __( 'the WordPress team' ) }></TextControl>
				<TextControl label={ __( 'Author URI' ) } onChange={ ( value ) => setTheme( { ...theme, "author_uri": value } ) } placeholder={ __( 'https://wordpress.org/' ) }></TextControl>
				<Button variant="secondary" onClick={ () => createBlankTheme() }>{ __( 'Create blank theme' ) }</Button>
			</MenuGroup>
		</PanelBody>
	)
}

const CreateBlockThemePlugin = () => (
	<Fragment>
		<PluginSidebarMoreMenuItem target="create-block-theme-sidebar" icon={ blockDefault }>
			{ __( 'Create Block Theme' ) }
		</PluginSidebarMoreMenuItem>
		<PluginSidebar name="create-block-theme-sidebar" icon={ blockDefault } title={ __( 'Create Block Theme' ) }>
			<GlobalStylesProvider>
				<StyleVariations />
				<BlankTheme />
			</GlobalStylesProvider>
		</PluginSidebar>
	</Fragment>
);

registerPlugin( 'plugin-sidebar-expanded-test', {
	render: CreateBlockThemePlugin,
} );
