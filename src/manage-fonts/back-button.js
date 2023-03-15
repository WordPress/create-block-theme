import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

function BackButton() {
	const { adminUrl } = createBlockTheme;
	return (
		<Button
			varint="secondary"
			icon={ chevronLeft }
			href={ `${ adminUrl }themes.php?page=manage-fonts` }
			iconSize={ 20 }
			style={ {
				padding: '0',
				height: '1.5rem',
				minWidth: '1.5rem',
				marginLeft: '-.5rem',
			} }
			aria-label={ __( 'Back to manage fonts', 'create-block-theme' ) }
		></Button>
	);
}

export default BackButton;
