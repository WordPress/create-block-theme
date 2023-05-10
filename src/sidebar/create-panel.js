import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
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
	TextareaControl,
} from '@wordpress/components';

export const CreateThemePanel = () => {

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
			author: themeData.author.raw,
			author_uri: themeData.author_uri.raw,
			theme_uri: themeData.theme_uri.raw,
			subfolder: themeData.stylesheet.lastIndexOf( '/' ) > 1 ? themeData.stylesheet.substring( 0, themeData.stylesheet.lastIndexOf( '/' ) ) : '',
		} );
	}, [] );

	const handleCloneClick = () => {
		console.log('clone theme', theme);
	};

	const handleCreateBlankClick = () => {
		console.log('create blank theme', theme);
	};

	return (
		<>
			<Heading><NavigatorToParentButton>&lt;</NavigatorToParentButton> { __( 'Create Theme', 'create-block-theme' ) }</Heading>
			<VStack>
				<Text variant="muted">
					{ __( 'Create a new theme and activate it.', 'create-block-theme' ) }
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
				disabled={ ! theme.name }
				onClick={ handleCloneClick }
			>
				{ __( 'Clone This Theme', 'create-block-theme' ) }
			</Button>
			<Spacer />
			<Button
				variant="secondary"
				disabled={ ! theme.name }
				onClick={ handleCreateBlankClick }
			>
				{ __( 'Create Blank Theme', 'create-block-theme' ) }
			</Button>
		</>
	)
};
