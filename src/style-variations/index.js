import { __ } from '@wordpress/i18n';
import { apiFetch } from '@wordpress/data-controls';
import {
	MenuGroup,
	MenuItem,
	TextControl,
	Button,
} from '@wordpress/components';

const GlobalStylesProvider = wp.editSite.GlobalStylesProvider;

export default GlobalStylesProvider = wp.editSite.unstableGlobalStylesProvider;

const StyleVariations = () => {
	const [ variationName, setVariationName ] = useState( '' );
	const [ canReset, onReset ] = wp.editSite.unstableUseGlobalStylesReset();
	const { createErrorNotice } = useDispatch( noticesStore );

	if ( ! canReset ) {
		return null; // This requires the Gutenberg plugin.
	}

	async function createVariation() {
		try {
			const response = await apiFetch( {
				path: '/create-block-theme/v1/variation/' + variationName,
			} );

			// Clear global styles customizations
			onReset();

			// Reload variations
			// We need to invalidate and maybe refetch __experimentalGetCurrentThemeGlobalStylesVariations(),
			if ( response.req.status === 200 ) {
				createErrorNotice( __( 'Variation created successfully' ), {
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
		<GlobalStylesProvider>
			<MenuGroup label={ __( 'Create style variation' ) }>
				<MenuItem>
					<TextControl
						onChange={ ( value ) => setVariationName( value ) }
						placeholder={ __( 'Variation name' ) }
					></TextControl>
					<Button onClick={ () => createVariation() }>
						{ __( 'Create variation' ) }
					</Button>
				</MenuItem>
			</MenuGroup>
		</GlobalStylesProvider>
	);
};
