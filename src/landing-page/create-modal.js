/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	Modal,
	Button,
	TextControl,
	TextareaControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	createBlankTheme,
	createClonedTheme,
	createChildTheme,
} from '../resolvers';

export const CreateThemeModal = ( { onRequestClose, creationType } ) => {
	const [ errorMessage, setErrorMessage ] = useState( null );

	const [ theme, setTheme ] = useState( {
		name: '',
		description: '',
		author: '',
	} );

	const renderCreateButtonText = ( type ) => {
		switch ( type ) {
			case 'blank':
				return __(
					'Create and Activate Blank Theme',
					'create-block-theme'
				);
			case 'clone':
				return __( 'Clone Block Theme', 'create-block-theme' );
			case 'child':
				return __( 'Create Child Theme', 'create-block-theme' );
		}
	};

	const createBlockTheme = async () => {
		let constructionFunction = null;
		switch ( creationType ) {
			case 'blank':
				constructionFunction = createBlankTheme;
				break;
			case 'clone':
				constructionFunction = createClonedTheme;
				break;
			case 'child':
				constructionFunction = createChildTheme;
				break;
		}

		if ( ! constructionFunction ) {
			return;
		}
		constructionFunction( theme )
			.then( () => {
				// eslint-disable-next-line no-alert
				window.alert(
					__(
						'Theme created successfully. The editor will now load.',
						'create-block-theme'
					)
				);
				window.location = window.cbt_landingpage_variables.editor_url;
			} )
			.catch( ( error ) => {
				setErrorMessage(
					error.message ||
						__(
							'An error occurred while attempting to create the theme.',
							'create-block-theme'
						)
				);
			} );
	};

	if ( errorMessage ) {
		return (
			<Modal
				title={ __( 'Create Block Theme', 'create-block-theme' ) }
				onRequestClose={ onRequestClose }
			>
				<p>{ errorMessage }</p>
			</Modal>
		);
	}

	return (
		<Modal
			title={ __( 'Create Block Theme', 'create-block-theme' ) }
			onRequestClose={ onRequestClose }
		>
			<VStack spacing="5">
				<Text>
					{ __(
						"Let's get started creating a new Block Theme.",
						'create-block-theme'
					) }
				</Text>
				<TextControl
					__nextHasNoMarginBottom
					label={ __(
						'Theme name (required)',
						'create-block-theme'
					) }
					value={ theme.name }
					required
					onChange={ ( value ) =>
						setTheme( { ...theme, name: value } )
					}
					help={ __(
						'(Tip: You can edit all of this and more in the Editor later.)',
						'create-block-theme'
					) }
				/>
				<TextareaControl
					__nextHasNoMarginBottom
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
					__nextHasNoMarginBottom
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
				<HStack>
					<Button
						variant="primary"
						disabled={ ! theme.name }
						onClick={ () => createBlockTheme() }
					>
						{ renderCreateButtonText( creationType ) }
					</Button>
				</HStack>
			</VStack>
		</Modal>
	);
};
