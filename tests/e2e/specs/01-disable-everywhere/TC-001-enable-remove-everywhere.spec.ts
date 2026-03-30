import { test, expect } from '../../utils/fixtures';
import { wpCli } from '../../utils/wp-cli';
import { SettingsPage } from '../../page-objects/SettingsPage';

test.describe( 'disable-everywhere', () => {
	test( 'TC-001 — Enable "Remove Everywhere" mode and verify global comment disable', async ( {
		page,
		admin,
	} ) => {
		test.info().annotations.push( { type: 'TC', value: 'TC-001' } );

		// ── Prerequisite: ensure a Post and a Page exist with comments open ────
		const postId = wpCli(
			"post create --post_title='TC-001 Test Post' --post_status=publish --comment_status=open --porcelain"
		).trim();
		const postUrl = wpCli( `post get ${ postId } --field=url` ).trim();

		const pageId = wpCli(
			"post create --post_title='TC-001 Test Page' --post_type=page --post_status=publish --comment_status=open --porcelain"
		).trim();
		const pageUrl = wpCli( `post get ${ pageId } --field=url` ).trim();

		const settings = new SettingsPage( page, admin );

		// ── Step 1: Verify initial settings state — Remove Everywhere is OFF ───
		await settings.navigate();
		await expect( settings.removeEverywhereRadio ).not.toBeChecked();

		// ── Step 2: Verify initial frontend state — comment form IS present ────
		await page.goto( postUrl );
		await expect( page.locator( '#respond' ) ).toBeVisible();
		await expect( page.locator( '#comment' ) ).toBeVisible();

		// ── Step 3-4: Select "Remove Everywhere" and save via UI ─────────────
		await settings.navigate();
		await settings.selectRemoveEverywhere();
		await expect( settings.removeEverywhereRadio ).toBeChecked();
		await settings.saveAndWaitForSuccess();

		// ── Step 5: Reload settings and verify persistence ────────────────────
		await page.reload();
		await expect( settings.removeEverywhereRadio ).toBeChecked();

		// ── Step 6: Comment form is now absent on the Post ────────────────────
		await page.goto( postUrl );
		await expect( page.locator( '#respond' ) ).not.toBeAttached();
		await expect(
			page.locator( '#comment-form, form.comment-form' )
		).not.toBeAttached();

		// ── Step 7: Comment form is absent on the Page too ───────────────────
		await page.goto( pageUrl );
		await expect( page.locator( '#respond' ) ).not.toBeAttached();

		// ── Step 8: DB verification via WP-CLI ────────────────────────────────
		const raw = wpCli( 'option get disable_comments_options --format=json' );
		const options = JSON.parse( raw ) as Record< string, unknown >;
		expect( options.remove_everywhere ).toBeTruthy();
	} );
} );
