const fontFileElement = document.querySelector( '#font-file' );
const fontNameElement = document.querySelector( '#font-name' );
const fontStyleElement = document.querySelector( '#font-style' );
const fontWeightElement = document.querySelector( '#font-weight' );

function resetForm() {
	fontNameElement.value = '';
	fontStyleElement.value = 'normal';
	fontWeightElement.value = '';
}

fontFileElement.onchange = ( evt ) => {
	// Grab file from drop event or file upload
	const file = evt.target.files[ 0 ];

	if ( ! file ) {
		resetForm();
	}

	// Use FileReader to, well, read the file
	const reader = new FileReader();
	reader.readAsArrayBuffer( file );

	reader.onload = () => {
		// Create a font object
		const fontObj = new Font( 'Uploaded Font' );

		// Pass the buffer, and the original filename
		fontObj.fromDataBuffer( reader.result, file.name );

		fontObj.onload = ( onloadEvent ) => {
			// Map the details LibFont gathered from the font to the
			// "font" variable
			const font = onloadEvent.detail.font;

			// From all the OpenType tables in the font, take the "name"
			// table so we can inspect it further
			const { name } = font.opentype.tables;

			// From the name table, take the entry with ID "1". This is
			// the Font Family name. More info and names you can grab:
			// https://docs.microsoft.com/en-us/typography/opentype/spec/name

			const fontName = name.get( 1 );
			const isItalic = name.get( 2 ).toLowerCase().includes( 'italic' );
			const fontWeight =
				font.opentype.tables[ 'OS/2' ].usWeightClass || 'normal';

			fontNameElement.value = fontName;
			fontStyleElement.value = isItalic ? 'italic' : 'normal';
			fontWeightElement.value = fontWeight;
		};
	};
};
