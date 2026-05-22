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
import { useSelect, useDispatch } from '@wordpress/data';

const ThemeJsonEditorModal = ( { onRequestClose } ) => {
	const [ themeData, setThemeData ] = useState( '' );
	const themeJsonData = useSelect(
		( select ) => select( 'core' ).getCurrentTheme(),
		[]
	);
	const { invalidateResolution } = useDispatch( 'core' );

	// Force a fresh fetch on every mount so the modal reflects writes the
	// Edit Theme Settings flow has made to theme.json since the resolver
	// last resolved.
	useEffect( () => {
		invalidateResolution( 'getCurrentTheme' );
	}, [ invalidateResolution ] );

	useEffect( () => {
		if ( themeJsonData ) {
			setThemeData(
				JSON.stringify( themeJsonData?.theme_json, null, 2 )
			);
		}
	}, [ themeJsonData ] );

	const handleSave = () => {};

	return (
		<Modal
			size="large"
			title={ sprintf(
				// translators: %s: theme name.
				__( 'theme.json for %s', 'create-block-theme' ),
				themeJsonData?.name?.raw ?? ''
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
