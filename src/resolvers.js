/**
 * WordPress dependencies
 */
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

export async function fetchThemeStyleData() {
	const fetchOptions = {
		path: '/create-block-theme/v1/get-style-data',
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
		},
	};

	try {
		const response = await apiFetch( fetchOptions );
		if ( ! response?.data || 'SUCCESS' !== response?.status ) {
			throw new Error(
				`Failed to fetch style data: ${
					response?.message || response?.status
				}`
			);
		}
		return response?.data;
	} catch ( e ) {
		// @todo: handle error
	}
}

export async function createBlankTheme( theme ) {
	const fetchOptions = {
		path: '/create-block-theme/v1/create-blank',
		method: 'POST',
		data: theme,
		headers: {
			'Content-Type': 'application/json',
		},
	};
	return apiFetch( fetchOptions ).then( ( response ) => {
		if ( 'SUCCESS' !== response?.status ) {
			throw new Error(
				`Failed to create blank theme: ${
					response?.message || response?.status
				}`
			);
		}
		return response;
	} );
}

export async function createClonedTheme( theme ) {
	const fetchOptions = {
		path: '/create-block-theme/v1/clone',
		method: 'POST',
		data: theme,
		headers: {
			'Content-Type': 'application/json',
		},
	};
	return apiFetch( fetchOptions ).then( ( response ) => {
		if ( 'SUCCESS' !== response?.status ) {
			throw new Error(
				`Failed to clone theme: ${
					response?.message || response?.status
				}`
			);
		}
		return response;
	} );
}

export async function createChildTheme( theme ) {
	const fetchOptions = {
		path: '/create-block-theme/v1/create-child',
		method: 'POST',
		data: theme,
		headers: {
			'Content-Type': 'application/json',
		},
	};
	return apiFetch( fetchOptions ).then( ( response ) => {
		if ( 'SUCCESS' !== response?.status ) {
			throw new Error(
				`Failed to create child theme: ${
					response?.message || response?.status
				}`
			);
		}
		return response;
	} );
}

export async function fetchReadmeData() {
	const fetchOptions = {
		path: '/create-block-theme/v1/get-readme-data',
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
		},
	};

	try {
		const response = await apiFetch( fetchOptions );
		if ( ! response?.data || 'SUCCESS' !== response?.status ) {
			throw new Error(
				`Failed to fetch readme data: ${
					response?.message || response?.status
				}`
			);
		}
		return response?.data;
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

export async function postUpdateThemeMetadata( theme ) {
	return apiFetch( {
		path: '/create-block-theme/v1/update',
		method: 'POST',
		data: theme,
		headers: {
			'Content-Type': 'application/json',
		},
	} );
}

export async function downloadExportedTheme() {
	return apiFetch( {
		path: '/create-block-theme/v1/export',
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		parse: false,
	} );
}
