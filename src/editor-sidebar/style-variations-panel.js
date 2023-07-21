import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	MenuGroup,
	MenuItem,
	TextControl,
	Button,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const StyleVariationsPanel = () => {
	const [ variationName, setVariationName ] = useState( '' );
	const { createErrorNotice, createSuccessNotice } =
		useDispatch( noticesStore );

	async function createVariation() {
		if ( ! variationName ) {
			return;
		}

		try {
			const response = await apiFetch( {
				path: '/create-block-theme/v1/variation/' + variationName,
				method: 'POST',
			} );

			// Reload variations
			// We need to invalidate and maybe refetch __experimentalGetCurrentThemeGlobalStylesVariations(),
			if ( 'SUCCESS' === response.status ) {
				createSuccessNotice( __( 'Variation created successfully' ), {
					type: 'snackbar',
				} );
			}
		} catch ( error ) {
			const errorMessage =
				error.message && error.code !== 'unknown_error'
					? error.message
					: __( 'An error occurred while creating the site export.' );
			createErrorNotice( errorMessage, { type: 'snackbar' } );
		}
	}

	return (
		<MenuGroup label={ __( 'Create style variation' ) }>
			<MenuItem>
				<TextControl
					onChange={ setVariationName }
					placeholder={ __( 'Variation name' ) }
				/>
				<Button onClick={ createVariation }>
					{ __( 'Create variation' ) }
				</Button>
			</MenuItem>
		</MenuGroup>
	);
};
