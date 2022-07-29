import { __ } from '@wordpress/i18n';
import { apiFetch } from '@wordpress/data-controls';
import {
	MenuGroup,
	MenuItem,
	TextControl,
	Button,
} from '@wordpress/components';

const GlobalStylesProvider = wp.editSite.GlobalStylesProvider;

export default StyleVariations = () => {
	const [ variationName, setVariationName ] = useState( '' );
	const [ canReset, onReset ] = wp.editSite.useGlobalStylesReset();
	const { createErrorNotice } = useDispatch( noticesStore );

	async function createVariation() {
		try {
			const response = await apiFetch( {
				path: '/create-block-theme/v1/variation/' + variationName,
			} );

			// Clear global styles customizations
			onReset();

			// Reload variations
			// We need to invalidate and maybe refetch __experimentalGetCurrentThemeGlobalStylesVariations(),
		} catch ( errorResponse ) {
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
