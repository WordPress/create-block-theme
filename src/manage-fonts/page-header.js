import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';

function PageHeader( { toggleIsHelpOpen } ) {
	const { adminUrl } = createBlockTheme;

	return (
		<>
			<div className="manage-fonts-header-flex">
				<h1 className="wp-heading-inline">
					{ __( 'Manage Theme Fonts', 'create-block-theme' ) }
				</h1>
				<div className="buttons">
					<Button
						href={ `${ adminUrl }themes.php?page=add-google-font-to-theme-json` }
						variant="secondary"
					>
						{ __( 'Add Google Font', 'create-block-theme' ) }
					</Button>
					<Button
						href={ `${ adminUrl }themes.php?page=add-local-font-to-theme-json` }
						variant="secondary"
					>
						{ __( 'Add Local Font', 'create-block-theme' ) }
					</Button>
				</div>
			</div>
			<hr className="wp-header-end" />

			<p className="help">
				{ __(
					'These are the fonts currently embedded in your theme ',
					'create-block-theme'
				) }
				<Button
					onClick={ toggleIsHelpOpen }
					style={ { padding: '0', height: '1rem' } }
				>
					<Icon icon={ 'info' } />
				</Button>
			</p>
		</>
	);
}

export default PageHeader;
