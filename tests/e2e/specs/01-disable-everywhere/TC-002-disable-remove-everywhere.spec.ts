import { test, expect } from '../../utils/fixtures';
import { wpCli } from '../../utils/wp-cli';
import { SettingsPage } from '../../page-objects/SettingsPage';

test.describe( 'disable-everywhere', () => {
	test( 'TC-002 — Disable "Remove Everywhere" mode restores comments', async ( {
		page,
		admin,
	} ) => {
		test.info().annotations.push( { type: 'TC', value: 'TC-002' } );

		// ── Prerequisite: create a Post with comments open ─────────────────────
		const postId = wpCli(
			"post create --post_title='TC-002 Test Post' --post_status=publish --comment_status=open --porcelain"
		).trim();
		const postUrl = wpCli( `post get ${ postId } --field=url` ).trim();

		const settings = new SettingsPage( page, admin );

		// ── Step 1: Verify initial settings state — Remove Everywhere is OFF ───
		await settings.navigate();
		await expect( settings.removeEverywhereRadio ).not.toBeChecked();

		// ── Step 2: Verify initial frontend state — comment form IS present ────
		await page.goto( postUrl );
		await expect( page.locator( '#respond' ) ).toBeVisible();
		await expect( page.locator( 'h3#reply-title' ) ).toBeVisible();
		await expect( page.locator( '#comment' ) ).toBeVisible();

		// ── Step 3: Enable Remove Everywhere via UI ───────────────────────────
		await settings.navigate();
		await settings.selectRemoveEverywhere();
		await settings.saveAndWaitForSuccess();

		// ── Step 4: Verify comment form is now absent (Remove Everywhere active)
		await page.goto( postUrl );
		await expect( page.locator( '#respond' ) ).not.toBeAttached();

		// ── Step 5: Switch back to "Disable by Post Type" with nothing checked ─
		await settings.navigate();
		await expect( settings.removeEverywhereRadio ).toBeChecked();
		await settings.selectDisableByPostType();
		await settings.uncheckAllPostTypes();
		await settings.saveAndWaitForSuccess();

		// ── Step 6: Reload and verify the new settings state persisted ────────
		await page.reload();
		await expect( settings.selectedTypesRadio ).toBeChecked();
		const count = await settings.postTypeCheckboxes
			.and( page.locator( ':checked' ) )
			.count();
		expect( count ).toBe( 0 );

		// ── Step 7: Comment form is restored and fully functional ─────────────
		await page.goto( postUrl );
		await expect( page.locator( '#respond' ) ).toBeVisible();
		await expect( page.locator( 'h3#reply-title' ) ).toBeVisible();
		await expect( page.locator( '#comment' ) ).toBeVisible();
		await expect( page.locator( '#submit' ) ).toBeVisible();
		const formAction = await page
			.locator( '#commentform' )
			.getAttribute( 'action' );
		expect( formAction ).toContain( 'wp-comments-post.php' );

		// ── Step 8: DB verification — remove_everywhere is no longer truthy ───
		const raw = wpCli( 'option get disable_comments_options --format=json' );
		const options = JSON.parse( raw ) as Record< string, unknown >;
		expect( options.remove_everywhere ).toBeFalsy();
	} );
} );
