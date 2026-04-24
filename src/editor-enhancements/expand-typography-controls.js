/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

// Mapping verified against
// gutenberg/packages/block-editor/src/components/global-styles/typography-panel.js
// fontWeight and fontStyle collapse into the single "fontAppearance" control.
export const TYPOGRAPHY_SUPPORT_TO_CONTROL = {
	fontSize: 'fontSize',
	lineHeight: 'lineHeight',
	textAlign: 'textAlign',
	textColumns: 'textColumns',
	textIndent: 'textIndent',
	__experimentalFontFamily: 'fontFamily',
	__experimentalLetterSpacing: 'letterSpacing',
	__experimentalTextDecoration: 'textDecoration',
	__experimentalTextTransform: 'textTransform',
	__experimentalWritingMode: 'writingMode',
	__experimentalFontWeight: 'fontAppearance',
	__experimentalFontStyle: 'fontAppearance',
};

export const PREFERENCE_SCOPE = 'create-block-theme';
export const PREFERENCE_KEY = 'expandAllTypographyControls';

export function expandTypographyDefaults( typographySupport ) {
	if ( ! typographySupport || typeof typographySupport !== 'object' ) {
		return typographySupport;
	}
	const defaults = {};
	for ( const [ supportKey, controlKey ] of Object.entries(
		TYPOGRAPHY_SUPPORT_TO_CONTROL
	) ) {
		if ( typographySupport[ supportKey ] ) {
			defaults[ controlKey ] = true;
		}
	}
	return {
		...typographySupport,
		__experimentalDefaultControls: defaults,
	};
}

addFilter(
	'blocks.registerBlockType',
	'create-block-theme/expand-typography-controls',
	( settings ) => {
		const enabled = select( preferencesStore )?.get?.(
			PREFERENCE_SCOPE,
			PREFERENCE_KEY
		);
		if ( ! enabled || ! settings?.supports?.typography ) {
			return settings;
		}
		return {
			...settings,
			supports: {
				...settings.supports,
				typography: expandTypographyDefaults(
					settings.supports.typography
				),
			},
		};
	}
);
