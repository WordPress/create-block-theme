import { __, sprintf } from '@wordpress/i18n';

export function PageHeader( { themeName } ) {
	return (
		<>
			<h1 className="wp-heading-inline">
				{ __( 'Create Block Theme', 'create-block-theme' ) }
			</h1>
			<p>
				{ __(
					'Create or export a block theme with changes made to Templates, Template Parts and Global Styles.',
					'create-block-theme'
				) }
			</p>
		</>
	);
}
