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

		if ( ! response?.data || 'SUCCESS' !== response?.status ) {
			throw new Error(
				`Failed to fetch theme data: ${
					response?.message || response?.status
				}`
			);
		}

		return JSON.stringify( response?.data, null, 2 );
	} catch ( e ) {
		// @todo: handle error
	}
}

export async function postCreateThemeVariation( name ) {
	return apiFetch( {
		path: '/create-block-theme/v1/create-variation',
		method: 'POST',
		data: { name },
		headers: {
			'Content-Type': 'application/json',
		},
	} );
}
