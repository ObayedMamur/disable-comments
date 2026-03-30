// @ts-check
/**
 * E2E tests for multisite site selection (pagination + search).
 *
 * Skipped on single-site — detects multisite by checking whether the
 * sites list element is present on the settings page.
 */
const { test, expect } = require( '@playwright/test' );
const { SETTINGS_URL } = require( './helpers/wp-urls' );

test.describe( 'Multisite — site selection', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( SETTINGS_URL );
	} );

	test( 'sites list panel visible on multisite', async ( { page } ) => {
		const sitesList = page.locator( '#dc-sites-list, .dc-sites-wrapper' );
		const isVisible = await sitesList.isVisible().catch( () => false );

		if ( ! isVisible ) {
			test.skip();
		}

		await expect( sitesList ).toBeVisible();
	} );

	test( 'page size selector changes visible rows', async ( { page } ) => {
		const sitesList = page.locator( '#dc-sites-list, .dc-sites-wrapper' );
		const isVisible = await sitesList.isVisible().catch( () => false );
		if ( ! isVisible ) test.skip();

		const pageSizeSelect = page.locator( '.dc-page-size-select, select[name=pageSize]' );
		await pageSizeSelect.selectOption( '20' );

		// Rows should reload — wait for any loading indicator to disappear.
		await page.waitForTimeout( 2000 );
		const rows = page.locator( '.dc-site-row, #dc-sites-list tr' );
		const count = await rows.count();
		expect( count ).toBeLessThanOrEqual( 20 );
	} );

	test( 'search input filters site list', async ( { page } ) => {
		const sitesList = page.locator( '#dc-sites-list, .dc-sites-wrapper' );
		const isVisible = await sitesList.isVisible().catch( () => false );
		if ( ! isVisible ) test.skip();

		const searchInput = page.locator( '#dc-site-search, input[name=search]' );
		await searchInput.fill( 'nonexistent_site_xyz_abc' );

		// Debounce wait.
		await page.waitForTimeout( 1500 );

		const rows = page.locator( '.dc-site-row, #dc-sites-list tr' );
		const count = await rows.count();
		expect( count ).toBe( 0 );
	} );

	test( 'clearing search restores all sites', async ( { page } ) => {
		const sitesList = page.locator( '#dc-sites-list, .dc-sites-wrapper' );
		const isVisible = await sitesList.isVisible().catch( () => false );
		if ( ! isVisible ) test.skip();

		const searchInput = page.locator( '#dc-site-search, input[name=search]' );
		await searchInput.fill( 'nonexistent_xyz' );
		await page.waitForTimeout( 1500 );

		await searchInput.fill( '' );
		await page.waitForTimeout( 1500 );

		const rows = page.locator( '.dc-site-row, #dc-sites-list tr' );
		const count = await rows.count();
		expect( count ).toBeGreaterThan( 0 );
	} );

	test( 'sitewide settings toggle disables site list', async ( { page } ) => {
		const sitesList = page.locator( '#dc-sites-list, .dc-sites-wrapper' );
		const isVisible = await sitesList.isVisible().catch( () => false );
		if ( ! isVisible ) test.skip();

		const sitewideToggle = page.locator( '#sitewide_settings' );
		const isToggleVisible = await sitewideToggle.isVisible().catch( () => false );
		if ( ! isToggleVisible ) test.skip();

		await sitewideToggle.check();

		// Site checkboxes should become disabled.
		const siteCheckboxes = page.locator( '.dc-site-row input[type=checkbox]' );
		const firstCheckbox  = siteCheckboxes.first();
		if ( await firstCheckbox.count() > 0 ) {
			await expect( firstCheckbox ).toBeDisabled();
		}
	} );
} );
