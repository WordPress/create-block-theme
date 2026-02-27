/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Create Block Theme — Site Editor Sidebar', () => {
	test( 'plugin sidebar button is present in the editor toolbar', async ( {
		admin,
		page,
	} ) => {
		await admin.visitSiteEditor( { canvas: 'edit' } );
		await expect(
			page.getByRole( 'button', { name: 'Create Block Theme' } )
		).toBeVisible();
	} );

	test( 'clicking the toolbar button opens the plugin sidebar', async ( {
		admin,
		page,
	} ) => {
		await admin.visitSiteEditor( { canvas: 'edit' } );
		await page
			.getByRole( 'button', { name: 'Create Block Theme' } )
			.click();
		await expect(
			page.getByRole( 'button', { name: 'Save Changes to Theme' } )
		).toBeVisible();
	} );
} );
