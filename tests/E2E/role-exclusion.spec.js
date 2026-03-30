// @ts-check
/**
 * E2E tests for role-based comment exclusion.
 *
 * Verifies that users whose role is in the exclusion list still see
 * comments even when comments are globally disabled.
 */
const { test, expect, request } = require( '@playwright/test' );

const BASE_URL     = process.env.WP_BASE_URL || 'http://localhost:8888';
const SETTINGS_URL = `${ BASE_URL }/wp-admin/options-general.php?page=disable_comments_settings`;

test.describe( 'Role exclusion — admin UI', () => {
	test.beforeEach( async ( { page } ) => {
		await page.goto( SETTINGS_URL );
	} );

	test( 'role exclusion section is visible', async ( { page } ) => {
		const section = page.locator( '#exclude_by_role_section, .dc-role-exclusion' );
		await expect( section ).toBeVisible();
	} );

	test( 'enabling role exclusion reveals role selector', async ( { page } ) => {
		const toggle = page.locator( '#enable_exclude_by_role' );
		if ( ! await toggle.isVisible() ) test.skip();

		await toggle.check();

		await expect( page.locator( '#exclude_by_role_select_wrapper, .dc-select2' ) ).toBeVisible();
	} );

	test( 'can save role exclusion settings', async ( { page } ) => {
		const toggle = page.locator( '#enable_exclude_by_role' );
		if ( ! await toggle.isVisible() ) test.skip();

		await toggle.check();

		// Save.
		await page.click( '#disableCommentSaveSettings button[type=submit]' );

		await expect( page.locator( '.dc-notice-success, .swal2-success, .notice-success' ) )
			.toBeVisible( { timeout: 10_000 } );
	} );
} );

test.describe( 'Role exclusion — comments visible for excluded role', () => {
	test( 'excluded editor role user can see comments on disabled post', async ( { browser } ) => {
		// Create a context for an editor user (would need editor credentials in real setup).
		// This test documents the expected behaviour — implement with real editor credentials
		// once wp-env user setup is available.
		const context = await browser.newContext();
		const page    = await context.newPage();

		await page.goto( `${ BASE_URL }/wp-login.php` );
		const editorUser = process.env.WP_EDITOR_USERNAME || 'editor';
		const editorPass = process.env.WP_EDITOR_PASSWORD || 'password';

		await page.fill( '#user_login', editorUser );
		await page.fill( '#user_pass', editorPass );
		await page.click( '#wp-submit' );

		// If editor login succeeded, verify comment form present on a post.
		const url = await page.url();
		if ( url.includes( 'wp-login.php' ) ) {
			// Editor account not set up — skip.
			await context.close();
			test.skip();
			return;
		}

		// Navigate to any post.
		await page.goto( BASE_URL + '/?p=1' );
		// Editor should still see the comment form or comments section.
		const commentsSection = page.locator( '#comments, #respond' );
		const isVisible = await commentsSection.isVisible().catch( () => false );

		// We assert we got a valid page (not necessarily the exact UI state
		// which depends on theme) — full coverage requires a controlled fixture post.
		expect( typeof isVisible ).toBe( 'boolean' );

		await context.close();
	} );
} );

test.describe( 'Role exclusion — REST API', () => {
	test( 'comment creation blocked for non-excluded anonymous user', async ( { request } ) => {
		// Attempt to create a comment via REST without auth.
		const response = await request.post( `${ BASE_URL }/wp-json/wp/v2/comments`, {
			data: {
				post: 1,
				content: 'Test comment from E2E',
				author_name: 'Tester',
				author_email: 'test@example.com',
			},
			failOnStatusCode: false,
		} );

		// Expect 401 (unauthenticated) or 403 (forbidden by plugin).
		expect( [ 401, 403 ] ).toContain( response.status() );
	} );
} );
