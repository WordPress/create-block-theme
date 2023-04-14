import { __, sprintf } from '@wordpress/i18n';
import { getThemeExportOptions } from './create-theme-options';
import styles from './styles.module.css';

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
			<p>
				<em>
					{ __(
						'Items indicated with (*) are required.',
						'create-block-theme'
					) }
				</em>
			</p>
			<label>
				{ __( 'Theme Name (*):', 'create-block-theme' ) }
				<br />
				<input
					placeholder={ __( 'Theme Name', 'create-block-theme' ) }
					type="text"
					name="theme[name]"
					className="large-text"
				/>
			</label>
			<br />
			<br />
			<label>
				{ __( 'Theme Description:', 'create-block-theme' ) }
				<br />
				<textarea
					placeholder={ __(
						'A short description of the theme.',
						'create-block-theme'
					) }
					rows="4"
					cols="50"
					name="theme[description]"
					className="large-text"
				></textarea>
			</label>
			<br />
			<br />
			<label>
				{ __( 'Theme URI:', 'create-block-theme' ) }
				<br />
				<small>
					{ __(
						'The URL of a public web page where users can find more information about the theme.',
						'create-block-theme'
					) }
				</small>
				<br />
				<input
					placeholder={ __(
						'https://github.com/wordpress/twentytwentythree/',
						'create-block-theme'
					) }
					type="text"
					name="theme[uri]"
					className="large-text code"
				/>
			</label>
			<br />
			<br />
			<label>
				{ __( 'Author:', 'create-block-theme' ) }
				<br />
				<small>
					{ __(
						'The name of the individual or organization who developed the theme.',
						'create-block-theme'
					) }
				</small>
				<br />
				<input
					placeholder={ __(
						'the WordPress team',
						'create-block-theme'
					) }
					type="text"
					name="theme[author]"
					className="large-text"
				/>
			</label>
			<br />
			<br />
			<label>
				{ __( 'Author URI:', 'create-block-theme' ) }
				<br />
				<small>
					{ __(
						'The URL of the authoring individual or organization.',
						'create-block-theme'
					) }
				</small>
				<br />
				<input
					placeholder={ __(
						'https://wordpress.org/',
						'create-block-theme'
					) }
					type="text"
					name="theme[author_uri]"
					className="large-text code"
				/>
			</label>
			<br />
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
			<p>
				<em>
					{ __(
						'Items indicated with (*) are required.',
						'create-block-theme'
					) }
				</em>
			</p>
			<label>
				{ __( 'Variation Name (*):', 'create-block-theme' ) }
				<br />
				<input
					placeholder={ __( 'Variation Name', 'create-block-theme' ) }
					type="text"
					name="theme[variation]"
					className="large-text"
				/>
			</label>
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
                    <input type="checkbox" name={tag.value} value={tag.value} />
			        <label>{tag.label}</label>
                </div>
            }) }</div>

            <div style={{marginTop: '1rem', marginBottom: '0.5rem'}}>Layout:</div>
			<div style={{display: 'grid', gridTemplateColumns: '1fr 1fr'}}>{ layoutTags.map(tag => {
                return <div>
                    <input type="checkbox" name={tag.value} value={tag.value} />
			        <label>{tag.label}</label>
                </div>
            }) }</div>

            <div style={{marginTop: '1rem', marginBottom: '0.5rem'}}>Features:</div>
			<div style={{display: 'grid', gridTemplateColumns: '1fr 1fr'}}>{ featureTags.map(tag => {
                return <div>
                    <input type="checkbox" name={tag.value} value={tag.value} />
			        <label>{tag.label}</label>
                </div>
            }) }</div>
		</div>
	);
}
