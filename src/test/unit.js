import { addQuotesToName } from '../utils';

describe( 'addQuotesToName', () => {
	const easyFonts = [ 'Roboto' ];
	const complicatedFonts = [
		'Roboto Mono',
		'Open Sans Condensed',
		'Exo 2',
		'Libre Barcode 128 Text',
		'Press Start 2P',
		'Rock 3D',
		'Rubik 80s Fade',
	];

	it( 'should add quotes to all font names', () => {
		[ ...easyFonts, ...complicatedFonts ].forEach( ( font ) => {
			expect( addQuotesToName( font ) ).toEqual( `'${ font }'` );
		} );
	} );

	it( 'should avoid FontFace objects with empty font name', () => {
		complicatedFonts.forEach( ( font ) => {
			const quoted = addQuotesToName( font );
			const fontObject = new FontFace( quoted, {} );

			expect( fontObject ).toBeInstanceOf( FontFace );
			expect( fontObject.family ).toEqual( quoted );
		} );
	} );
} );
