// eslint-disable-next-line no-unused-vars
function toggleForm( element ) {
	// Toggles the visibility of the forms based on the selected theme type

	if ( ! element?.value ) return;
	const themeType = element.value;
	hideAllForms();

	switch ( themeType ) {
		case 'export':
		case 'save':
			hideAllForms();
			break;

		case 'child':
		case 'clone':
		case 'blank':
			document
				.getElementById( 'new_theme_metadata_form' )
				.toggleAttribute( 'hidden', false );

			clearThemeTags( element.value );
			validateSubjectThemeTags( element.value );
			break;

		case 'variation':
			document
				.getElementById( 'new_variation_metadata_form' )
				.toggleAttribute( 'hidden', false );
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

function validateSubjectThemeTags( themeType ) {
	// Validates theme subject tags, allows only 3 to be selected

	const subjectCheckboxes = document.querySelectorAll(
		'input[name="theme[tags-subject][]"]'
	);
	const maxTags = 3;
	handleCheckboxes();

	for ( let i = 0; i < subjectCheckboxes.length; i++ ) {
		if ( 'blank' === themeType ) {
			subjectCheckboxes[ i ].checked = false;
			subjectCheckboxes[ i ].removeAttribute( 'disabled' );
		}

		subjectCheckboxes[ i ].addEventListener( 'change', function () {
			handleCheckboxes();
		} );
	}

	function handleCheckboxes() {
		const checked = document.querySelectorAll(
			'input[name="theme[tags-subject][]"]:checked'
		);
		const unchecked = document.querySelectorAll(
			'input[name="theme[tags-subject][]"]:not(:checked)'
		);

		if ( checked.length >= maxTags ) {
			for ( let j = 0; j < unchecked.length; j++ ) {
				unchecked[ j ].setAttribute( 'disabled', true );
			}
		}

		if ( checked.length < maxTags ) {
			for ( let j = 0; j < unchecked.length; j++ ) {
				unchecked[ j ].removeAttribute( 'disabled' );
			}
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

function clearThemeTags( themeType ) {
	// Clears all theme tag states (checked, disabled)

	if ( ! activeThemeTags ) return;

	// Clear all checkboxes
	const allCheckboxes = document.querySelectorAll(
		'.theme-tags input[type="checkbox"]'
	);
	allCheckboxes.forEach( ( checkbox ) => {
		checkbox.checked = false;
		checkbox.removeAttribute( 'disabled' );
	} );

	if ( 'blank' !== themeType ) {
		// Recheck active theme tags
		activeThemeTags.forEach( ( checkbox ) => {
			checkbox.checked = true;
		} );
	}
}
