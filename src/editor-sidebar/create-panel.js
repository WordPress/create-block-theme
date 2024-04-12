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
	CheckboxControl,
} from '@wordpress/components';
import { chevronLeft, addCard, download, copy } from '@wordpress/icons';

export const CreateThemePanel = ( { createType } ) => {
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
		if ( createType.includes( 'export') ) {
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
	} else {
		setTheme( {
			subfolder:
				themeData.stylesheet.lastIndexOf( '/' ) > 1
					? themeData.stylesheet.substring(
							0,
							themeData.stylesheet.lastIndexOf( '/' )
					  )
					: '',
		} );
	}
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

	const handleExportChildClick = () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/export-child-clone',
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
								'An error occurred while attempting to export the child theme.',
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

	const handleCreateChildClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/create-child',
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
						'Child theme created successfully. The editor will now reload.',
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

	const handleCreateVariationClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/create-variation',
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
						'Theme variation created successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to create the theme variation.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	return (
		<PanelBody>
			<Heading>
				<NavigatorToParentButton icon={ chevronLeft }>
					{
						__( 'Clone Theme', 'create-block-theme' )
					}
				</NavigatorToParentButton>
			</Heading>

			<VStack>
				<TextControl
					label={ __( 'Theme name', 'create-block-theme' ) }
					value={ theme.name }
					onChange={ ( value ) =>
						setTheme( { ...theme, name: value } )
					}
				/>
				<details>
					<summary>Theme MetaData</summary>
					<Spacer />
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
				</details>
				<br />
				{ createType === 'createClone' && (
					<>
						<Button
							icon={ copy }
							variant="primary"
							onClick={ handleCloneClick }
						>
							{ __( 'Create Theme', 'create-block-theme' ) }
						</Button>
					</>
				) }
				{ createType === 'createChild' && (
					<>
						<Button
							icon={ copy }
							variant="primary"
							onClick={ handleCreateChildClick }
						>
							{ __( 'Create Child Theme', 'create-block-theme' ) }
						</Button>
						<Spacer />
						<Text variant="muted">
							{ __(
								'Create a child theme on the server and activate it. The user changes will be preserved in the new theme.',
								'create-block-theme'
							) }
						</Text>
					</>
				) }
				{ createType === 'createVariation' && (
					<>
						<Button
							icon={ copy }
							variant="primary"
							onClick={ handleCreateVariationClick }
						>
							{ __(
								'Create Theme Variation',
								'create-block-theme'
							) }
						</Button>
						<Spacer />
						<Text variant="muted">
							{ __(
								'Save the Global Styles changes as a theme variation.',
								'create-block-theme'
							) }
						</Text>
					</>
				) }
				{ createType === 'exportClone' && (
					<>
						<Button
							icon={ download }
							variant="primary"
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
					</>
				) }
				{ createType === 'exportChild' && (
					<>
						<Button
							icon={ download }
							variant="primary"
							onClick={ handleExportChildClick }
						>
							{ __( 'Export Child Theme', 'create-block-theme' ) }
						</Button>
						<Spacer />
						<Text variant="muted">
							{ __(
								'Export a child of this theme as a .zip file. The user changes will be preserved in the new theme.',
								'create-block-theme'
							) }
						</Text>
					</>
				) }
				{ createType === 'createBlank' && (
					<>
						<Button
							icon={ addCard }
							variant="primary"
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
					</>
				) }
			</VStack>
		</PanelBody>
	);
};
