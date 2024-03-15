import { useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-site';
import { __, _x } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { downloadFile } from './utils';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalNavigatorProvider as NavigatorProvider,
	// eslint-disable-next-line
	__experimentalNavigatorScreen as NavigatorScreen,
	// eslint-disable-next-line
	__experimentalNavigatorButton as NavigatorButton,
	// eslint-disable-next-line
	__experimentalHStack as HStack,
	// eslint-disable-next-line
	__experimentalText as Text,
	Button,
	Icon,
	FlexItem,
	PanelBody,
	Modal,
} from '@wordpress/components';

import { UpdateThemePanel } from './editor-sidebar/update-panel';
import { CreateThemePanel } from './editor-sidebar/create-panel';
import {
	tool,
	copy,
	download,
	edit,
	chevronRight,
	archive,
} from '@wordpress/icons';
import CodeMirror from '@uiw/react-codemirror';
import { json } from '@codemirror/lang-json';

const CreateBlockThemePlugin = () => {
	const [ isEditorOpen, setIsEditorOpen ] = useState( false );
	const [ themeData, setThemeData ] = useState( '' );
	const { createErrorNotice } = useDispatch( noticesStore );

	const handleSaveClick = () => {
		apiFetch( {
			path: '/create-block-theme/v1/save',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( () => {
				// eslint-disable-next-line
				alert(
					__(
						'Theme saved successfully. The editor will now reload.',
						'create-block-theme'
					)
				);
				window.location.reload();
			} )
			.catch( ( error ) => {
				const errorMessage =
					error.message ||
					__(
						'An error occurred while attempting to save the theme.',
						'create-block-theme'
					);
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			} );
	};

	const handleExportClick = () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/export',
			method: 'POST',
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
								'An error occurred while attempting to export the theme.',
								'create-block-theme'
						  );
				createErrorNotice( errorMessage, { type: 'snackbar' } );
			}
		}

		exportTheme();
	};

	const toggleThemeJsonEditor = async () => {
		const fetchOptions = {
			path: '/create-block-theme/v1/get-theme-data',
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
			},
		};

		try {
			const response = await apiFetch( fetchOptions );
			const data = JSON.stringify( response, null, 2 );
			const themeJson = JSON.stringify(
				JSON.parse( data )?.data,
				null,
				2
			);
			setThemeData( themeJson );
		} catch ( e ) {
			// @todo: handle error
			setThemeData( '' );
		}

		setIsEditorOpen( ! isEditorOpen );
	};

	const Editor = ( { isOpen = true, value, onChange } ) => {
		if ( ! isOpen ) {
			return null;
		}

		return (
			<Modal
				title={
					<>
						<Icon icon={ 'info' } />{ ' ' }
						{ __( 'Info', 'create-block-theme' ) }
					</>
				}
				onRequestClose={ toggleThemeJsonEditor }
			>
				<CodeMirror
					extensions={ [ json() ] }
					value={ value }
					onChange={ onChange }
					width="65vw"
				/>
			</Modal>
		);
	};

	return (
		<>
			<PluginSidebarMoreMenuItem
				target="create-block-theme-sidebar"
				icon={ tool }
			>
				{ _x(
					'Create Block Theme',
					'UI String',
					'create-block-theme'
				) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name="create-block-theme-sidebar"
				icon={ tool }
				title={ _x(
					'Create Block Theme',
					'UI String',
					'create-block-theme'
				) }
			>
				<NavigatorProvider initialPath="/">
					<NavigatorScreen path="/">
						<PanelBody>
							<VStack>
								<Button
									icon={ archive }
									onClick={ handleSaveClick }
								>
									{ __(
										'Save Changes',
										'create-block-theme'
									) }
								</Button>
								<Text variant="muted">
									{ __(
										'Save user changes (including Templates and Global Styles) to the theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<Button
									icon={ download }
									onClick={ handleExportClick }
								>
									{ __( 'Export Zip', 'create-block-theme' ) }
								</Button>
								<Text variant="muted">
									{ __(
										'Export your theme as a zip file. Note: You may want to save your user changes to the theme first.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/update" icon={ edit }>
									<Spacer />
									<HStack justify="space-between">
										<FlexItem>
											{ __(
												'Theme Info',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Edit Metadata properties of your current theme.',
										'create-block-theme'
									) }
								</Text>
								<hr></hr>
								<NavigatorButton path="/create" icon={ copy }>
									<Spacer />
									<HStack>
										<FlexItem>
											{ __(
												'Create Theme',
												'create-block-theme'
											) }
										</FlexItem>
										<Icon icon={ chevronRight } />
									</HStack>
								</NavigatorButton>
								<Text variant="muted">
									{ __(
										'Create a new theme based on your current theme and either save it or export it.',
										'create-block-theme'
									) }
								</Text>
							</VStack>
						</PanelBody>
					</NavigatorScreen>

					<NavigatorScreen path="/update">
						<UpdateThemePanel />
					</NavigatorScreen>

					<NavigatorScreen path="/create">
						<CreateThemePanel />
					</NavigatorScreen>
				</NavigatorProvider>
				<Button
					icon={ edit }
					onClick={ toggleThemeJsonEditor }
					isSecondary
				>
					{ __( 'Edit Theme JSON', 'create-block-theme' ) }
				</Button>
			</PluginSidebar>
			<Editor
				isOpen={ isEditorOpen }
				value={ themeData }
				onChange={ ( value ) => setThemeData( value ) }
			/>
		</>
	);
};

registerPlugin( 'cbt-plugin-sidebar', {
	render: CreateBlockThemePlugin,
} );
