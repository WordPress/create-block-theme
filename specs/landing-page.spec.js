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

		const templates = await requestUtils.rest( {
			path: '/wp/v2/templates?context=edit',
		} );
		const template = templates.find( ( { slug } ) => slug === 'index' );
		expect( template.source ).toBe( 'theme' );
		await page.waitForFunction( () => window.wp?.blocks?.parse );
		const invalidBlocks = await page.evaluate( ( markup ) => {
			const invalid = [];
			const checkBlocks = ( blocks ) => {
				for ( const block of blocks ) {
					if ( ! block.isValid ) {
						invalid.push( block.name );
					}
					checkBlocks( block.innerBlocks );
				}
			};
			checkBlocks( window.wp.blocks.parse( markup ) );
			return invalid;
		}, template.content.raw );
		expect( invalidBlocks ).toEqual( [] );

		const globalStylesId =
			await requestUtils.getCurrentThemeGlobalStylesPostId();
		const globalStylesPath = `/wp/v2/global-styles/${ globalStylesId }`;
		const globalStyles = await requestUtils.rest( {
			path: `${ globalStylesPath }?context=edit`,
		} );
		const content = [ 'normal', 'wide', 'full' ]
			.map( ( alignment ) => {
				const attributes = { anchor: `alignment-${ alignment }` };
				if ( alignment !== 'normal' ) {
					attributes.align = alignment;
				}
				const className =
					alignment === 'normal' ? '' : ` align${ alignment }`;
				return `<!-- wp:group ${ JSON.stringify( attributes ) } -->
<div id="alignment-${ alignment }" class="wp-block-group${ className }"><!-- wp:paragraph -->
<p>${ alignment } content</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->`;
			} )
			.join( '\n' );
		const testPage = await requestUtils.rest( {
			path: '/wp/v2/pages',
			method: 'POST',
			data: {
				title: 'Blank theme alignments',
				status: 'publish',
				content,
			},
		} );

		let testPost;
		try {
			testPost = await requestUtils.rest( {
				path: '/wp/v2/posts',
				method: 'POST',
				data: {
					title: 'Blank theme post alignments',
					status: 'publish',
					content,
				},
			} );
			for ( const padding of [ 0, 32 ] ) {
				if ( padding ) {
					await requestUtils.rest( {
						path: globalStylesPath,
						method: 'POST',
						data: {
							styles: {
								...globalStyles.styles,
								spacing: {
									...globalStyles.styles.spacing,
									padding: {
										top: '0px',
										bottom: '0px',
										left: `${ padding }px`,
										right: `${ padding }px`,
									},
								},
							},
						},
					} );
				}
				for ( const width of [ 1440, 390 ] ) {
					for ( const link of [
						testPage.link,
						testPost.link,
						'/?post_type=post',
					] ) {
						await page.setViewportSize( { width, height: 900 } );
						await page.goto( link );
						for ( const [ alignment, expectedWidth ] of [
							[ 'normal', Math.min( 620, width - padding * 2 ) ],
							[ 'wide', Math.min( 1000, width - padding * 2 ) ],
							[ 'full', width ],
						] ) {
							await expect
								.poll( () =>
									page
										.locator( `#alignment-${ alignment }` )
										.evaluate(
											( element ) =>
												element.getBoundingClientRect()
													.width
										)
								)
								.toBeCloseTo( expectedWidth, 0 );
						}
						await expect
							.poll( () =>
								page
									.locator(
										'.wp-block-post:has(#alignment-full) .wp-block-post-title'
									)
									.evaluate(
										( element ) =>
											element.getBoundingClientRect()
												.width
									)
							)
							.toBeCloseTo(
								Math.min( 620, width - padding * 2 ),
								0
							);
						await expect
							.poll( () =>
								page.evaluate(
									() =>
										document.documentElement.scrollWidth <=
										document.documentElement.clientWidth
								)
							)
							.toBe( true );
					}
				}
			}
		} finally {
			await Promise.all( [
				requestUtils.rest( {
					path: globalStylesPath,
					method: 'POST',
					data: { styles: globalStyles.styles },
				} ),
				requestUtils.rest( {
					path: `/wp/v2/pages/${ testPage.id }?force=true`,
					method: 'DELETE',
				} ),
				...( testPost
					? [
							requestUtils.rest( {
								path: `/wp/v2/posts/${ testPost.id }?force=true`,
								method: 'DELETE',
							} ),
					  ]
					: [] ),
			] );
		}
	} );
} );
