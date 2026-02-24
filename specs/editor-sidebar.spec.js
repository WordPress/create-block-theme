/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Create Block Theme — Site Editor Sidebar', () => {
	test( 'site editor loads', async ( { admin } ) => {
		await admin.visitSiteEditor();
	} );

	test( 'plugin sidebar button is present in the editor toolbar', async ( {
		admin,
		page,
	} ) => {
		await admin.visitSiteEditor( { canvas: 'edit' } );
		await expect(
			page.getByRole( 'button', { name: 'Create Block Theme' } )
		).toBeVisible();
	} );
} );
