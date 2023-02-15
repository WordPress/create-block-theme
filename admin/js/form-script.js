// eslint-disable-next-line no-unused-vars
function toggleForm( formID, hide ) {
	if ( formID === 'new_theme_metadata_form' && ! hide ) {
		toggleForm( 'new_variation_metadata_form', true );
	}
	if ( formID === 'new_variation_metadata_form' && ! hide ) {
		toggleForm( 'new_theme_metadata_form', true );
	}
	if (
		! hide &&
		( formID === 'new_theme_metadata_form' ||
			formID === 'new_variation_metadata_form' )
	) {
		validateSubjectThemeTags();
	}
	document.getElementById( formID ).toggleAttribute( 'hidden', hide );
}

// validate theme subject tags, only allow 3 to be selected
function validateSubjectThemeTags() {
	const subjectCheckboxes = document.querySelectorAll(
		'input[name="theme[tags-subject][]"]'
	);
	const maxTags = 3;

	for ( let i = 0; i < subjectCheckboxes.length; i++ ) {
		subjectCheckboxes[ i ].addEventListener( 'change', function () {
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
		} );
	}
}
