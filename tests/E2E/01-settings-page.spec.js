// @ts-check
/**
 * E2E tests for the Disable Comments settings page.
 *
 * Covers: form save (both modes), remove_everywhere toggle UI,
 * XML-RPC / REST API checkboxes, role exclusion, allowed comment types,
 * form dirty detection (save button state).
 */
const { test, expect } = require( '@playwright/test' );
const { SETTINGS_URL } = require( './helpers/wp-urls' );

test.describe( 'Settings page — remove everywhere', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( SETTINGS_URL );
	} );

	test( 'page loads without JS errors', async ( { page } ) => {
		const errors = [];
		page.on( 'pageerror', ( err ) => errors.push( err.message ) );

		await page.goto( SETTINGS_URL );

		expect( errors ).toHaveLength( 0 );
	} );

	test( 'selecting Remove Everywhere and saving shows success', async ( { page } ) => {
		await page.check( '#remove_everywhere_checkbox' );
		await page.click( '#disableCommentSaveSettings button[type=submit]' );

		await expect( page.locator( '.dc-notice-success, .swal2-success, .notice-success' ) )
			.toBeVisible( { timeout: 10_000 } );
	} );

	test( 'saving with Remove Everywhere checked persists selection on reload', async ( { page } ) => {
		await page.check( '#remove_everywhere_checkbox' );
		await page.click( '#disableCommentSaveSettings button[type=submit]' );
		await page.waitForTimeout( 1000 );
		await page.reload();

		await expect( page.locator( '#remove_everywhere_checkbox' ) ).toBeChecked();
	} );

	test( 'post-type checkboxes disabled when remove everywhere selected', async ( { page } ) => {
		await page.check( '#remove_everywhere_checkbox' );

		const postCheckbox = page.locator( 'input[name="disabled_types[]"][value="post"]' );
		await expect( postCheckbox ).toBeDisabled();
	} );
} );

test.describe( 'Settings page — selected post types', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( SETTINGS_URL );
	} );

	test( 'can select specific post types and save', async ( { page } ) => {
		// Uncheck remove_everywhere if checked.
		const removeEverywhere = page.locator( '#remove_everywhere_checkbox' );
		if ( await removeEverywhere.isChecked() ) {
			await removeEverywhere.uncheck();
		}

		await page.check( 'input[name="disabled_types[]"][value="post"]' );
		await page.click( '#disableCommentSaveSettings button[type=submit]' );

		await expect( page.locator( '.dc-notice-success, .swal2-success, .notice-success' ) )
			.toBeVisible( { timeout: 10_000 } );
	} );

	test( 'selected post types persist on reload', async ( { page } ) => {
		const removeEverywhere = page.locator( '#remove_everywhere_checkbox' );
		if ( await removeEverywhere.isChecked() ) {
			await removeEverywhere.uncheck();
		}
		await page.check( 'input[name="disabled_types[]"][value="post"]' );
		await page.click( '#disableCommentSaveSettings button[type=submit]' );
		await page.waitForTimeout( 1000 );
		await page.reload();

		await expect( page.locator( 'input[name="disabled_types[]"][value="post"]' ) ).toBeChecked();
	} );
} );

test.describe( 'Settings page — XML-RPC and REST API', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( SETTINGS_URL );
	} );

	test( 'can enable disable xmlrpc comments', async ( { page } ) => {
		await page.check( '#remove_xmlrpc_comments' );
		await page.click( '#disableCommentSaveSettings button[type=submit]' );

		await expect( page.locator( '.dc-notice-success, .swal2-success, .notice-success' ) )
			.toBeVisible( { timeout: 10_000 } );
	} );

	test( 'can enable disable rest api comments', async ( { page } ) => {
		await page.check( '#remove_rest_API_comments' );
		await page.click( '#disableCommentSaveSettings button[type=submit]' );

		await expect( page.locator( '.dc-notice-success, .swal2-success, .notice-success' ) )
			.toBeVisible( { timeout: 10_000 } );
	} );
} );

test.describe( 'Settings page — form dirty detection', () => {
	test( 'save button is disabled when form is pristine', async ( { page } ) => {
		await page.goto( SETTINGS_URL );

		const saveBtn = page.locator( '#disableCommentSaveSettings button[type=submit]' );
		await expect( saveBtn ).toBeDisabled();
	} );

	test( 'save button enabled after form change', async ( { page } ) => {
		await page.goto( SETTINGS_URL );

		await page.click( '#remove_everywhere_checkbox' );

		const saveBtn = page.locator( '#disableCommentSaveSettings button[type=submit]' );
		await expect( saveBtn ).toBeEnabled();
	} );
} );
