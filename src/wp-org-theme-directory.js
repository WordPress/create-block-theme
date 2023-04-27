import apiFetch from '@wordpress/api-fetch';

async function loadUnavailableThemeNames() {
	const requestOptions = {
		path: '/create-block-theme/v1/wp-org-theme-names',
	};
	const request = await apiFetch( requestOptions );
	wpOrgThemeDirectory.themeSlugs = request.names;
}

window.addEventListener( 'load', loadUnavailableThemeNames );
