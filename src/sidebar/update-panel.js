import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
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
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
	Button,
	TextControl,
	TextareaControl
} from '@wordpress/components';

export const UpdateThemePanel = () => {

	const { createErrorNotice } = useDispatch( noticesStore );

	const [ theme, setTheme ] = useState( {
		name: '',
		description: '',
		uri: '',
		author: '',
		author_uri: '',
		tags_custom: '',
	} );

	useSelect( ( select ) => {
		const themeData = select( 'core' ).getCurrentTheme();
		setTheme( {
			name: themeData.name.raw,
			description: themeData.description.raw,
			author: themeData.author.raw,
			author_uri: themeData.author_uri.raw,
			theme_uri: themeData.theme_uri.raw,
			subfolder: themeData.stylesheet.lastIndexOf( '/' ) > 1 ? themeData.stylesheet.substring( 0, themeData.stylesheet.lastIndexOf( '/' ) ) : '',
		} );
	}, [] );

	const handleSaveClick = () => {
		console.log('save theme', theme);
		const fetchOptions = {
			path: '/create-block-theme/v1/save',
			method: 'POST',
		};

		async function saveTheme() {
			try {
				await apiFetch( fetchOptions );
			} catch ( error ) {
				const errorMessage =
					error.message && error.code !== 'unknown_error'
						? error.message
						: __(
								'An error occurred while attempting to save the theme.'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		saveTheme();
	};

	const handleUpdateClick = () => {
		console.log('update theme', theme);

		const fetchOptions = {
			path: '/create-block-theme/v1/update',
			method: 'POST',
			data: theme,
			headers: {
				'Content-Type': 'application/json',
			},
			parse: false,
		};

		async function updateTheme() {
			try {
				await apiFetch( fetchOptions );
			} catch ( error ) {
				const errorMessage =
					error.message && error.code !== 'unknown_error'
						? error.message
						: __(
								'An error occurred while attempting to update the theme.'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		updateTheme();
	};


	return (
		<>
			<Heading><NavigatorToParentButton>&lt;</NavigatorToParentButton> { __( 'Update Theme', 'create-block-theme' ) }</Heading>
			<VStack>
				<Text variant="muted">
					{ __( 'Save user changes to the theme.', 'create-block-theme' ) }
				</Text>
			</VStack>
			<Spacer />
			<Button
				variant="secondary"
				onClick={ handleSaveClick }
			>
				{ __( 'Save', 'create-block-theme' ) }
			</Button>

			<Spacer margin={ 10 } />

			<hr />

			<VStack>
				<Text variant="muted">
					{ __( 'Change properties of the theme.', 'create-block-theme' ) }
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
					label={ __( 'Theme Subfolder', 'create-block-theme' ) }
					value={ theme.subfolder }
					onChange={ ( value ) =>
						setTheme( { ...theme, subfolder: value } )
					}
				/>

			</VStack>
			<Spacer />
			<Button
				variant="secondary"
				onClick={ handleUpdateClick }
			>
				{ __( 'Update', 'create-block-theme' ) }
			</Button>

		</>
	)
};
