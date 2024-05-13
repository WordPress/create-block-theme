/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line
	__experimentalHStack as HStack,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	BaseControl,
	FormTokenField,
	Modal,
	Button,
	TextControl,
	TextareaControl,
	ExternalLink,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { postUpdateThemeMetadata, fetchReadmeData } from '../resolvers';

const ALLOWED_SCREENSHOT_MEDIA_TYPES = [
	'image/png',
	'image/gif',
	'image/jpg',
	'image/jpeg',
	'image/webp',
	'image/avif',
];

export const ThemeMetadataEditorModal = ( { onRequestClose } ) => {
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

	const { createErrorNotice } = useDispatch( noticesStore );

	useSelect( async ( select ) => {
		const themeData = select( 'core' ).getCurrentTheme();
		const readmeData = await fetchReadmeData();
		setTheme( {
			name: themeData.name.raw,
			description: themeData.description.raw,
			uri: themeData.theme_uri.raw,
			version: themeData.version,
			author: themeData.author.raw,
			author_uri: themeData.author_uri.raw,
			tags_custom: themeData.tags.rendered,
			screenshot: themeData.screenshot,
			recommended_plugins: readmeData.recommended_plugins,
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
		postUpdateThemeMetadata( theme )
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

	const onChangeTags = ( newTags ) => {
		setTheme( { ...theme, tags_custom: newTags.join( ', ' ) } );
	};

	const onUpdateImage = ( image ) => {
		setTheme( { ...theme, screenshot: image.url } );
	};

	return (
		<Modal
			isFullScreen
			title={ sprintf(
				// translators: %s: theme name.
				__( 'Metadata for %s', 'create-block-theme' ),
				theme?.name
			) }
			onRequestClose={ onRequestClose }
		>
			<VStack>
				<Text>
					{ __(
						'Edit Metadata properties of the current theme.',
						'create-block-theme'
					) }
				</Text>
				<Spacer />
				<TextControl
					disabled
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
				<FormTokenField
					label={ __( 'Theme tags', 'create-block-theme' ) }
					value={
						theme.tags_custom ? theme.tags_custom.split( ', ' ) : []
					}
					onChange={ onChangeTags }
				/>
				<ExternalLink
					href="https://make.wordpress.org/themes/handbook/review/required/theme-tags/"
					style={ {
						fontSize: '12px',
						marginTop: '-20px',
						marginBottom: '1rem',
					} }
				>
					{ __( 'Read more.', 'create-block-theme' ) }
				</ExternalLink>
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
				<BaseControl>
					<BaseControl.VisualLabel>
						{ __( 'Screenshot', 'create-block-theme' ) }
					</BaseControl.VisualLabel>
					<MediaUploadCheck>
						<MediaUpload
							title={ __( 'Screenshot', 'create-block-theme' ) }
							onSelect={ onUpdateImage }
							allowedTypes={ ALLOWED_SCREENSHOT_MEDIA_TYPES }
							render={ ( { open } ) => (
								<>
									{ theme.screenshot ? (
										<VStack alignment="left">
											<img
												src={ theme.screenshot }
												style={ {
													maxWidth: '200px',
													height: 'auto',
													aspectRatio: '4 / 3',
													objectFit: 'cover',
												} }
												alt=""
											/>
											<Button
												variant="secondary"
												size="compact"
												onClick={ open }
											>
												{ __(
													'Replace',
													'create-block-theme'
												) }
											</Button>
										</VStack>
									) : (
										<HStack alignment="left">
											<Button
												variant="secondary"
												size="compact"
												onClick={ open }
											>
												{ __(
													'Add screenshot',
													'create-block-theme'
												) }
											</Button>
										</HStack>
									) }
								</>
							) }
							value={ theme.screenshot }
						/>
					</MediaUploadCheck>
				</BaseControl>
				<TextControl
					label={ __( 'Theme Subfolder', 'create-block-theme' ) }
					value={ theme.subfolder }
					onChange={ ( value ) =>
						setTheme( { ...theme, subfolder: value } )
					}
				/>
			</VStack>
			<Spacer />
			<Button variant="primary" onClick={ handleUpdateClick }>
				{ __( 'Update', 'create-block-theme' ) }
			</Button>
		</Modal>
	);
};
