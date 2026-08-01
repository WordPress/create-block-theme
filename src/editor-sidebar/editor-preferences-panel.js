/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	Card,
	CardBody,
	CheckboxControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';
import {
	PREFERENCE_SCOPE,
	PREFERENCE_KEY,
} from '../editor-enhancements/expand-typography-controls';

export const EditorPreferencesPanel = () => {
	const expandAllTypographyControls = useSelect(
		( select ) =>
			!! select( preferencesStore ).get(
				PREFERENCE_SCOPE,
				PREFERENCE_KEY
			),
		[]
	);

	const { set: setPreference } = useDispatch( preferencesStore );
	const { createSuccessNotice } = useDispatch( noticesStore );

	const handleToggle = ( value ) => {
		setPreference( PREFERENCE_SCOPE, PREFERENCE_KEY, value );
		createSuccessNotice(
			__(
				'Preference updated. The editor will now reload.',
				'create-block-theme'
			),
			{ type: 'snackbar' }
		);
		setTimeout( () => {
			window.location.reload();
		}, 1000 );
	};

	return (
		<Card size="small" isBorderless>
			<ScreenHeader
				title={ __( 'Editor preferences', 'create-block-theme' ) }
			/>
			<CardBody>
				<VStack spacing={ 4 }>
					<CheckboxControl
						__nextHasNoMarginBottom
						label={ __(
							'Expand typography controls in the block inspector',
							'create-block-theme'
						) }
						help={ __(
							'Shows the controls in the typography panel by default instead of hiding them behind the ellipsis menu. Applies in the Site Editor only. The editor will reload when you change this.',
							'create-block-theme'
						) }
						checked={ expandAllTypographyControls }
						onChange={ handleToggle }
					/>
				</VStack>
			</CardBody>
		</Card>
	);
};
