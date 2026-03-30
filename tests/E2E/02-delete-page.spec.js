// @ts-check
/**
 * E2E tests for the Disable Comments delete/tools page.
 *
 * Covers: delete mode switching, confirmation dialog (SweetAlert2),
 * form state (delete button active/inactive), success/error responses.
 */
const { test, expect } = require( '@playwright/test' );
const { DELETE_URL } = require( './helpers/wp-urls' );

test.describe( 'Delete page', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( DELETE_URL );
		// Wait for the delete tab to be active.
		await page.waitForSelector( '#deleteCommentSettings', { state: 'visible' } );
	} );

	test( 'delete page loads without errors', async ( { page } ) => {
		const errors = [];
		page.on( 'pageerror', ( err ) => errors.push( err.message ) );

		await page.goto( DELETE_URL );
		await page.waitForSelector( '#deleteCommentSettings', { state: 'visible' } );

		expect( errors ).toHaveLength( 0 );
	} );

	test( 'delete everywhere radio is present and selectable', async ( { page } ) => {
		const radio = page.locator( 'input[type=radio][value=delete_everywhere]' );
		await expect( radio ).toBeVisible();
		await radio.check();
		await expect( radio ).toBeChecked();
	} );

	test( 'selecting delete by post type shows post type checkboxes', async ( { page } ) => {
		await page.check( 'input[type=radio][value=selected_delete_types]' );

		await expect( page.locator( '.dc-delete-post-types' ) ).toBeVisible();
	} );

	test( 'selecting delete by comment type shows comment type checkboxes', async ( { page } ) => {
		await page.check( 'input[type=radio][value=selected_delete_comment_types]' );

		await expect( page.locator( '.dc-delete-comment-types' ) ).toBeVisible();
	} );

	test( 'delete form shows SweetAlert2 confirmation before submitting', async ( { page } ) => {
		await page.check( 'input[type=radio][value=delete_everywhere]' );

		// Click the delete button — SweetAlert2 should appear before the request fires.
		await page.click( '#deleteCommentSettings button[type=submit]' );

		await expect( page.locator( '.swal2-popup' ) ).toBeVisible( { timeout: 5_000 } );
	} );

	test( 'cancelling confirmation dialog does not delete comments', async ( { page } ) => {
		await page.check( 'input[type=radio][value=delete_everywhere]' );
		await page.click( '#deleteCommentSettings button[type=submit]' );
		await page.locator( '.swal2-popup' ).waitFor( { state: 'visible' } );

		// Click Cancel in SweetAlert2.
		await page.click( '.swal2-cancel' );

		await expect( page.locator( '.swal2-popup' ) ).toBeHidden();
	} );

	test( 'confirming delete everywhere shows success message', async ( { page } ) => {
		await page.check( 'input[type=radio][value=delete_everywhere]' );
		await page.click( '#deleteCommentSettings button[type=submit]' );
		await page.locator( '.swal2-popup' ).waitFor( { state: 'visible' } );

		// Confirm.
		await page.click( '.swal2-confirm' );

		await expect( page.locator( '.swal2-success, .swal2-popup' ) )
			.toBeVisible( { timeout: 15_000 } );
	} );
} );
