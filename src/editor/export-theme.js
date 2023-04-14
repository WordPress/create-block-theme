import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	PanelBody,
	TextControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	CheckboxControl
} from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

import { downloadFile } from '../utils';

const ExportTheme = () => {
	const { createErrorNotice } = useDispatch( noticesStore );
	const [ edit, setEdit ] = useState( false );
	const [ activate, setActivate ] = useState( false ); 
	const [ exportType, setExportType ] = useState( 'zip' ); // zip or folder
	const [ createChild, setCreateChild ] = useState( false );
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
				downloadFile( response );
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

	const onTypeChange = ( value ) => {
		if ( value === "zip" ) {
			setActivate( false );
		}
		if ( value === "folder" ) {
			setEdit( true );
		}
		setExportType( value );
	}

	const onSetCreateChildChange = ( value ) => {
		if ( value === true ) {
			setEdit( true );
		}
		setCreateChild( value );
	}


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

				<ToggleGroupControl
					__nextHasNoMarginBottom
					isBlock
					label="Create:"
					onChange={ onTypeChange }
					value={ exportType }
				>
					<ToggleGroupControlOption
						label="Zip"
						value="zip"
					/>
					<ToggleGroupControlOption
						label="Folder"
						value="folder"
					/>
				</ToggleGroupControl>

				<Spacer />

				<CheckboxControl
					label="Export as Child Theme"
					checked={ createChild }
					onChange={ onSetCreateChildChange }
				/>

				<Spacer />

				<CheckboxControl
					label="Edit Theme Info"
					checked={ edit }
					onChange={ () => setEdit( ! edit ) }
					disabled={ createChild || exportType === "folder" }
				/>

				<Spacer />

				{ edit &&(
					<>
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
					</>
				) }
				
			</VStack>

			<Spacer />
			{ exportType === "folder" && (
				<CheckboxControl
					label="Activate After Export"
					checked={ activate }
					onChange={ (value) => setActivate( value ) }
				/>
			)}

			<Spacer margin={8}/>
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

export default ExportTheme;
