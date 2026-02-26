/**
 * External dependencies
 */
const { execSync } = require( 'child_process' );

/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const E2E_THEME_SLUG = 'e2e-test-theme';

test.describe( 'Create Block Theme — Admin Landing Page', () => {
	let originalThemeSlug;

	test.beforeAll( async ( { requestUtils } ) => {
		const themes = await requestUtils.rest( { path: '/wp/v2/themes' } );
		const active = themes.find( ( { status } ) => status === 'active' );
		originalThemeSlug = active?.stylesheet;

		try {
			execSync(
				`npx wp-env run cli wp theme delete ${ E2E_THEME_SLUG }`,
				{ stdio: 'ignore' }
			);
		} catch {
			// Theme doesn't exist, which is expected on a clean environment.
		}
	} );

	test.afterAll( async ( { requestUtils } ) => {
		if ( originalThemeSlug ) {
			await requestUtils.activateTheme( originalThemeSlug );
		}
		try {
			execSync(
				`npx wp-env run cli wp theme delete ${ E2E_THEME_SLUG }`,
				{ stdio: 'ignore' }
			);
		} catch {
			// Theme may not exist if the test was skipped or failed early.
		}
	} );

	test( 'page loads and shows main heading', async ( { admin, page } ) => {
		await admin.visitAdminPage(
			'themes.php',
			'page=create-block-theme-landing'
		);
		await expect(
			page.getByRole( 'heading', { name: 'What would you like to do?' } )
		).toBeVisible();
	} );

	test( 'export button is visible', async ( { admin, page } ) => {
		await admin.visitAdminPage(
			'themes.php',
			'page=create-block-theme-landing'
		);
		await expect(
			page.getByRole( 'button', { name: /Export/ } )
		).toBeVisible();
	} );

	test( 'create blank theme modal opens with name field', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'themes.php',
			'page=create-block-theme-landing'
		);
		await page
			.getByRole( 'button', { name: /Create a new Blank Theme/i } )
			.click();
		await expect( page.getByLabel( /Theme name/i ) ).toBeVisible();
	} );

	test( 'create blank theme end-to-end', async ( { admin, page } ) => {
		await admin.visitAdminPage(
			'themes.php',
			'page=create-block-theme-landing'
		);

		// Accept the alert dialog that fires on successful theme creation.
		page.once( 'dialog', ( dialog ) => dialog.accept() );

		await page
			.getByRole( 'button', { name: /Create a new Blank Theme/i } )
			.click();
		await page.getByLabel( /Theme name/i ).fill( 'E2E Test Theme' );
		await page
			.getByRole( 'button', {
				name: 'Create and Activate Blank Theme',
			} )
			.click();

		await page.waitForURL( /site-editor/ );
	} );
} );
