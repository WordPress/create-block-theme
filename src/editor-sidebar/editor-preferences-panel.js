/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
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

	const handleToggle = ( value ) => {
		setPreference( PREFERENCE_SCOPE, PREFERENCE_KEY, value );
		// eslint-disable-next-line no-alert
		window.alert(
			__(
				'Preference updated. The editor will now reload.',
				'create-block-theme'
			)
		);
		window.location.reload();
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
							'Expand all typography controls in the block inspector',
							'create-block-theme'
						) }
						help={ __(
							'Shows every typography control by default instead of hiding them behind the ellipsis menu. Applies in the Site Editor only. The editor will reload when you change this.',
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
