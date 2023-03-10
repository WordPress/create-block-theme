import {
	Button,
	// eslint-disable-next-line
	__experimentalInputControl as InputControl,
	SelectControl,
} from '@wordpress/components';
import { update } from '@wordpress/icons';
import { Font } from 'lib-font';
import { __ } from '@wordpress/i18n';
import AxisRangeControl from './axis-range-control';
import { variableAxesToCss } from './utils';

function UploadFontForm( { formData, setFormData, isFormValid } ) {
	// pickup the nonce from the input printed in the server
	const nonce = document.querySelector( '#nonce' ).value;

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

				// Variable fonts info
				const isVariable = !! font.opentype.tables.fvar;
				const axes = isVariable
					? font.opentype.tables.fvar.axes.reduce(
							(
								acc,
								{ tag, minValue, defaultValue, maxValue }
							) => {
								acc[ tag ] = {
									tag,
									minValue,
									defaultValue,
									maxValue,
									currentValue: defaultValue,
								};
								return acc;
							},
							{}
					  )
					: {};

				setFormData( {
					file,
					name: fontName,
					weight: fontWeight,
					style: isItalic ? 'italic' : 'normal',
					variable: isVariable,
					axes,
				} );
			};
		};
	};

	const resetDefaultVariableSettings = () => {
		const newAxes = Object.keys( formData.axes ).reduce(
			( acc, axisTag ) => {
				acc[ axisTag ] = {
					...formData.axes[ axisTag ],
					currentValue: formData.axes[ axisTag ].defaultValue,
				};
				return acc;
			},
			{}
		);
		setFormData( {
			...formData,
			axes: newAxes,
		} );
	};

	const fontVariationSettings = variableAxesToCss( formData.axes );

	return (
		<>
			<form
				method="POST"
				id="font-upload-form"
				action=""
				encType="multipart/form-data"
			>
				<input type="hidden" name="nonce" value={ nonce } />

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

				<h4>
					{ __(
						'Font face definition for this font file:',
						'create-block-theme'
					) }
				</h4>

				<div className="form-group">
					<InputControl
						label={ __( 'Font name:', 'create-block-theme' ) }
						type="text"
						name="font-name"
						id="font-name"
						placeholder={ __( 'Font name', 'create-block-theme' ) }
						required
						value={ formData.name }
						onChange={ ( val ) =>
							setFormData( { ...formData, name: val } )
						}
					/>
				</div>

				<div className="form-group">
					<SelectControl
						label={ __( 'Font style:', 'create-block-theme' ) }
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
					<InputControl
						label={ __( 'Font weight:', 'create-block-theme' ) }
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

				{ formData.variable && (
					<input
						type="hidden"
						name="font-variation-settings"
						value={ fontVariationSettings }
					/>
				) }
			</form>

			{ /* Render the range controls for each available axis of a variable font */ }
			{ formData.variable && (
				<div className="variable-settings">
					<div className="header">
						<p>Variable font settings:</p>
						<Button
							isSmall
							icon={ update }
							variant="secondary"
							onClick={ resetDefaultVariableSettings }
						>
							Default settings
						</Button>
					</div>
					{ Object.keys( formData.axes ).map( ( key ) => (
						<AxisRangeControl
							axis={ formData.axes[ key ] }
							key={ `axis-range-${ key }` }
							formData={ formData }
							setFormData={ setFormData }
						/>
					) ) }
				</div>
			) }

			<Button
				variant="primary"
				type="submit"
				disabled={ ! isFormValid() }
				form="font-upload-form"
			>
				{ __( 'Upload font to your theme', 'create-block-theme' ) }
			</Button>
		</>
	);
}

export default UploadFontForm;
