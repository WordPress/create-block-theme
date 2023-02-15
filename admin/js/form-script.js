// eslint-disable-next-line no-unused-vars
function toggleForm( element ) {
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

			validateSubjectThemeTags();
			clearThemeTags( element.value );
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

// hide all theme forms
function hideAllForms() {
	const allForms = document.querySelectorAll( '.theme-form' );
	allForms.forEach( ( form ) => {
		form.toggleAttribute( 'hidden', true );
	} );
}

// validate theme subject tags, only allow 3 to be selected
function validateSubjectThemeTags() {
	const subjectCheckboxes = document.querySelectorAll(
		'input[name="theme[tags-subject][]"]'
	);
	const maxTags = 3;
	handleCheckboxes();

	for ( let i = 0; i < subjectCheckboxes.length; i++ ) {
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

// store active theme tags when page is loaded
let activeThemeTags = [];
window.onload = () => {
	activeThemeTags = document.querySelectorAll(
		'.theme-tags input[type="checkbox"]:checked'
	);
};

// clear all theme tags for blank theme
function clearThemeTags( themeType ) {
	if ( ! activeThemeTags ) return;

	activeThemeTags.forEach( ( checkbox ) => {
		checkbox.checked = true;
	} );

	if ( 'blank' === themeType ) {
		activeThemeTags.forEach( ( checkbox ) => {
			checkbox.checked = false;
		} );
	}
}
