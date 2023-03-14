import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

import UploadFontForm from './upload-font-form';
import './local-fonts.css';
import DemoTextInput from '../demo-text-input';
import Demo from '../demo-text-input/demo';
import { variableAxesToCss } from './utils';

const INITIAL_FORM_DATA = {
	file: null,
	name: null,
	weight: null,
	style: null,
};

function LocalFonts() {
	const [ formData, setFormData ] = useState( INITIAL_FORM_DATA );

	const isFormValid = () => {
		return (
			formData.file && formData.name && formData.weight && formData.style
		);
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
			style.fontVariationSettings = variableAxesToCss( formData.axes );
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

	const debounceOnFormDataChange = useDebounce( onFormDataChange, 500 );

	useEffect( () => {
		debounceOnFormDataChange();
	}, [ formData ] );

	return (
		<div className="layout">
			<main>
				<h1>{ __( 'Local Fonts', 'create-block-theme' ) }</h1>
				<h3>
					{ __(
						'Add local fonts assets and font face definitions to your currently active theme',
						'create-block-theme'
					) }
				</h3>

				<UploadFontForm
					isFormValid={ isFormValid }
					formData={ formData }
					setFormData={ setFormData }
				/>
			</main>

			{ isFormValid() && (
				<div className="preview">
					<DemoTextInput />
					<p>{ __( 'Demo:', 'create-block-theme' ) }</p>
					<Demo style={ demoStyle() } />
				</div>
			) }
		</div>
	);
}

export default LocalFonts;
