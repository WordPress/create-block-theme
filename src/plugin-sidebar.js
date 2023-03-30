import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { tool } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	__experimentalVStack as VStack,
	__experimentalSpacer as Spacer,
	__experimentalText as Text,
	__experimentalHeading as Heading,
	PanelBody,
	TextControl,
} from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const ExportTheme = () => {
	const { createErrorNotice } = useDispatch( noticesStore );
	const [ theme, setTheme ] = useState( {
		name: '',
		description: '',
		uri: '',
		author: '',
		author_uri: '',
	} );

	useSelect( ( select ) => {
		const themeData = select( 'core' ).getCurrentTheme();
		setTheme( {
			name: themeData.name.raw,
			description: themeData.description.raw,
			author: themeData.author.raw,
			author_uri: themeData.author_uri.raw,
			theme_uri: themeData.theme_uri.raw,
		} );
	}, [] );

	const handleSubmit = () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/export',
			method: 'POST',
			data: theme,
			headers: {
				'Content-Type': 'application/json',
			},
			parse: false,
		};

		async function exportTheme() {
			try {
				const response = await apiFetch( fetchOptions );
				const blob = await response.blob();
				const url = URL.createObjectURL( blob );
				window.location.href = url;
			} catch ( error ) {
				const errorMessage =
					error.message && error.code !== 'unknown_error'
						? error.message
						: __(
								'An error occurred while attempting to export the theme.'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		exportTheme();
	};

	return (
		<PanelBody>
			<Heading>{ __( 'Export', 'create-block-theme' ) }</Heading>
			<VStack>
				<Text variant="muted">
					{ __(
						'Export your theme with updated templates and styles.',
						'create-block-theme'
					) }
				</Text>
				<Spacer />
				<TextControl
					label={ __( 'Theme name', 'create-block-theme' ) }
					value={ theme.name }
					onChange={ ( value ) =>
						setTheme( { ...theme, name: value } )
					}
					placeholder={ __( 'Theme name', 'create-block-theme' ) }
				/>
				<TextControl
					label={ __( 'Theme description', 'create-block-theme' ) }
					value={ theme.description }
					onChange={ ( value ) =>
						setTheme( { ...theme, description: value } )
					}
					placeholder={ __(
						'A short description of the theme',
						'create-block-theme'
					) }
				/>
				<TextControl
					label={ __( 'Theme URI', 'create-block-theme' ) }
					value={ theme.uri }
					onChange={ ( value ) =>
						setTheme( { ...theme, uri: value } )
					}
					placeholder={ __(
						'https://github.com/wordpress/twentytwentythree/',
						'create-block-theme'
					) }
				/>
				<TextControl
					label={ __( 'Author', 'create-block-theme' ) }
					value={ theme.author }
					onChange={ ( value ) =>
						setTheme( { ...theme, author: value } )
					}
					placeholder={ __(
						'the WordPress team',
						'create-block-theme'
					) }
				/>
				<TextControl
					label={ __( 'Author URI', 'create-block-theme' ) }
					value={ theme.author_uri }
					onChange={ ( value ) =>
						setTheme( { ...theme, author_uri: value } )
					}
					placeholder={ __(
						'https://wordpress.org/',
						'create-block-theme'
					) }
				/>
			</VStack>
			<Spacer />
			<Button
				variant="secondary"
				disabled={ ! theme.name }
				onClick={ handleSubmit }
			>
				{ __( 'Export', 'create-block-theme' ) }
			</Button>
			<Spacer />
			{ ! theme.name && (
				<Text variant="muted">
					{ __(
						'Theme name is required for export.',
						'create-block-theme'
					) }
				</Text>
			) }
		</PanelBody>
	);
};

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
				<ExportTheme />
			</PluginSidebar>
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
