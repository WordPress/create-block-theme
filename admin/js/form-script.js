const { __ } = wp.i18n;

// Toggles the visibility of the forms based on the selected theme type
// eslint-disable-next-line no-unused-vars
function toggleForm( element ) {
	if ( ! element?.value ) return;
	const themeType = element.value;

	toggleInputsOnBlankTheme();
	hideAllForms();

	switch ( themeType ) {
		case 'export':
		case 'save':
			// Forms should stay hidden
			resetThemeName();
			break;

		case 'child':
		case 'clone':
		case 'sibling':
			// Show New Theme form
			document
				.getElementById( 'new_theme_metadata_form' )
				.toggleAttribute( 'hidden', false );

			resetThemeTags( element.value );
			validateThemeTags( 'subject' );
			break;

		case 'blank':
			// Show New Theme form
			// and hide image credits input
			document
				.getElementById( 'new_theme_metadata_form' )
				.toggleAttribute( 'hidden', false );

			toggleInputsOnBlankTheme( true );
			resetThemeTags( element.value );
			validateThemeTags( 'subject' );
			break;

		case 'variation':
			// Show Variation form
			document
				.getElementById( 'new_variation_metadata_form' )
				.toggleAttribute( 'hidden', false );
			break;

		default:
			break;
	}
}

function toggleInputsOnBlankTheme( isHidden = false ) {
	const inputsHiddenOnBlankTheme = document.getElementsByClassName(
		'hide-on-blank-theme'
	);

	for ( let i = 0; i < inputsHiddenOnBlankTheme.length; i++ ) {
		inputsHiddenOnBlankTheme[ i ].toggleAttribute( 'hidden', isHidden );
	}
}

function hideAllForms() {
	const allForms = document.querySelectorAll( '.theme-form' );
	allForms.forEach( ( form ) => {
		form.toggleAttribute( 'hidden', true );
	} );
}

// Handle theme tag validation
function validateThemeTags( tagCategory ) {
	if ( ! tagCategory ) return;
	let checkboxes;

	if ( 'subject' === tagCategory ) {
		checkboxes = 'input[name="theme[tags-subject][]"]';
	}

	// Maximum number of checkboxes that can be selected
	const max = 3;

	// Run validation on form load
	limitCheckboxSelection( checkboxes, max );

	const allCheckboxes = document.querySelectorAll( checkboxes );

	// Run validation on each checkbox change
	if ( allCheckboxes.length > max ) {
		for ( let i = 0; i < allCheckboxes.length; i++ ) {
			allCheckboxes[ i ].addEventListener( 'change', function () {
				limitCheckboxSelection( checkboxes, max );
			} );
		}
	}
}

// Takes a checkbox selector and limits the number of checkboxes that can be selected
function limitCheckboxSelection( checkboxesSelector, max = 0 ) {
	if ( ! checkboxesSelector ) return;

	const checked = document.querySelectorAll(
		`${ checkboxesSelector }:checked`
	);
	const unchecked = document.querySelectorAll(
		`${ checkboxesSelector }:not(:checked)`
	);

	if ( checked.length >= max ) {
		for ( let i = 0; i < unchecked.length; i++ ) {
			unchecked[ i ].setAttribute( 'disabled', true );
		}
	} else {
		for ( let i = 0; i < unchecked.length; i++ ) {
			unchecked[ i ].removeAttribute( 'disabled' );
		}
	}
}

// Store active theme tags when page is loaded
let activeThemeTags = [];
function onWindowLoad() {
	activeThemeTags = document.querySelectorAll(
		'.theme-tags input[type="checkbox"]:checked'
	);
}

window.addEventListener( 'load', onWindowLoad );
window.addEventListener( 'load', prepareThemeNameValidation );

function prepareThemeNameValidation() {
	const themeNameInput = document.getElementById( 'theme-name' );
	if ( themeNameInput ) {
		themeNameInput.addEventListener( 'input', validateThemeNameInput );
	}
}

function slugify( text ) {
	// Removes spaces
	return text.toLowerCase().replace( / /g, '' );
}

function slugifyUnderscores( text ) {
	// Replaces spaces with underscores
	return text.toLowerCase().replace( / /g, '_' );
}

function slugifyDashes( text ) {
	// Replaces spaces with dashes
	return text.toLowerCase().replace( / /g, '-' );
}

function slugifyNoDashes( text ) {
	// Removes spaces, dashes, and underscores
	return text.toLowerCase().replace( / /g, '' ).replace( /[-_]/g, '' );
}

const ERROR_NAME_NOT_AVAILABLE = __(
	'Theme name is not available in the WordPress.org theme directory',
	'create-block-theme'
);
const ERROR_NAME_CONTAINS_THEME = __(
	'Theme name cannot contain the word "theme"',
	'create-block-theme'
);
const ERROR_NAME_CONTAINS_WORDPRESS = __(
	'Theme name cannot contain the word "WordPress"',
	'create-block-theme'
);

function isThemeNameValid( themeName ) {
	// Check the validity of the theme name following the WordPress.org theme directory rules
	// https://meta.svn.wordpress.org/sites/trunk/wordpress.org/public_html/wp-content/plugins/theme-directory/class-wporg-themes-upload.php

	/* eslint-disable @wordpress/no-unused-vars-before-return */
	const lowerCaseName = themeName.toLowerCase();
	const slug = slugify( themeName );
	const slugDashes = slugifyUnderscores( themeName );
	const slugUnderscores = slugifyDashes( themeName );
	const slugNoDashes = slugifyNoDashes( themeName );

	const validityStatus = {
		isValid: true,
		errorMessage: '',
	};

	// Check if the theme contains the word theme
	if ( lowerCaseName.includes( 'theme' ) ) {
		validityStatus.isValid = false;
		validityStatus.errorMessage = ERROR_NAME_CONTAINS_THEME;
		return validityStatus;
	}

	// Check if the theme name contains WordPress
	if ( slugNoDashes.includes( 'wordpress' ) ) {
		validityStatus.isValid = false;
		validityStatus.errorMessage = ERROR_NAME_CONTAINS_WORDPRESS;
		return validityStatus;
	}

	// Check if the theme name is available
	const isNameAvailable = () => {
		// default to empty array if the unavailable theme names are not loaded yet from the API
		const notAvailableSlugs = wpOrgThemeDirectory.themeSlugs || [];

		// Compare the theme name to the list of unavailable theme names using several different slug formats
		return ! notAvailableSlugs.some(
			( s ) =>
				s === slug ||
				s === slugDashes ||
				s === slugUnderscores ||
				slugifyNoDashes( s ) === slugNoDashes
		);
	};

	if ( ! isNameAvailable() ) {
		validityStatus.isValid = false;
		validityStatus.errorMessage = ERROR_NAME_NOT_AVAILABLE;
		return validityStatus;
	}

	return validityStatus;
}

function validateThemeNameInput() {
	const themeName = this?.value;
	if ( ! themeName ) return true;

	// Check if theme name is available
	const validityStatus = isThemeNameValid( themeName );

	if ( ! validityStatus.isValid ) {
		this.setCustomValidity( validityStatus.errorMessage );
		this.reportValidity();
	} else {
		this.setCustomValidity( '' );
	}
}

// Resets all theme tag states (checked, disabled) to default values
function resetThemeTags( themeType ) {
	// Clear all checkboxes
	const allCheckboxes = document.querySelectorAll(
		'.theme-tags input[type="checkbox"]'
	);
	allCheckboxes.forEach( ( checkbox ) => {
		checkbox.checked = false;
		checkbox.removeAttribute( 'disabled' );
	} );

	// Recheck default tags
	const defaultTags = document.querySelectorAll(
		'.theme-tags input[type="checkbox"].default-tag'
	);
	defaultTags.forEach( ( checkbox ) => {
		checkbox.checked = true;
	} );

	if ( 'blank' !== themeType ) {
		// Recheck active theme tags
		if ( ! activeThemeTags ) return;

		activeThemeTags.forEach( ( checkbox ) => {
			checkbox.checked = true;
		} );
	}
}

function resetThemeName() {
	const themeNameInput = document.getElementById( 'theme-name' );
	themeNameInput.value = '';
	themeNameInput.setCustomValidity( '' );
}
