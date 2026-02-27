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

		if (
			themes.some( ( { stylesheet } ) => stylesheet === E2E_THEME_SLUG )
		) {
			execSync(
				`npx wp-env run tests-cli wp theme delete ${ E2E_THEME_SLUG }`,
				{ stdio: 'ignore' }
			);
		}
	} );

	test.afterAll( async ( { requestUtils } ) => {
		if ( originalThemeSlug ) {
			await requestUtils.activateTheme( originalThemeSlug );
		}

		const themes = await requestUtils.rest( { path: '/wp/v2/themes' } );
		if (
			themes.some( ( { stylesheet } ) => stylesheet === E2E_THEME_SLUG )
		) {
			execSync(
				`npx wp-env run tests-cli wp theme delete ${ E2E_THEME_SLUG }`,
				{ stdio: 'ignore' }
			);
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

	test( 'clicking export downloads a zip file', async ( { admin, page } ) => {
		await admin.visitAdminPage(
			'themes.php',
			'page=create-block-theme-landing'
		);
		const [ download ] = await Promise.all( [
			page.waitForEvent( 'download' ),
			page.getByRole( 'button', { name: /Export/ } ).click(),
		] );
		expect( download.suggestedFilename() ).toMatch( /\.zip$/ );
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

	test( 'create blank theme end-to-end', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
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

		await page.waitForURL( /site-editor/, { waitUntil: 'commit' } );

		const themes = await requestUtils.rest( { path: '/wp/v2/themes' } );
		const active = themes.find( ( { status } ) => status === 'active' );
		expect( active?.stylesheet ).toBe( E2E_THEME_SLUG );
	} );
} );
