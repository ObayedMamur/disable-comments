import { test, expect } from '../../utils/fixtures';
import { wpCli } from '../../utils/wp-cli';
import { SettingsPage } from '../../page-objects/SettingsPage';

test.describe( 'disable-everywhere', () => {
	test( 'TC-003 — Comment form is absent on Posts when globally disabled', async ( {
		page,
		admin,
		browser,
	} ) => {
		test.info().annotations.push( { type: 'TC', value: 'TC-003' } );

		// ── Prerequisite: create a Post with comments open ─────────────────────
		const postId = wpCli(
			"post create --post_title='TC-003 Test Post' --post_status=publish --comment_status=open --porcelain"
		).trim();
		const postUrl = wpCli( `post get ${ postId } --field=url` ).trim();

		const settings = new SettingsPage( page, admin );

		// ── Step 1: Verify initial settings state — Remove Everywhere is OFF ───
		await settings.navigate();
		await expect( settings.removeEverywhereRadio ).not.toBeChecked();

		// ── Step 2: Verify initial frontend state — comment form IS visible
		//            (tested as a logged-out visitor, the target audience for this check)
		const guestContext = await browser.newContext();
		const guestPage = await guestContext.newPage();
		await guestPage.goto( postUrl );
		await expect( guestPage.locator( '#respond' ) ).toBeVisible();
		await expect( guestPage.locator( '#comment' ) ).toBeVisible();
		await guestContext.close();

		// ── Step 3: Enable Remove Everywhere via UI ───────────────────────────
		await settings.navigate();
		await settings.selectRemoveEverywhere();
		await settings.saveAndWaitForSuccess();

		// ── Step 4: Verify all comment elements are absent for a logged-out visitor
		const guestContext2 = await browser.newContext();
		const guestPage2 = await guestContext2.newPage();

		await guestPage2.goto( postUrl );

		// #respond must be completely absent from the DOM (not just hidden)
		await expect( guestPage2.locator( '#respond' ) ).not.toBeAttached();

		// comment-form element must not exist
		await expect(
			guestPage2.locator( '#comment-form, form.comment-form' )
		).not.toBeAttached();

		// "Leave a Reply" heading must not exist
		await expect( guestPage2.locator( 'h3#reply-title' ) ).not.toBeAttached();

		// comment-reply.js must not be loaded
		const scriptCount = await guestPage2
			.locator( 'script[src*="comment-reply"]' )
			.count();
		expect( scriptCount ).toBe( 0 );

		// Page source must not contain id="respond"
		const html = await guestPage2.content();
		expect( html ).not.toContain( 'id="respond"' );

		await guestContext2.close();
	} );
} );
