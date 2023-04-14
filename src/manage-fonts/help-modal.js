import { __ } from '@wordpress/i18n';
import { Modal, Icon } from '@wordpress/components';

function HelpModal( { isOpen, onClose } ) {
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
			onRequestClose={ onClose }
		>
			<p>
				{ __(
					'This is a list of your font families listed in the theme.json file of your theme.',
					'create-block-theme'
				) }
			</p>
			<p>
				{ __(
					'If your theme.json makes reference to fonts providers other than local they may not be displayed correctly.',
					'create-block-theme'
				) }
			</p>
		</Modal>
	);
}

export default HelpModal;
