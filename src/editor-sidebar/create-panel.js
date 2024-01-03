import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { downloadFile } from '../utils';
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
} from '@wordpress/components';
import { chevronLeft, addCard, download, copy } from '@wordpress/icons';

export const CreateThemePanel = () => {
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
			uri: themeData.theme_uri.raw,
			author: themeData.author.raw,
			author_uri: themeData.author_uri.raw,
			subfolder:
				themeData.stylesheet.lastIndexOf( '/' ) > 1
					? themeData.stylesheet.substring(
							0,
							themeData.stylesheet.lastIndexOf( '/' )
					  )
					: '',
		} );
	}, [] );

	const handleExportClick = () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/export-clone',
			method: 'POST',
			data: theme,
			headers: {
				'Content-Type': 'application/json',
			},
			parse: false,
		};

		async function exportCloneTheme() {
			try {
				const response = await apiFetch( fetchOptions );
				downloadFile( response );
			} catch ( error ) {
				const errorMessage =
					error.message && error.code !== 'unknown_error'
						? error.message
						: __(
								'An error occurred while attempting to export the theme.',
								'create-block-theme'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		exportCloneTheme();
	};

	const handleCreateBlankClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/create-blank',
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
						'Theme created successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to create the theme.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	const handleCloneClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/clone',
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
						'Theme cloned successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to create the theme.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	return (
		<PanelBody>
			<Heading>
				<NavigatorToParentButton icon={ chevronLeft }>
					{ __( 'Create Theme', 'create-block-theme' ) }
				</NavigatorToParentButton>
			</Heading>

			<VStack>
				<Text>
					{ __(
						'Enter Metadata properties of the new theme.',
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
			<hr></hr>
			<Spacer />
			<Button
				icon={ copy }
				variant="secondary"
				onClick={ handleCloneClick }
			>
				{ __( 'Clone Theme', 'create-block-theme' ) }
			</Button>
			<Spacer />
			<Text variant="muted">
				{ __(
					'Create a copy of this theme on the server and activate it. The user changes will be preserved in the new theme.',
					'create-block-theme'
				) }
			</Text>
			<hr></hr>
			<Spacer />
			<Button
				icon={ download }
				variant="secondary"
				onClick={ handleExportClick }
			>
				{ __( 'Export Theme', 'create-block-theme' ) }
			</Button>
			<Spacer />
			<Text variant="muted">
				{ __(
					'Export a copy of this theme as a .zip file. The user changes will be preserved in the new theme.',
					'create-block-theme'
				) }
			</Text>
			<hr></hr>
			<Spacer />
			<Button
				icon={ addCard }
				variant="secondary"
				onClick={ handleCreateBlankClick }
			>
				{ __( 'Create Blank Theme', 'create-block-theme' ) }
			</Button>
			<Spacer />
			<Text variant="muted">
				{ __(
					'Create a blank theme with no styles or templates.',
					'create-block-theme'
				) }
			</Text>
			<Spacer />
		</PanelBody>
	);
};
