import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import {
	__experimentalText as Text,
	__experimentalHeading as Heading,
	__experimentalSpacer as Spacer,
	__experimentalHStack as HStack,
	Button,
	TextareaControl,
	FormFileUpload,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

import SidebarSection from './sidebar-section';

function EditTheme() {
	const [ themeData, setThemeData ] = useState( {} );

	useEffect( () => {
		const getDebugData = async () => {
			const requestOptions = {
				path: '/create-block-theme/v1/theme-data',
			};
			const data = await apiFetch( requestOptions );
			setThemeData( data );
		};
		getDebugData();
	}, [] );

	//  style.css data
	const description = themeData?.description || '';
	const thumbnail = themeData?.thumbnail || '';
	const themeUri = themeData?.theme_uri || '';
	const author = themeData?.author || '';
	const authorUri = themeData?.author_uri || '';
	const version = themeData?.version || '';
	const tags = themeData?.tags || '';

	const userJson = JSON.stringify( themeData?.user_json );
	const templates = themeData?.templates
		? JSON.parse( themeData.templates )
		: [];
	const customTemplates = templates.filter(
		( { source } ) => source === 'custom'
	);
	const templateParts = themeData?.template_parts
		? JSON.parse( themeData.template_parts )
		: [];
	const customTemplateParts = templateParts.filter(
		( { source } ) => source === 'custom'
	);

	return (
		<SidebarSection title={ __( 'Theme Inspector', 'create-block-theme' ) }>
			<TextareaControl
				label={ __( 'Description', 'create-block-theme' ) }
				value={ description }
			></TextareaControl>

			<Spacer margin={ 10 } />

			<Heading level="5">
				{ __( 'Theme Image:', 'create-block-theme' ) }
			</Heading>
			{ thumbnail && (
				<img
					src={ thumbnail }
					width="100%"
					alt={ __( 'Theme Image', 'create-block-theme' ) }
				/>
			) }
			{ ! thumbnail && (
				<FormFileUpload
					onChange={ ( event ) => console.log( event ) }
					accept="image/*"
					isSecondary
				>
					{ __( 'Upload Image', 'create-block-theme' ) }
				</FormFileUpload>
			) }

			<Spacer margin={ 10 } />

			<InputControl
				label="Theme URI"
				placeholder="Theme URI"
				value={ themeUri }
				onChange={ ( value ) =>
					setThemeData( { ...themeData, theme_uri: value } )
				}
			/>

			<Spacer margin={ 10 } />

			<InputControl
				label="Author"
				placeholder="Author"
				value={ author }
				onChange={ ( value ) =>
					setThemeData( { ...themeData, author: value } )
				}
			/>

			<Spacer margin={ 10 } />

			<InputControl
				label="Author URI"
				placeholder="Author URI"
				value={ authorUri }
				onChange={ ( value ) =>
					setThemeData( { ...themeData, author_uri: value } )
				}
			/>

			<Spacer margin={ 10 } />

			<Heading level="5">
				{ __( 'Custom styles:', 'create-block-theme' ) }
			</Heading>
			<HStack justify="space-between">
				<Text as="p">
					{ __( 'User Custom Styles:', 'create-block-theme' ) }{ ' ' }
					{ userJson
						? __( 'Yes', 'create-block-theme' )
						: __( 'No', 'create-block-theme' ) }
				</Text>
				{ userJson && (
					<Button variant="secondary" isSmall>
						{ __( 'Reset', 'create-block-theme' ) }
					</Button>
				) }
			</HStack>

			<Spacer margin={ 10 } />

			<Heading level="5">
				{ __( 'Templates:', 'create-block-theme' ) }
			</Heading>
			{ customTemplates.length ? (
				<HStack justify="space-between">
					<Text as="p">{ `${ customTemplates.length } ${ __(
						'custom templates',
						'create-block-theme'
					) }` }</Text>
					<Button variant="secondary" isSmall>
						{ __( 'Reset', 'create-block-theme' ) }
					</Button>
				</HStack>
			) : (
				<Text as="p">
					{ __( 'No custom templates', 'create-block-theme' ) }
				</Text>
			) }

			<Spacer margin={ 10 } />

			<Heading level="5">
				{ __( 'Template Parts:', 'create-block-theme' ) }
			</Heading>
			{ customTemplateParts.length ? (
				<HStack justify="space-between">
					<Text as="p">{ `${ customTemplateParts.length } ${ __(
						'custom template parts',
						'create-block-theme'
					) }` }</Text>
					<Button variant="secondary" isSmall>
						{ __( 'Reset', 'create-block-theme' ) }
					</Button>
				</HStack>
			) : (
				<Text as="p">
					{ __( 'No custom template parts', 'create-block-theme' ) }
				</Text>
			) }

			<Spacer margin={ 10 } />

			<Button variant="secondary">
				{ __( 'Save Changes', 'create-block-theme' ) }
			</Button>
		</SidebarSection>
	);
}

export default EditTheme;
