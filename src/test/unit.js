/**
 * Internal dependencies
 */
import { generateWpVersions } from '../utils/generate-versions';

describe( 'generateWpVersions', () => {
	it( 'should generate version list down to 5.9 for version 6.5', () => {
		const versions = generateWpVersions( '6.5' );
		expect( versions[ 0 ] ).toBe( '6.5' );
		expect( versions[ versions.length - 1 ] ).toBe( '5.9' );
		expect( versions ).toContain( '6.0' );
		expect( versions ).toContain( '5.9' );
	} );

	it( 'should handle versions with release candidate suffixes', () => {
		const versions = generateWpVersions( '6.2-RC1' );
		expect( versions[ 0 ] ).toBe( '6.2' );
		expect( versions[ versions.length - 1 ] ).toBe( '5.9' );
	} );
} );
