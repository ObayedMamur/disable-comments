import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Smoke', () => {
	test( 'WordPress site is accessible', async ( { page } ) => {
		await page.goto( '/' );
		await expect( page ).toHaveTitle( /Disable Comments Test/ );
	} );

	test( 'Admin can access dashboard', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'index.php' );
		await expect( page ).toHaveTitle( /Dashboard/ );
	} );

	test( 'Disable Comments plugin is active', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'plugins.php' );
		const pluginRow = page.locator( 'tr[data-slug="disable-comments"]' );
		await expect( pluginRow ).toBeVisible();
		await expect( pluginRow.locator( '.plugin-title strong' ) ).toContainText(
			'Disable Comments'
		);
		// Confirm it's active (row has the 'active' class)
		await expect( pluginRow ).toHaveClass( /active/ );
	} );
} );
