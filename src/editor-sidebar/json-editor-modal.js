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
import { useSelect } from '@wordpress/data';

const ThemeJsonEditorModal = ( { onRequestClose } ) => {
	const [ themeData, setThemeData ] = useState( '' );
	const themeJsonData = useSelect(
		( select ) => select( 'core' ).getCurrentTheme(),
		[]
	);

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
