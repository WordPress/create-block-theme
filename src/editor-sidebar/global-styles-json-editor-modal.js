/**
 * External dependencies
 */
import CodeMirror from '@uiw/react-codemirror';
import { json } from '@codemirror/lang-json';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

const GlobalStylesJsonEditorModal = ( { onRequestClose } ) => {
	const themeName = useSelect( ( select ) =>
		select( 'core' ).getCurrentTheme()
	)?.name?.raw;

	const { record: globalStylesRecord } = useSelect( ( select ) => {
		const {
			__experimentalGetCurrentGlobalStylesId,
			getEditedEntityRecord,
		} = select( coreStore );
		const globalStylesId = __experimentalGetCurrentGlobalStylesId();
		const record = getEditedEntityRecord(
			'root',
			'globalStyles',
			globalStylesId
		);
		return {
			record,
		};
	} );

	const globalStyles = {
		...( globalStylesRecord?.styles && {
			styles: globalStylesRecord.styles,
		} ),
		...( globalStylesRecord?.settings && {
			settings: globalStylesRecord.settings,
		} ),
	};

	const globalStylesAsString = globalStyles
		? JSON.stringify( globalStyles, null, 4 )
		: '';

	const handleSave = () => {};

	return (
		<Modal
			size="large"
			title={ sprintf(
				// translators: %s: theme name.
				__( 'Custom Styles for %s', 'create-block-theme' ),
				themeName
			) }
			onRequestClose={ onRequestClose }
			className="create-block-theme__theme-json-modal"
		>
			<CodeMirror
				extensions={ [ json() ] }
				value={ globalStylesAsString }
				onChange={ handleSave }
				readOnly
			/>
		</Modal>
	);
};

export default GlobalStylesJsonEditorModal;
