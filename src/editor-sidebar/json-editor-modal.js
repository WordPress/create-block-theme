import { useState, useEffect } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import CodeMirror from '@uiw/react-codemirror';
import { json } from '@codemirror/lang-json';
import { fetchThemeJson } from '../resolvers';

const ThemeJsonEditorModal = ( { onRequestClose } ) => {
	const [ themeData, setThemeData ] = useState( '' );
	const themeName = useSelect( ( select ) =>
		select( 'core' ).getCurrentTheme()
	)?.name?.raw;
	const fetchThemeData = async () => {
		setThemeData( await fetchThemeJson() );
	};
	const handleSave = () => {};

	useEffect( () => {
		fetchThemeData();
	} );

	return (
		<Modal
			isFullScreen
			title={ `theme.json for ${ themeName }` }
			onRequestClose={ onRequestClose }
		>
			<CodeMirror
				extensions={ [ json() ] }
				value={ themeData }
				onChange={ handleSave }
				readOnly={ true }
			/>
		</Modal>
	);
};

export default ThemeJsonEditorModal;
