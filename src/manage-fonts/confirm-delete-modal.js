import {
	// eslint-disable-next-line
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { localizeFontStyle } from '../utils';

function ConfirmDeleteModal( { isOpen, onConfirm, onCancel, fontToDelete } ) {
	const deleteFontFaceMessage = sprintf(
		// translators: %1$s: Font Style, %2$s: Font Weight, %3$s: Font Family
		__(
			`Are you sure you want to delete "%1$s - %2$s" variant of "%3$s" from your theme?`,
			'create-block-theme'
		),
		fontToDelete?.weight,
		localizeFontStyle( fontToDelete?.style ),
		fontToDelete?.fontFamily
	);

	const deleteFontFamilyMessage = sprintf(
		// translators: %s: Font Family
		__(
			`Are you sure you want to delete "%s" from your theme?`,
			'create-block-theme'
		),
		fontToDelete?.fontFamily
	);

	return (
		<ConfirmDialog
			isOpen={ isOpen }
			onConfirm={ onConfirm }
			onCancel={ onCancel }
		>
			{ fontToDelete?.weight !== undefined &&
			fontToDelete.style !== undefined ? (
				<h3>{ deleteFontFaceMessage }</h3>
			) : (
				<h3>{ deleteFontFamilyMessage }</h3>
			) }
			<p>
				{ __(
					'This action will delete the font definition and the font file assets from your theme.',
					'create-block-theme'
				) }
			</p>
		</ConfirmDialog>
	);
}

export default ConfirmDeleteModal;
