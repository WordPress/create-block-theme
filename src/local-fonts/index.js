import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import UploadFontForm from './upload-font-form';
import './local-fonts.css';
import DemoTextInput from '../demo-text-input';
import Demo from '../demo-text-input/demo';
import { variableAxesToCss } from '../demo-text-input/utils';
import BackButton from '../manage-fonts/back-button';

const INITIAL_FORM_DATA = {
	file: null,
	name: null,
	weight: null,
	style: null,
};

function LocalFonts() {
	const [ formData, setFormData ] = useState( INITIAL_FORM_DATA );
	const [ axes, setAxes ] = useState( {} );

	const resetFormData = () => {
		setFormData( INITIAL_FORM_DATA );
	};

	const resetAxes = () => {
		const newAxes = Object.keys( axes ).reduce( ( acc, axisTag ) => {
			acc[ axisTag ] = {
				...axes[ axisTag ],
				currentValue: axes[ axisTag ].defaultValue,
			};
			return acc;
		}, {} );
		setAxes( newAxes );
	};

	const isFormValid = () => {
		const isValid = formData.file && formData.name && formData.style;

		// if the font is not variable weight, the weight is required
		if ( ! formData.variableWeight ) {
			return isValid && formData.weight;
		}

		return isValid;
	};

	const demoStyle = () => {
		if ( ! isFormValid() ) {
			return {};
		}
		const style = {
			fontFamily: formData.name,
			fontWeight: formData.weight,
			fontStyle: formData.style,
		};
		if ( formData.variable ) {
			style.fontVariationSettings = variableAxesToCss( axes );
		}
		return style;
	};

	// load the local font in the browser to make the preview work
	const onFormDataChange = async () => {
		if ( ! isFormValid() ) {
			return;
		}

		const data = await formData.file.arrayBuffer();
		const newFont = new FontFace( formData.name, data, {
			style: formData.style,
			weight: formData.weight,
		} );
		newFont
			.load()
			.then( function ( loadedFace ) {
				document.fonts.add( loadedFace );
			} )
			.catch( function ( error ) {
				// TODO: show error in the UI
				// eslint-disable-next-line
				console.error( error );
			} );
	};

	useEffect( () => {
		onFormDataChange();
	}, [ formData ] );

	return (
		<div className="layout">
			<main>
				<header>
					<BackButton />
					<h1>{ __( 'Local Fonts', 'create-block-theme' ) }</h1>
					<p>
						{ __(
							'Add local fonts assets and font face definitions to your currently active theme',
							'create-block-theme'
						) }
					</p>
				</header>
				<UploadFontForm
					isFormValid={ isFormValid }
					formData={ formData }
					setFormData={ setFormData }
					resetFormData={ resetFormData }
					setAxes={ setAxes }
				/>
			</main>

			<div className="preview">
				<h2>{ __( 'Font file preview', 'create-block-theme' ) }</h2>

				{ isFormValid() ? (
					<>
						<DemoTextInput
							axes={ axes }
							setAxes={ setAxes }
							resetAxes={ resetAxes }
						/>
						<p>{ __( 'Demo:', 'create-block-theme' ) }</p>
						<Demo style={ demoStyle() } />
					</>
				) : (
					<p>
						{ __(
							'Load a font file to preview it.',
							'create-block-theme'
						) }
					</p>
				) }
			</div>
		</div>
	);
}

export default LocalFonts;
