// The module under test imports WordPress packages that are provided as
// externals at build time and are not installed as npm dependencies. Mock them
// so Jest can resolve the module to test the pure mapping function.
jest.mock( '@wordpress/hooks', () => ( { addFilter: jest.fn() } ), {
	virtual: true,
} );
jest.mock( '@wordpress/data', () => ( { select: jest.fn() } ), {
	virtual: true,
} );
jest.mock( '@wordpress/preferences', () => ( { store: 'core/preferences' } ), {
	virtual: true,
} );

/**
 * Internal dependencies
 */
// eslint-disable-next-line import/first
import {
	expandTypographyDefaults,
	TYPOGRAPHY_SUPPORT_TO_CONTROL,
} from '../editor-enhancements/expand-typography-controls';

describe( 'expandTypographyDefaults', () => {
	it( 'returns input unchanged when null', () => {
		expect( expandTypographyDefaults( null ) ).toBe( null );
	} );

	it( 'returns input unchanged when undefined', () => {
		expect( expandTypographyDefaults( undefined ) ).toBe( undefined );
	} );

	it( 'returns input unchanged when not an object', () => {
		expect( expandTypographyDefaults( true ) ).toBe( true );
	} );

	it( 'yields an empty defaults map when no supports are enabled', () => {
		expect( expandTypographyDefaults( {} ) ).toEqual( {
			__experimentalDefaultControls: {},
		} );
	} );

	it( 'maps fontSize to fontSize', () => {
		expect( expandTypographyDefaults( { fontSize: true } ) ).toEqual( {
			fontSize: true,
			__experimentalDefaultControls: { fontSize: true },
		} );
	} );

	it( 'maps __experimentalFontFamily to fontFamily', () => {
		expect(
			expandTypographyDefaults( { __experimentalFontFamily: true } )
		).toEqual( {
			__experimentalFontFamily: true,
			__experimentalDefaultControls: { fontFamily: true },
		} );
	} );

	it( 'maps __experimentalFontWeight alone to fontAppearance', () => {
		expect(
			expandTypographyDefaults( { __experimentalFontWeight: true } )
		).toEqual( {
			__experimentalFontWeight: true,
			__experimentalDefaultControls: { fontAppearance: true },
		} );
	} );

	it( 'maps __experimentalFontStyle alone to fontAppearance', () => {
		expect(
			expandTypographyDefaults( { __experimentalFontStyle: true } )
		).toEqual( {
			__experimentalFontStyle: true,
			__experimentalDefaultControls: { fontAppearance: true },
		} );
	} );

	it( 'collapses both weight and style into a single fontAppearance entry', () => {
		const result = expandTypographyDefaults( {
			__experimentalFontWeight: true,
			__experimentalFontStyle: true,
		} );
		expect( result.__experimentalDefaultControls ).toEqual( {
			fontAppearance: true,
		} );
		expect(
			Object.keys( result.__experimentalDefaultControls ).length
		).toBe( 1 );
	} );

	it( 'ignores support keys that are falsy', () => {
		expect(
			expandTypographyDefaults( {
				fontSize: true,
				lineHeight: false,
				textAlign: undefined,
			} )
		).toEqual( {
			fontSize: true,
			lineHeight: false,
			textAlign: undefined,
			__experimentalDefaultControls: { fontSize: true },
		} );
	} );

	it( 'maps a full realistic supports.typography shape to every expected control', () => {
		const fullShape = {
			fontSize: true,
			lineHeight: true,
			textAlign: true,
			textColumns: true,
			textIndent: true,
			__experimentalFontFamily: true,
			__experimentalLetterSpacing: true,
			__experimentalTextDecoration: true,
			__experimentalTextTransform: true,
			__experimentalWritingMode: true,
			__experimentalFontWeight: true,
			__experimentalFontStyle: true,
		};
		expect(
			expandTypographyDefaults( fullShape ).__experimentalDefaultControls
		).toEqual( {
			fontSize: true,
			lineHeight: true,
			textAlign: true,
			textColumns: true,
			textIndent: true,
			fontFamily: true,
			letterSpacing: true,
			textDecoration: true,
			textTransform: true,
			writingMode: true,
			fontAppearance: true,
		} );
	} );

	it( 'preserves unknown keys already set in __experimentalDefaultControls', () => {
		const input = {
			fontSize: true,
			__experimentalDefaultControls: {
				fontSize: false,
				someFutureControl: true,
			},
		};
		const result = expandTypographyDefaults( input );
		expect( result.__experimentalDefaultControls ).toEqual( {
			fontSize: true,
			someFutureControl: true,
		} );
	} );

	it( 'preserves other keys on the typography supports object', () => {
		const input = {
			fontSize: true,
			customKey: 'untouched',
		};
		const result = expandTypographyDefaults( input );
		expect( result.customKey ).toBe( 'untouched' );
		expect( result.__experimentalDefaultControls ).toEqual( {
			fontSize: true,
		} );
	} );
} );

describe( 'TYPOGRAPHY_SUPPORT_TO_CONTROL', () => {
	it( 'merges fontWeight and fontStyle into fontAppearance', () => {
		expect( TYPOGRAPHY_SUPPORT_TO_CONTROL.__experimentalFontWeight ).toBe(
			'fontAppearance'
		);
		expect( TYPOGRAPHY_SUPPORT_TO_CONTROL.__experimentalFontStyle ).toBe(
			'fontAppearance'
		);
	} );
} );
