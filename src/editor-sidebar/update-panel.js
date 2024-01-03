import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	// eslint-disable-next-line
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
	PanelBody,
	Button,
	TextControl,
	TextareaControl,
	ExternalLink,
} from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';

export const UpdateThemePanel = () => {
	const { createErrorNotice } = useDispatch( noticesStore );

	const [ theme, setTheme ] = useState( {
		name: '',
		description: '',
		uri: '',
		version: '',
		author: '',
		author_uri: '',
		tags_custom: '',
		recommended_plugins: '',
	} );

	useEffect( () => {
		const getThemeReadmeData = async () => {
			return apiFetch( {
				path: '/create-block-theme/v1/get-readme-data',
				method: 'GET',
			} ).then( ( response ) => {
				if ( response.status === 'SUCCESS' ) {
					return response.data;
				}
				return {};
			} );
		};
		const setThemeReadmeData = async () => {
			const themeReadmeData = await getThemeReadmeData();
			setTheme( ( prevTheme ) => ( {
				...prevTheme,
				recommended_plugins: themeReadmeData.recommendedPlugins,
			} ) );
		};
		setThemeReadmeData();
	}, [] );

	useSelect( async ( select ) => {
		const themeData = select( 'core' ).getCurrentTheme();
		setTheme( {
			name: themeData.name.raw,
			description: themeData.description.raw,
			uri: themeData.theme_uri.raw,
			version: themeData.version,
			author: themeData.author.raw,
			author_uri: themeData.author_uri.raw,
			tags_custom: themeData.tags.rendered,
			subfolder:
				themeData.stylesheet.lastIndexOf( '/' ) > 1
					? themeData.stylesheet.substring(
							0,
							themeData.stylesheet.lastIndexOf( '/' )
					  )
					: '',
		} );
	}, [] );

	const handleUpdateClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/update',
			method: 'POST',
			data: theme,
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( () => {
				// eslint-disable-next-line
				alert(
					__(
						'Theme updated successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to update the theme.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	return (
		<PanelBody>
			<Heading>
				<NavigatorToParentButton icon={ chevronLeft }>
					{ __( 'Theme Info', 'create-block-theme' ) }
				</NavigatorToParentButton>
			</Heading>

			<VStack>
				<Text>
					{ __(
						'Edit Metadata properties of the current theme.',
						'create-block-theme'
					) }
				</Text>
				<Spacer />
				<TextControl
					disabled={ true }
					label={ __( 'Theme name', 'create-block-theme' ) }
					value={ theme.name }
				/>
				<TextareaControl
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
				<TextControl
					label={ __( 'Version', 'create-block-theme' ) }
					value={ theme.version }
					onChange={ ( value ) =>
						setTheme( { ...theme, version: value } )
					}
					placeholder={ __(
						'Version of the theme',
						'create-block-theme'
					) }
				/>
				<TextareaControl
					label={ __( 'Theme tags', 'create-block-theme' ) }
					value={ theme.tags_custom }
					onChange={ ( value ) =>
						setTheme( { ...theme, tags_custom: value } )
					}
					placeholder={ __(
						'A comma-separated collection of tags',
						'create-block-theme'
					) }
				/>
				<TextareaControl
					label={ __( 'Recommended Plugins', 'create-block-theme' ) }
					help={
						<>
							{ __(
								'List the recommended plugins for this theme. e.g. contact forms, social media. Plugins must be from the WordPress.org plugin repository.',
								'create-block-theme'
							) }
							<br />
							<ExternalLink href="https://make.wordpress.org/themes/handbook/review/required/#6-plugins">
								{ __( 'Read more.', 'create-block-theme' ) }
							</ExternalLink>
						</>
					}
					// eslint-disable-next-line @wordpress/i18n-no-collapsible-whitespace
					placeholder={ __(
						`Plugin Name
https://wordpress.org/plugins/plugin-name/
Plugin Description`,
						'create-block-theme'
					) }
					value={ theme.recommended_plugins }
					onChange={ ( value ) =>
						setTheme( { ...theme, recommended_plugins: value } )
					}
				/>
				<TextControl
					label={ __( 'Theme Subfolder', 'create-block-theme' ) }
					value={ theme.subfolder }
					onChange={ ( value ) =>
						setTheme( { ...theme, subfolder: value } )
					}
				/>
			</VStack>
			<Spacer />
			<Button variant="secondary" onClick={ handleUpdateClick }>
				{ __( 'Update', 'create-block-theme' ) }
			</Button>
		</PanelBody>
	);
};
