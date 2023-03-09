import { __ } from '@wordpress/i18n';
import {
	Button,
	// eslint-disable-next-line
	__experimentalInputControl as InputControl,
	SelectControl,
} from '@wordpress/components';
import { Font } from 'lib-font';
import { useState } from '@wordpress/element';

import FontsPageLayout from '../fonts-page-layout';
import './local-fonts.css';

const INITIAL_FORM_DATA = {
	file: null,
	name: null,
	weight: null,
	style: null,
};

function LocalFonts() {
	// pickup the nonce from the input printed in the server
	const nonce = document.querySelector( '#nonce' ).value;
	const [ formData, setFormData ] = useState( INITIAL_FORM_DATA );

	const isFormValid = () => {
		return (
			formData.file && formData.name && formData.weight && formData.style
		);
	};

	const onFileSelectChange = ( event ) => {
		const file = event.target.files[ 0 ];

		if ( ! file ) {
			setFormData( INITIAL_FORM_DATA );
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
				const isItalic = name
					.get( 2 )
					.toLowerCase()
					.includes( 'italic' );
				const fontWeight =
					font.opentype.tables[ 'OS/2' ].usWeightClass || 'normal';

				setFormData( {
					file,
					name: fontName,
					weight: fontWeight,
					style: isItalic ? 'italic' : 'normal',
				} );
			};
		};
	};

	return (
		<FontsPageLayout>
			<main>
				<h1>Local Fonts</h1>
				<h3>
					{ __(
						'Add local fonts assets and font face definitions to your currently active theme',
						'create-block-theme'
					) }
				</h3>

				<form method="POST" action="" encType="multipart/form-data">
					<div className="form-group">
						<label htmlFor="font-file">
							{ __( 'Font file:', 'create-block-theme' ) }
						</label>
						<input
							type="file"
							name="font-file"
							id="font-file"
							onChange={ onFileSelectChange }
							accept=".ttf, .woff, .woff2"
						/>
						<small>
							{ __(
								'.ttf, .woff, .woff2 file extensions supported',
								'create-block-theme'
							) }
						</small>
					</div>

					<h3>
						{ __(
							'Font face definition for this font file:',
							'create-block-theme'
						) }
					</h3>

					<div className="form-group">
						<label htmlFor="font-name">
							{ __( 'Font name:', 'create-block-theme' ) }
						</label>
						<InputControl
							type="text"
							name="font-name"
							id="font-name"
							placeholder={ __(
								'Font name',
								'create-block-theme'
							) }
							required
							value={ formData.name }
							onChange={ ( val ) =>
								setFormData( { ...formData, name: val } )
							}
						/>
					</div>

					<div className="form-group">
						<label htmlFor="font-style">
							{ __( 'Font style:', 'create-block-theme' ) }
						</label>
						<SelectControl
							name="font-style"
							id="font-style"
							required
							value={ formData.style }
							onChange={ ( val ) =>
								setFormData( { ...formData, style: val } )
							}
						>
							<option value="normal">Normal</option>
							<option value="italic">Italic</option>
						</SelectControl>
					</div>

					<div className="form-group">
						<label htmlFor="font-weight">
							{ __( 'Font weight:', 'create-block-theme' ) }
						</label>
						<InputControl
							type="text"
							name="font-weight"
							id="font-weight"
							placeholder={ __(
								'Font weight:',
								'create-block-theme'
							) }
							required
							value={ formData.weight }
							onChange={ ( val ) =>
								setFormData( { ...formData, weight: val } )
							}
						/>
					</div>

					<Button
						variant="primary"
						type="submit"
						disabled={ ! isFormValid() }
					>
						{ __(
							'Upload font to your theme',
							'create-block-theme'
						) }
					</Button>
					<input type="hidden" name="nonce" value={ nonce } />
				</form>
			</main>
		</FontsPageLayout>
	);
}

export default LocalFonts;
