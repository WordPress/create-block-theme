import apiFetch from '@wordpress/api-fetch';

export async function fetchThemeJson() {
	const fetchOptions = {
		path: '/create-block-theme/v1/get-theme-data',
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
		},
	};

	try {
		const response = await apiFetch( fetchOptions );
		const data = JSON.stringify( response, null, 2 );
		return JSON.stringify( JSON.parse( data )?.data, null, 2 );
	} catch ( e ) {
		// @todo: handle error
	}
}
