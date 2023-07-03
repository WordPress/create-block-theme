import { __, sprintf } from '@wordpress/i18n';
import { getThemeExportOptions } from './create-theme-options';
import styles from './styles.module.css';
import {
	Button,
	RangeControl,
	SelectControl,
	// eslint-disable-next-line
	__experimentalInputControl as InputControl,
    TextareaControl,
    CheckboxControl
} from '@wordpress/components';

export function CreateThemeForm( { themeName, tags, selectedOption } ) {
	const themeOptions = getThemeExportOptions( themeName );
	const optionDesc = ( themeOptions[ selectedOption ] || {} ).description;
	const showThemeForm = [ 'export', 'blank', 'child', 'sibling' ].includes(
		selectedOption
	);
	const showVariationForm = selectedOption === 'variation';

	return (
		<div>
			<div className={ styles.optionDesc }>{ optionDesc }</div>
			<div className={ styles.themeFormContainer }>
				<div className={ styles.themeForm }>
					{ showVariationForm && <VariationForm /> }
					{ showThemeForm && <ThemeForm /> }

					<input
						type="hidden"
						name="page"
						value="create-block-theme"
					/>
					<input type="hidden" name="nonce" value={ nonce } />
				</div>
				<div>{ showThemeForm && <ThemeTags tags={ tags } /> }</div>
			</div>
		</div>
	);
}

function ThemeForm() {
	return (
		<div id="new_theme_metadata_form" className="theme-form">
			{/* <p>
				<em>
					{ __(
						'Items indicated with (*) are required.',
						'create-block-theme'
					) }
				</em>
			</p> */}

            <InputControl
                label="Theme Name"
                required
                value={ '' }
                onChange={ () => {} }
            />

            <br />
            <TextareaControl
                __nextHasNoMarginBottom
                label={ __( 'Theme Description' ) }
                value={ '' }
                onChange={ ( ) => {} }
                placeholder='A short description of the theme.'
            />
			
            <br />
            <InputControl
                label="Theme URI"
                placeholder={ __(
                    'https://github.com/wordpress/twentytwentythree/',
                    'create-block-theme'
                ) }
                help="The URL of a public web page where users can find more information about the theme."
                value={ '' }
                onChange={ () => {} }
            />

			<br />
            <InputControl
                label="Author"
                placeholder={ __(
                    'WordPress Team',
                    'create-block-theme'
                ) }
                help="The name of the individual or organization who developed the theme."
                value={ '' }
                onChange={ () => {} }
            />

			<br />
			<InputControl
                label="Author URI"
                placeholder={ __(
                    'https://wordpress.org/',
                    'create-block-theme'
                ) }
                help="The URL of the authoring individual or organization."
                value={ '' }
                onChange={ () => {} }
            />

			<br />
			<label htmlFor="screenshot">
				{ __( 'Screenshot:', 'create-block-theme' ) }
				<br />
				<small>
					{ __(
						'Upload a new theme screenshot (2mb max | .png only | 1200x900 recommended)',
						'create-block-theme'
					) }
				</small>
				<br />
				<input
					type="file"
					accept=".png"
					name="screenshot"
					id="screenshot"
					className="upload"
				/>
			</label>
			<br />
			<br />
		</div>
	);
}

function VariationForm() {
	return (
		<div id="new_variation_metadata_form" className="theme-form">
			<InputControl
                label="Variation Name"
                required
                value={ '' }
                onChange={ () => {} }
            />
		</div>
	);
}

function ThemeTags( { tags } ) {
    const subjectTags = Object.entries(tags['Subject'] || {}).map(([key,value]) => ({value: key, label: value}))
    const layoutTags = Object.entries(tags['Layout'] || {}).map(([key,value]) => ({value: key, label: value}))
    const featureTags = Object.entries(tags['Features'] || {}).map(([key,value]) => ({value: key, label: value}))
    
	return (
		<div>
			<div>
				<span>Theme Tags:</span>
				<br />
				<span>Add theme tags to help categorize the theme </span>
				<a href="https://make.wordpress.org/themes/handbook/review/required/theme-tags/">
					read more
				</a>
			</div>

            <div style={{marginTop: '1rem', marginBottom: '0.5rem'}}>Subject (max 3):</div>
			<div style={{display: 'grid', gridTemplateColumns: '1fr 1fr'}}>{ subjectTags.map(tag => {
                return <div>
                    <CheckboxControl
						// __nextHasNoMarginBottom
						label={ tag.label }
						checked={ false }
						// indeterminate={ isMixed }
						onChange={ ( newValue ) => {}}
					/>
                </div>
            }) }</div>

            <div style={{marginTop: '1rem', marginBottom: '0.5rem'}}>Layout:</div>
			<div style={{display: 'grid', gridTemplateColumns: '1fr 1fr'}}>{ layoutTags.map(tag => {
                return <div>
                    <CheckboxControl
						// __nextHasNoMarginBottom
						label={ tag.label }
						checked={ false }
						// indeterminate={ isMixed }
						onChange={ ( newValue ) => {}}
					/>
                </div>
            }) }</div>

            <div style={{marginTop: '1rem', marginBottom: '0.5rem'}}>Features:</div>
			<div style={{display: 'grid', gridTemplateColumns: '1fr 1fr'}}>{ featureTags.map(tag => {
                return <div>
                    <CheckboxControl
						// __nextHasNoMarginBottom
						label={ tag.label }
						checked={ false }
						// indeterminate={ isMixed }
						onChange={ ( newValue ) => {}}
					/>
                </div>
            }) }</div>
		</div>
	);
}
