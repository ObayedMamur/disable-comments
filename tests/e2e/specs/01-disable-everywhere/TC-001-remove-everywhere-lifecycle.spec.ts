import { test, expect } from '../../utils/fixtures';
import { wpCli } from '../../utils/wp-cli';
import { SettingsPage } from '../../page-objects/SettingsPage';

test.describe( 'disable-everywhere', () => {
	test( 'TC-001 — Remove Everywhere lifecycle: enable, verify disabled, restore', async ( {
		page,
		admin,
		browser,
		requestUtils,
	} ) => {
		test.info().annotations.push( { type: 'TC', description: 'TC-001' } );

		// ── Setup ──────────────────────────────────────────────────────────────
		//
		// Twenty Twenty-Five's page.html omits the Comments block entirely.
		// Create a DB-based template override so the page frontend renders
		// #respond — otherwise the "absent after disable" check is trivially true
		// and the "present before disable" check always fails.
		//
		// The DB entry takes precedence over the file-based template for the
		// duration of this test; the freshDB fixture restores the DB clean state
		// before the next test.
		await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/templates',
			data: {
				slug: 'page',
				title: 'Page',
				theme: 'twentytwentyfive',
				content:
					'<!-- wp:template-part {"slug":"header"} /-->' +
					'<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->' +
					'<main class="wp-block-group">' +
					'<!-- wp:post-title {"level":1} /-->' +
					'<!-- wp:post-content {"layout":{"type":"constrained"}} /-->' +
					'<!-- wp:post-comments-form /-->' +
					'</main>' +
					'<!-- /wp:group -->' +
					'<!-- wp:template-part {"slug":"footer"} /-->',
				status: 'publish',
			},
		} );

		// Create a published post and a published page, both with comments open.
		const post = await requestUtils.createPost( {
			title: 'TC-001 Test Post',
			status: 'publish',
			comment_status: 'open',
		} );
		const postUrl = post.link;

		// requestUtils.createPage() return type omits `link`; use rest() directly
		// so the full REST response (including `link`) is available.
		const wpPage = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: {
				title: 'TC-001 Test Page',
				status: 'publish',
				comment_status: 'open',
			},
		} );
		const pageUrl = wpPage.link;

		const settings = new SettingsPage( page, admin );

		// ── Phase 1: Verify initial state ──────────────────────────────────────

		// Settings: Remove Everywhere is OFF
		await settings.navigate();
		await expect( settings.removeEverywhereRadio ).not.toBeChecked();

		// Frontend: comment form IS present
		const initialContext = await browser.newContext();
		const initialPage = await initialContext.newPage();
		await initialPage.goto( postUrl );
		await expect( initialPage.locator( '#respond' ) ).toBeVisible();
		await expect( initialPage.locator( '#comment' ) ).toBeVisible();
		await initialPage.goto( pageUrl );
		await expect( initialPage.locator( '#respond' ) ).toBeVisible();
		await initialContext.close();

		// ── Phase 2: Enable Remove Everywhere via UI ───────────────────────────
		await settings.navigate();
		await settings.selectRemoveEverywhere();
		await settings.saveAndWaitForSuccess();

		// Settings persist after reload
		await page.reload();
		await expect( settings.removeEverywhereRadio ).toBeChecked();

		// ── Phase 3: Verify comment form absent ────────────────────────────────
		const disabledContext = await browser.newContext();
		const disabledPage = await disabledContext.newPage();

		// Post: all comment elements must be completely absent from the DOM
		await disabledPage.goto( postUrl );
		await expect( disabledPage.locator( '#respond' ) ).not.toBeAttached();
		await expect(
			disabledPage.locator( '#comment-form, form.comment-form' )
		).not.toBeAttached();
		await expect( disabledPage.locator( 'h3#reply-title' ) ).not.toBeAttached();
		const scriptCount = await disabledPage
			.locator( 'script[src*="comment-reply"]' )
			.count();
		expect( scriptCount ).toBe( 0 );
		const postHtml = await disabledPage.content();
		expect( postHtml ).not.toContain( 'id="respond"' );

		// Page: comment form must also be completely absent
		await disabledPage.goto( pageUrl );
		await expect( disabledPage.locator( '#respond' ) ).not.toBeAttached();
		await expect(
			disabledPage.locator( '#comment-form, form.comment-form' )
		).not.toBeAttached();

		await disabledContext.close();

		// DB: remove_everywhere is active
		const rawEnabled = wpCli( 'option get disable_comments_options --format=json' );
		expect(
			( JSON.parse( rawEnabled ) as Record< string, unknown > ).remove_everywhere
		).toBeTruthy();

		// ── Phase 4: Switch back to "Disable by Post Type" with nothing checked ─
		await settings.navigate();
		await settings.selectDisableByPostType();
		await settings.uncheckAllPostTypes();
		await settings.saveAndWaitForSuccess();

		// Settings persist after reload
		await page.reload();
		await expect( settings.selectedTypesRadio ).toBeChecked();
		const checkedCount = await settings.postTypeCheckboxes
			.and( page.locator( ':checked' ) )
			.count();
		expect( checkedCount ).toBe( 0 );

		// ── Phase 5: Verify comment form is fully restored ─────────────────────
		await page.goto( postUrl );
		await expect( page.locator( '#respond' ) ).toBeVisible();
		await expect( page.locator( 'h3#reply-title' ) ).toBeVisible();
		await expect( page.locator( '#comment' ) ).toBeVisible();
		await expect( page.locator( '#submit' ) ).toBeVisible();
		const formAction = await page
			.locator( '#commentform' )
			.getAttribute( 'action' );
		expect( formAction ).toContain( 'wp-comments-post.php' );

		// DB: remove_everywhere is no longer active
		const rawDisabled = wpCli( 'option get disable_comments_options --format=json' );
		expect(
			( JSON.parse( rawDisabled ) as Record< string, unknown > ).remove_everywhere
		).toBeFalsy();
	} );
} );
