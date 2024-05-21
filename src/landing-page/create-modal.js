/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	// eslint-disable-next-line
	__experimentalHStack as HStack,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	Modal,
	Button,
	TextControl,
	TextareaControl,
} from '@wordpress/components';

export const CreateThemeModal = ( { onRequestClose, createModalType } ) => {

	const [ theme, setTheme ] = useState( {
		name: '',
		description: '',
		author: '',
	} );

	const createBlockTheme = async () => {
		if ( createModalType === 'blank' ) {
			// Do something with the theme data
		}
		else if ( createModalType === 'clone' ) {
			// Do something with the theme data
		}
		else if ( createModalType === 'child' ) {
			// Do something with the theme data
		}
		onRequestClose();
	}

	return (
		<Modal
			title={ __('Create Block Theme', 'creat-block-theme') }
			onRequestClose={ onRequestClose }
		>
			<VStack>
				<Text>
					{ __(
						'Let\'s get started creating a new Block Theme.',
						'create-block-theme'
					) }
				</Text>
				<Spacer />
				<TextControl
					label={ __( 'Theme name (required)', 'create-block-theme' ) }
					value={ theme.name }
					required={ true }
					onChange={ ( value ) =>
						setTheme( { ...theme, name: value } )
					}
				/>

					<Spacer />
					<Text variant="muted">
					(Tip: You can edit all of this and more in the Editor later.)
					</Text>
					<Spacer />
					<TextareaControl
						label={ __(
							'Theme description',
							'create-block-theme'
						) }
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
				<br />
				<HStack>
				<Button variant='primary' disabled={!theme.name} onClick={()=>createBlockTheme()}>
					Create Block Theme
				</Button>
				</HStack>
			</VStack>
		</Modal>
	);
};
