/**
 * External dependencies
 */
import CodeMirror from '@uiw/react-codemirror';
import { json } from '@codemirror/lang-json';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const ThemeJsonEditorModal = ( { onRequestClose } ) => {
	const [ themeData, setThemeData ] = useState( '' );
	const [ themeName, setThemeName ] = useState( '' );
	const { invalidateResolution } = useDispatch( 'core' );

	// Fetch directly via the REST API on every mount so the modal always
	// shows the on-disk theme.json — bypassing the @wordpress/core-data
	// cache that would otherwise serve stale content when the user opens
	// View theme.json straight after closing the Edit Theme Settings modal.
	// Also invalidate the cached resolution so other subscribers refresh.
	useEffect( () => {
		let cancelled = false;
		invalidateResolution( 'getCurrentTheme' );
		apiFetch( { path: '/wp/v2/themes?status=active' } )
			.then( ( themes ) => {
				if ( cancelled ) {
					return;
				}
				const active = Array.isArray( themes ) ? themes[ 0 ] : null;
				if ( ! active ) {
					return;
				}
				setThemeName( active?.name?.raw ?? '' );
				setThemeData( JSON.stringify( active?.theme_json, null, 2 ) );
			} )
			.catch( () => {
				// Swallow — leave the modal showing whatever was last rendered.
			} );
		return () => {
			cancelled = true;
		};
	}, [ invalidateResolution ] );

	const handleSave = () => {};

	return (
		<Modal
			size="large"
			title={ sprintf(
				// translators: %s: theme name.
				__( 'theme.json for %s', 'create-block-theme' ),
				themeName
			) }
			onRequestClose={ onRequestClose }
			className="create-block-theme__theme-json-modal"
		>
			<CodeMirror
				extensions={ [ json() ] }
				value={ themeData }
				onChange={ handleSave }
				readOnly
			/>
		</Modal>
	);
};

export default ThemeJsonEditorModal;
