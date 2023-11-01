import apiFetch from '@wordpress/api-fetch';

async function loadUnavailableThemeNames() {
	const requestOptions = {
		path: '/create-block-theme/v1/wp-org-theme-names',
	};

	try {
		const request = await apiFetch( requestOptions );
		window.wpOrgThemeDirectory.themeSlugs = request.names;
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( error );
	}
}

window.addEventListener( 'load', loadUnavailableThemeNames );
