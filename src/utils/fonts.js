/**
 * Internal dependencies
 */
import { getFontFamilies } from '../resolvers';
import { Font } from '../lib/lib-font/lib-font.browser';

/**
 * Fetch a file from a URL and return it as an ArrayBuffer.
 *
 * @param {string} url The URL of the file to fetch.
 * @return {Promise<ArrayBuffer>} The file as an ArrayBuffer.
 */
async function fetchFileAsArrayBuffer( url ) {
	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( 'Network response was not ok.' );
	}
	const arrayBuffer = await response.arrayBuffer();
	return arrayBuffer;
}

/**
 * Retrieves the licensing information of a font file given its URL.
 *
 * This function fetches the file as an ArrayBuffer, initializes a font object, and extracts licensing details from the font's OpenType tables.
 *
 * @param {string} url - The URL pointing directly to the font file. The URL should be a direct link to the file and publicly accessible.
 * @return {Promise<Object>} A promise that resolves to an object containing the font's licensing details.
 *
 * The returned object includes the following properties (if available in the font's OpenType tables):
 *   - fontName: The full font name.
 *   - copyright: Copyright notice.
 *   - source: Unique identifier for the font's source.
 *   - license: License description.
 *   - licenseURL: URL to the full license text.
 */
async function getFontFileLicenseFromUrl( url ) {
	const buffer = await fetchFileAsArrayBuffer( url );
	const fontObj = new Font( 'Uploaded Font' );
	fontObj.fromDataBuffer( buffer, url );
	// Assuming that fromDataBuffer triggers onload event and returning a Promise
	const onloadEvent = await new Promise(
		( resolve ) => ( fontObj.onload = resolve )
	);
	const font = onloadEvent.detail.font;
	const { name: nameTable } = font.opentype.tables;
	return {
		fontName: nameTable.get( 16 ) || nameTable.get( 1 ),
		copyright: nameTable.get( 0 ),
		source: nameTable.get( 11 ),
		license: nameTable.get( 13 ),
		licenseURL: nameTable.get( 14 ),
	};
}

/**
 * Get the license for a font family.
 *
 * @param {Object} fontFamily The font family in theme.json format.
 * @return {Promise<Object|null>} A promise that resolved to the font license object if sucessful or null if the font family does not have a fontFace property.
 */
async function getFamilyLicense( fontFamily ) {
	// If the font family does not have a fontFace property, return an empty string.
	if ( ! fontFamily.fontFace?.length ) {
		return null;
	}

	// Load the fontFace from the first fontFace object in the font family.
	const fontFace = fontFamily.fontFace[ 0 ];
	const faceUrl = Array.isArray( fontFace.src )
		? fontFace.src[ 0 ]
		: fontFace.src;

	// Get the license from the font face url.
	return await getFontFileLicenseFromUrl( faceUrl );
}

/**
 * Get the text for the font licenses of all the fonts defined in the theme.
 *
 * @return {Promise<Array>} A promise that resolves to an array containing font credits objects.
 */
async function getFontsCreditsArray() {
	const fontFamilies = await getFontFamilies();

	//Remove duplicates. Removes the font families that have the same fontFamily property.
	const uniqueFontFamilies = fontFamilies.filter(
		( fontFamily, index, self ) =>
			index ===
			self.findIndex( ( t ) => t.fontFamily === fontFamily.fontFamily )
	);

	const credits = [];

	// Iterate over fontFamilies and get the license for each family
	for ( const fontFamily of uniqueFontFamilies ) {
		const fontCredits = await getFamilyLicense( fontFamily );
		if ( fontCredits ) {
			credits.push( fontCredits );
		}
	}

	return credits;
}

/**
 * Get the text for the font licenses of all the fonts defined in the theme.
 *
 * @return {Promise<string>} A promise that resolves to an string containing the formatted font licenses.
 */
export async function getFontsCreditsText() {
	const creditsArray = await getFontsCreditsArray();
	const credits = creditsArray
		.reduce( ( acc, credit ) => {
			// skip if fontName is not available
			if ( ! credit.fontName ) {
				// continue
				return acc;
			}

			acc.push( credit.fontName );

			if ( credit.copyright ) {
				acc.push( credit.copyright );
			}

			if ( credit.source ) {
				acc.push( `Source: ${ credit.source }` );
			}

			if ( credit.license ) {
				acc.push( `License: ${ credit.license }` );
			}

			acc.push( '' );

			return acc;
		}, [] )
		.join( '\n' );
	return credits;
}
