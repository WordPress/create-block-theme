// Toggles the visibility of the forms based on the selected theme type
// eslint-disable-next-line no-unused-vars
function toggleForm( element ) {
	if ( ! element?.value ) return;
	const themeType = element.value;
	hideAllForms();

	switch ( themeType ) {
		case 'export':
		case 'save':
			// Forms should stay hidden
			// Default submit button should be shown
			hideDefaultSubmitButton();
			break;

		case 'child':
		case 'clone':
		case 'blank':
			// Show New Theme form
			document
				.getElementById( 'new_theme_metadata_form' )
				.toggleAttribute( 'hidden', false );

			hideDefaultSubmitButton( true );
			resetThemeTags( element.value );
			validateThemeTags( 'subject' );
			break;

		case 'variation':
			// Show Variation form
			document
				.getElementById( 'new_variation_metadata_form' )
				.toggleAttribute( 'hidden', false );

			hideDefaultSubmitButton();
			break;

		default:
			break;
	}
}

function hideAllForms() {
	const allForms = document.querySelectorAll( '.theme-form' );
	allForms.forEach( ( form ) => {
		form.toggleAttribute( 'hidden', true );
	} );
}

// Hide the default submit button if a long form is being displayed
function hideDefaultSubmitButton( shouldHide ) {
	const defaultSubmitButton = document.querySelector(
		'#col-left input[type="submit"]'
	);

	if ( ! shouldHide ) {
		defaultSubmitButton.style.display = 'block';
	}
	if ( shouldHide ) {
		defaultSubmitButton.style.display = 'none';
	}
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
window.onload = () => {
	activeThemeTags = document.querySelectorAll(
		'.theme-tags input[type="checkbox"]:checked'
	);
};

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
