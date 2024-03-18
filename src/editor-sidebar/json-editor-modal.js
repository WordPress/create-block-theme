import { useState, useEffect } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import CodeMirror from '@uiw/react-codemirror';
import { json } from '@codemirror/lang-json';
import { fetchThemeJson } from '../resolvers';

const ThemeJsonEditorModal = ( { onRequestClose } ) => {
	const [ themeData, setThemeData ] = useState( '' );

	const fetchThemeData = async () => {
		setThemeData( await fetchThemeJson() );
	};

	useEffect( () => {
		fetchThemeData();
	} );

	return (
		<Modal
			isFullScreen
			title={ <>theme.json</> }
			onRequestClose={ onRequestClose }
		>
			<CodeMirror
				extensions={ [ json() ] }
				value={ themeData }
				// onChange={ onChange }
			/>
		</Modal>
	);
};

export default ThemeJsonEditorModal;
