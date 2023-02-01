function toggleForm( formID, hide ){
	if( formID == 'new_theme_metadata_form' && !hide ) {
		toggleForm( 'new_variation_metadata_form', true );
	}
	if( formID == 'new_variation_metadata_form' && !hide ) {
		toggleForm( 'new_theme_metadata_form', true );
	}
	document.getElementById( formID ).toggleAttribute('hidden', hide);
}
