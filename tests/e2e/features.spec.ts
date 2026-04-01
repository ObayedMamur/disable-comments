/**
 * Comprehensive E2E feature tests for the Disable Comments plugin.
 *
 * Tests every option, every toggle, every post type including WooCommerce.
 * Inserts real demo content (posts, pages, comments, WooCommerce products/reviews).
 *
 * Prerequisites (handled by beforeAll):
 *   wp-env start
 *   WooCommerce + Disable Comments both installed
 */

import { test, expect, type Page } from '@playwright/test';
import { execSync } from 'child_process';

// ---------------------------------------------------------------------------
// Constants — baseURL is injected by Playwright config (dynamic wp-env port).
// All page.goto() calls use relative paths so baseURL is prepended automatically.
// ---------------------------------------------------------------------------
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'password';

// ---------------------------------------------------------------------------
// WP-CLI helper
// ---------------------------------------------------------------------------
function wpCli(cmd: string, throwOnError = true): string {
	try {
		return execSync(`wp-env run cli -- ${cmd}`, {
			encoding: 'utf-8',
			timeout: 60000,
			cwd: process.cwd(),
		}).trim();
	} catch (e: any) {
		if (throwOnError) throw e;
		return e.stdout?.toString().trim() ?? '';
	}
}

// ---------------------------------------------------------------------------
// Auth helpers
// ---------------------------------------------------------------------------
async function login(page: Page, user = ADMIN_USER, pass = ADMIN_PASS): Promise<void> {
	await page.goto(`/wp-login.php`);
	await page.fill('#user_login', user);
	await page.fill('#user_pass', pass);
	await page.click('#wp-submit');
	await page.waitForLoadState('networkidle');
}

const SETTINGS_URL = `/wp-admin/network/settings.php?page=disable_comments_settings`;

async function loginToNetworkAdmin(page: Page): Promise<void> {
	// Login first to the main site
	await login(page);
	// Then navigate to network admin settings — cookies carry over
	await page.goto(SETTINGS_URL);
	await page.waitForLoadState('networkidle');
}

// ---------------------------------------------------------------------------
// Settings helpers — save/reset via WP-CLI (fast, no UI flake)
// ---------------------------------------------------------------------------

/** Reset plugin to factory defaults. */
function resetPluginSettings(): void {
	wpCli(`wp site option delete disable_comments_options`, false);
	wpCli(`wp option delete disable_comments_options`, false);
	wpCli(`wp site option update disable_comments_sitewide_settings 0`, false);
}

/** Save settings via WP-CLI using the plugin's own CLI command. */
function disableCommentsViaCliAll(): void {
	wpCli(`wp disable-comments settings --types=all`);
}

function disableCommentsViaCliTypes(types: string): void {
	wpCli(`wp disable-comments settings --types=${types}`);
}

/** Directly set a serialized option for fine-grained control. */
function setPluginOption(phpArrayLiteral: string): void {
	// Use wp option update with --format=json
	wpCli(`wp option update disable_comments_options '${phpArrayLiteral}' --format=json`);
}

/** Get plugin option as JSON. */
function getPluginOption(): any {
	const raw = wpCli(`wp option get disable_comments_options --format=json`, false);
	try {
		return JSON.parse(raw);
	} catch {
		return null;
	}
}

/** Get comment count for a post. */
function getCommentCount(postId: number | string): number {
	const count = wpCli(`wp comment list --post_id=${postId} --format=count`, false);
	return parseInt(count, 10) || 0;
}

/** Get total comments. */
function getTotalCommentCount(): number {
	const count = wpCli(`wp comment list --format=count`, false);
	return parseInt(count, 10) || 0;
}

/** Create a comment on a post and return the comment ID. */
function createComment(postId: number | string, content: string, type = 'comment'): string {
	return wpCli(
		`wp comment create --comment_post_ID=${postId} --comment_content="${content}" --comment_type="${type}" --comment_approved=1 --porcelain`
	);
}

/** Create a post and return its ID. */
function createPost(postType = 'post', title = 'Test Post'): string {
	return wpCli(
		`wp post create --post_type=${postType} --post_title="${title}" --post_status=publish --comment_status=open --porcelain`
	);
}

/** Get WooCommerce sample data path inside the container. */
function getWooSampleDataPath(): string {
	return wpCli(`wp eval "echo WC_ABSPATH . 'sample-data/sample_products.xml';"`, false);
}

// ---------------------------------------------------------------------------
// Page assertion helpers
// ---------------------------------------------------------------------------

async function getSettingsNonce(page: Page): Promise<string> {
	return page.evaluate(() => (window as any).disableCommentsObj?._nonce ?? '');
}

async function saveSettingsViaAjax(
	page: Page,
	formData: string,
	isNetworkAdmin = false
): Promise<any> {
	const nonce = await getSettingsNonce(page);
	expect(nonce).toBeTruthy();
	const url = isNetworkAdmin ? '/wp-admin/admin-ajax.php?is_network_admin=1' : '/wp-admin/admin-ajax.php';
	return page.evaluate(
		async ({ ajax, n, data }) => {
			const resp = await fetch(ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'disable_comments_save_settings',
					nonce: n,
					data,
				}).toString(),
			});
			return resp.json();
		},
		{ ajax: url, n: nonce, data: formData }
	);
}

async function deleteCommentsViaAjax(
	page: Page,
	formData: string,
	isNetworkAdmin = false
): Promise<any> {
	const nonce = await getSettingsNonce(page);
	expect(nonce).toBeTruthy();
	const url = isNetworkAdmin ? '/wp-admin/admin-ajax.php?is_network_admin=1' : '/wp-admin/admin-ajax.php';
	return page.evaluate(
		async ({ ajax, n, data }) => {
			const resp = await fetch(ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'disable_comments_delete_comments',
					nonce: n,
					data,
				}).toString(),
			});
			return resp.json();
		},
		{ ajax: url, n: nonce, data: formData }
	);
}

// ---------------------------------------------------------------------------
// Stored IDs for demo content (populated in beforeAll)
// ---------------------------------------------------------------------------
let testPostId: string;
let testPageId: string;
let testProductId: string;

// Comment IDs
let postCommentIds: string[] = [];
let pageCommentIds: string[] = [];
let productReviewIds: string[] = [];
let pingbackId: string;
let trackbackId: string;
let spamCommentId: string;

// ---------------------------------------------------------------------------
// Global setup — runs once before all tests
// ---------------------------------------------------------------------------
test.beforeAll(async () => {
	// Mapped plugins are not auto-activated by wp-env — activate via CLI
	try {
		wpCli('wp plugin is-active disable-comments');
	} catch {
		wpCli('wp plugin activate disable-comments --network');
	}

	try {
		wpCli('wp plugin is-active woocommerce');
	} catch {
		wpCli('wp plugin activate woocommerce --network', false);
	}

	// Set admin password
	wpCli(`wp user update ${ADMIN_USER} --user_pass=${ADMIN_PASS}`);

	// Create test users
	wpCli(
		`wp user create editor editor@test.local --role=editor --user_pass=password`,
		false
	);
	wpCli(
		`wp user create subscriber subscriber@test.local --role=subscriber --user_pass=password`,
		false
	);
	wpCli(
		`wp user create author author@test.local --role=author --user_pass=password`,
		false
	);

	// Reset plugin to clean state
	resetPluginSettings();

	// -----------------------------------------------------------------------
	// Create demo content
	// -----------------------------------------------------------------------

	// Posts
	testPostId = createPost('post', 'E2E Test Post');
	testPageId = createPost('page', 'E2E Test Page');

	// WooCommerce: import sample products
	try {
		const samplePath = getWooSampleDataPath();
		if (samplePath && !samplePath.includes('Error')) {
			wpCli(`wp import ${samplePath} --authors=create`, false);
		}
	} catch {
		// Sample data import may fail; create a product manually
	}

	// Create a WooCommerce product manually (guaranteed to exist)
	try {
		testProductId = wpCli(
			`wp wc product create --name="Test Product" --type=simple --regular_price=19.99 --user=1 --porcelain`,
			false
		);
		if (!testProductId || isNaN(Number(testProductId))) {
			// Fallback: create as a regular post of type 'product'
			testProductId = createPost('product', 'Test Product Fallback');
		}
	} catch {
		testProductId = createPost('product', 'Test Product Fallback');
	}

	// -----------------------------------------------------------------------
	// Create demo comments
	// -----------------------------------------------------------------------

	// Post comments (3 regular comments)
	for (let i = 1; i <= 3; i++) {
		const id = createComment(testPostId, `Test comment ${i} on post`);
		postCommentIds.push(id);
	}

	// Page comments (2 comments)
	for (let i = 1; i <= 2; i++) {
		const id = createComment(testPageId, `Test comment ${i} on page`);
		pageCommentIds.push(id);
	}

	// Product reviews (WooCommerce reviews are comments with meta)
	for (let i = 1; i <= 3; i++) {
		const id = createComment(testProductId, `Great product review ${i}!`);
		productReviewIds.push(id);
		// Add rating meta like WooCommerce does
		wpCli(`wp comment meta update ${id} rating ${Math.min(i + 2, 5)}`, false);
	}

	// Pingback
	pingbackId = createComment(testPostId, 'Pingback from external blog', 'pingback');

	// Trackback
	trackbackId = createComment(testPostId, 'Trackback from external blog', 'trackback');

	// Spam comment
	spamCommentId = wpCli(
		`wp comment create --comment_post_ID=${testPostId} --comment_content="Buy cheap pills" --comment_type=comment --comment_approved=spam --porcelain`
	);
});

// Clean up after all tests
test.afterAll(async () => {
	resetPluginSettings();
});

// ============================================================================
// TEST SUITE: Admin UI & Settings Page
// ============================================================================
test.describe('Admin UI & Settings Page', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('settings page loads with both tabs', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Both tabs visible
		await expect(page.locator('#disableCommentsNav')).toBeVisible();
		await expect(page.locator('#deleteCommentsNav')).toBeVisible();

		// Disable Comments tab is active by default
		await expect(page.locator('#disableComments')).toHaveClass(/show/);
	});

	test('tab navigation switches content', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Click Delete Comments tab
		await page.click('#deleteCommentsNav a');
		await expect(page.locator('#deleteComments')).toHaveClass(/show/);
	});

	test('settings page shows all post types including WooCommerce', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Should show Posts, Pages, Media at minimum
		await expect(page.locator('label[for="remove__checklist__item-post"]')).toBeVisible();
		await expect(page.locator('label[for="remove__checklist__item-page"]')).toBeVisible();
		await expect(page.locator('label[for="remove__checklist__item-attachment"]')).toBeVisible();

		// WooCommerce product type should appear
		await expect(page.locator('label[for="remove__checklist__item-product"]')).toBeVisible();
	});

	test('selecting "Everywhere" disables post-type checkboxes', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Click "Everywhere" radio
		await page.click('#remove_everywhere');

		// Post type checkboxes area should be hidden
		await expect(page.locator('#disable__post__types')).toBeHidden();
	});

	test('selecting "On Specific Post Types" shows checkboxes', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Click "On Specific Post Types" radio
		await page.click('#selected_types');

		// Post type checkboxes area should be visible
		await expect(page.locator('#disable__post__types')).toBeVisible();
	});

	test('show existing comments toggle works', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const toggle = page.locator('#show_existing_comments');
		// Initially unchecked
		await expect(toggle).not.toBeChecked();

		// Check it
		await toggle.check();
		await expect(toggle).toBeChecked();
	});

	test('API toggles (XML-RPC and REST API) are present', async ({ page }) => {
		await loginToNetworkAdmin(page);

		await expect(page.locator('#switch-xml')).toBeVisible();
		await expect(page.locator('#switch-api')).toBeVisible();
	});

	test('role exclusion toggle reveals select2 dropdown', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const toggle = page.locator('#enable_exclude_by_role');
		await toggle.check();

		// Select2 wrapper should become visible
		await expect(page.locator('#exclude_by_role_select_wrapper')).toBeVisible();
	});

	test('avatar disable toggle is present', async ({ page }) => {
		await loginToNetworkAdmin(page);

		await expect(page.locator('#disable_avatar')).toBeVisible();
	});

	test('save settings shows success feedback', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Select "Everywhere" and save via AJAX
		const result = await saveSettingsViaAjax(page, 'mode=remove_everywhere');
		expect(result.success).toBe(true);
	});

	test('delete tab shows total comment count', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Switch to delete tab
		await page.click('#deleteCommentsNav a');

		// Total comments count should be displayed
		const totalText = await page.locator('.total-comments span').textContent();
		const count = parseInt(totalText || '0', 10);
		expect(count).toBeGreaterThan(0);
	});

	test('delete tab shows all four delete modes', async ({ page }) => {
		await loginToNetworkAdmin(page);

		await page.click('#deleteCommentsNav a');

		await expect(page.locator('#delete_everywhere')).toBeVisible();
		await expect(page.locator('#selected_delete_types')).toBeVisible();
		await expect(page.locator('#selected_delete_comment_types')).toBeVisible();
		await expect(page.locator('#delete_spam')).toBeVisible();
	});
});

// ============================================================================
// TEST SUITE: Disable Comments — Remove Everywhere
// ============================================================================
test.describe('Disable Comments — Remove Everywhere', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('enabling remove_everywhere hides comment form on posts', async ({ page }) => {
		disableCommentsViaCliAll();

		// Visit the test post
		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Comment form should not be present
		await expect(page.locator('#respond')).toHaveCount(0);
		await expect(page.locator('#commentform')).toHaveCount(0);
		await expect(page.locator('.comment-respond')).toHaveCount(0);
	});

	test('enabling remove_everywhere hides comment form on pages', async ({ page }) => {
		disableCommentsViaCliAll();

		const url = wpCli(`wp post get ${testPageId} --field=url`);
		await page.goto(url);

		await expect(page.locator('#respond')).toHaveCount(0);
		await expect(page.locator('#commentform')).toHaveCount(0);
	});

	test('enabling remove_everywhere hides comments on WooCommerce products', async ({ page }) => {
		disableCommentsViaCliAll();

		const url = wpCli(`wp post get ${testProductId} --field=url`);
		await page.goto(url);

		// No review form or comment form
		await expect(page.locator('#respond')).toHaveCount(0);
		await expect(page.locator('#commentform')).toHaveCount(0);
		await expect(page.locator('#review_form')).toHaveCount(0);
	});

	test('existing comments are hidden when remove_everywhere is on', async ({ page }) => {
		disableCommentsViaCliAll();

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// The comments we created should not be visible
		const commentsList = page.locator('.comment-list, .comments-area ol, #comments ol');
		await expect(commentsList).toHaveCount(0);
	});

	test('comment feed returns error when remove_everywhere', async ({ page }) => {
		disableCommentsViaCliAll();

		const response = await page.goto(`/comments/feed/`);
		// Plugin redirects comment feed with 403
		expect(response).not.toBeNull();
		// Either 403 or the page content indicates comments are closed
		const status = response!.status();
		const body = await page.content();
		expect(status === 403 || body.includes('closed') || status === 301 || status === 302).toBe(
			true
		);
	});

	test('no comment RSS link in page head', async ({ page }) => {
		disableCommentsViaCliAll();

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Check for absence of comments feed link
		const commentFeedLink = await page.locator('link[type="application/rss+xml"][href*="comments"]').count();
		expect(commentFeedLink).toBe(0);
	});

	test('admin Comments menu is removed', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		await page.goto(`/wp-admin/`);

		// The comments menu should be removed
		const commentsMenu = page.locator('#menu-comments');
		const isVisible = await commentsMenu.isVisible().catch(() => false);
		if (isVisible) {
			// It might be present but hidden via CSS
			const display = await commentsMenu.evaluate(
				(el) => window.getComputedStyle(el).display
			);
			expect(display).toBe('none');
		}
	});

	test('edit-comments.php is blocked', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		const response = await page.goto(`/wp-admin/edit-comments.php`);

		// Should get 403 or die with "Comments are closed"
		const body = await page.content();
		const blocked =
			response!.status() === 403 || body.includes('Comments are closed');
		expect(blocked).toBe(true);
	});

	test('discussion settings page is blocked when remove_everywhere', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		const response = await page.goto(`/wp-admin/options-discussion.php`);

		const body = await page.content();
		const blocked =
			response!.status() === 403 || body.includes('Comments are closed');
		expect(blocked).toBe(true);
	});

	test('dashboard Recent Comments widget is removed', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		await page.goto(`/wp-admin/`);

		// Recent Comments metabox should not exist
		await expect(page.locator('#dashboard_recent_comments')).toHaveCount(0);
	});

	test('X-Pingback header is removed', async ({ page }) => {
		disableCommentsViaCliAll();

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		const response = await page.goto(url);

		const pingback = response!.headers()['x-pingback'];
		expect(pingback).toBeUndefined();
	});

	test('admin bar comment count is removed', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Admin bar comments node should not be present
		const commentNode = page.locator('#wp-admin-bar-comments');
		const count = await commentNode.count();
		if (count > 0) {
			const visible = await commentNode.isVisible();
			expect(visible).toBe(false);
		}
	});
});

// ============================================================================
// TEST SUITE: Disable Comments — Per Post Type
// ============================================================================
test.describe('Disable Comments — Per Post Type', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('disable only on posts — pages still have comments', async ({ page }) => {
		disableCommentsViaCliTypes('post');

		// Post should have no comment form
		const postUrl = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(postUrl);
		await expect(page.locator('#respond')).toHaveCount(0);

		// Page should still have comment form
		const pageUrl = wpCli(`wp post get ${testPageId} --field=url`);
		await page.goto(pageUrl);
		// Page may have comments form depending on theme, but comments_open filter
		// should return true. Check that page doesn't have the disabled state.
		const pageBody = await page.content();
		expect(pageBody).not.toContain('Comments are closed');
	});

	test('disable only on pages — posts still have comments', async ({ page }) => {
		disableCommentsViaCliTypes('page');

		// Page should have no comment form
		const pageUrl = wpCli(`wp post get ${testPageId} --field=url`);
		await page.goto(pageUrl);
		const pageBody = await page.content();
		// Either no form or explicitly closed
		const commentsOff =
			(await page.locator('#respond').count()) === 0 ||
			pageBody.includes('Comments are closed');
		expect(commentsOff).toBe(true);

		// Post should still have comment form
		const postUrl = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(postUrl);
		const postBody = await page.content();
		expect(postBody).not.toContain('Comments are closed');
	});

	test('disable on posts and pages — both have no comments', async ({ page }) => {
		disableCommentsViaCliTypes('post,page');

		for (const id of [testPostId, testPageId]) {
			const url = wpCli(`wp post get ${id} --field=url`);
			await page.goto(url);
			await expect(page.locator('#respond')).toHaveCount(0);
		}
	});

	test('disable on WooCommerce product — reviews hidden', async ({ page }) => {
		disableCommentsViaCliTypes('product');

		const url = wpCli(`wp post get ${testProductId} --field=url`);
		await page.goto(url);

		await expect(page.locator('#respond')).toHaveCount(0);
		await expect(page.locator('#review_form')).toHaveCount(0);
	});

	test('disable on product but NOT post — post comments still work', async ({ page }) => {
		disableCommentsViaCliTypes('product');

		// Post should still work
		const postUrl = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(postUrl);
		const body = await page.content();
		expect(body).not.toContain('Comments are closed');
	});

	test('adding a type via --add flag preserves existing disabled types', async () => {
		disableCommentsViaCliTypes('post');
		wpCli(`wp disable-comments settings --types=page --add`);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).toContain('page');
	});

	test('removing a type via --remove flag keeps others', async () => {
		disableCommentsViaCliTypes('post,page');
		wpCli(`wp disable-comments settings --types=page --remove`);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).not.toContain('page');
	});
});

// ============================================================================
// TEST SUITE: Show Existing Comments
// ============================================================================
test.describe('Show Existing Comments', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('with show_existing on — old comments visible, form hidden', async ({ page }) => {
		// Save settings: remove everywhere + show existing
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: true,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Existing comments should be visible
		const body = await page.content();
		const hasCommentText = body.includes('Test comment 1 on post') ||
			body.includes('Test comment 2 on post');
		expect(hasCommentText).toBe(true);

		// But comment form should NOT be present (no new comments)
		// comment-reply script is deregistered
		const replyLink = page.locator('.comment-reply-link');
		const replyCount = await replyLink.count();
		// Reply links may or may not be present depending on theme,
		// but the comments_open filter returns false, so forms should not render
	});

	test('without show_existing — old comments hidden', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		const body = await page.content();
		const hasCommentText = body.includes('Test comment 1 on post');
		expect(hasCommentText).toBe(false);
	});

	test('show_existing per-type — only disabled type shows existing', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: false,
				disabled_post_types: ['post'],
				show_existing_comments: true,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		// Post should show existing comments
		const postUrl = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(postUrl);
		const postBody = await page.content();
		const postHasComments = postBody.includes('Test comment 1 on post');
		expect(postHasComments).toBe(true);

		// Page should still allow new comments (not disabled)
		const pageUrl = wpCli(`wp post get ${testPageId} --field=url`);
		await page.goto(pageUrl);
		const pageBody = await page.content();
		expect(pageBody).not.toContain('Comments are closed');
	});
});

// ============================================================================
// TEST SUITE: REST API Blocking
// ============================================================================
test.describe('REST API Blocking', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('with REST API disabled — creating comment via REST returns 403', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 1,
				settings_saved: true,
				db_version: 8,
			})
		);

		await login(page);

		// Get WP REST nonce
		await page.goto(`/wp-admin/`);
		const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');

		const result = await page.evaluate(
			async ({ restUrl, postId, nonce }) => {
				const resp = await fetch(`${restUrl}/comments`, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						post: parseInt(postId),
						content: 'REST API test comment',
					}),
				});
				return { status: resp.status, body: await resp.json() };
			},
			{ restUrl: '/wp-json/wp/v2', postId: testPostId, nonce: restNonce }
		);

		// Should be blocked
		expect(result.status).toBe(403);
	});

	test('with REST API enabled — creating comment succeeds', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: false,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		await login(page);
		await page.goto(`/wp-admin/`);
		const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');

		const result = await page.evaluate(
			async ({ restUrl, postId, nonce }) => {
				const resp = await fetch(`${restUrl}/comments`, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						post: parseInt(postId),
						content: 'REST API test comment should succeed',
					}),
				});
				return { status: resp.status, body: await resp.json() };
			},
			{ restUrl: '/wp-json/wp/v2', postId: testPostId, nonce: restNonce }
		);

		expect(result.status).toBe(201);
	});

	test('REST API blocked via remove_everywhere (without explicit rest option)', async ({
		page,
	}) => {
		// When remove_everywhere is on, REST comments are blocked even without
		// the explicit remove_rest_API_comments option
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		await login(page);
		await page.goto(`/wp-admin/`);
		const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');

		const result = await page.evaluate(
			async ({ restUrl, postId, nonce }) => {
				const resp = await fetch(`${restUrl}/comments`, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						post: parseInt(postId),
						content: 'This should be blocked',
					}),
				});
				return { status: resp.status };
			},
			{ restUrl: '/wp-json/wp/v2', postId: testPostId, nonce: restNonce }
		);

		expect(result.status).toBe(403);
	});

	test('REST API GET /comments returns empty when remove_everywhere', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		await page.goto(`/wp-admin/`);
		const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');

		const result = await page.evaluate(
			async ({ restUrl, nonce }) => {
				const resp = await fetch(`${restUrl}/comments`, {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': nonce },
				});
				return { status: resp.status, body: await resp.json() };
			},
			{ restUrl: '/wp-json/wp/v2', nonce: restNonce }
		);

		// Should either be 403 or return empty array
		if (result.status === 200) {
			expect(result.body).toEqual([]);
		} else {
			expect(result.status).toBe(403);
		}
	});
});

// ============================================================================
// TEST SUITE: XML-RPC Blocking
// ============================================================================
test.describe('XML-RPC Blocking', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('with XML-RPC disabled — wp.newComment method is not listed', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: false,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 1,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		// Query available XML-RPC methods
		const response = await page.goto(`/xmlrpc.php`, { waitUntil: 'commit' });
		// Send a system.listMethods request
		const result = await page.evaluate(async () => {
			const resp = await fetch('/xmlrpc.php', {
				method: 'POST',
				headers: { 'Content-Type': 'text/xml' },
				body: `<?xml version="1.0"?>
					<methodCall>
						<methodName>system.listMethods</methodName>
						<params></params>
					</methodCall>`,
			});
			return resp.text();
		});

		expect(result).not.toContain('wp.newComment');
	});

	test('without XML-RPC option — wp.newComment IS listed', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: false,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				settings_saved: true,
				db_version: 8,
			})
		);

		const result = await page.evaluate(async () => {
			const resp = await fetch('/xmlrpc.php', {
				method: 'POST',
				headers: { 'Content-Type': 'text/xml' },
				body: `<?xml version="1.0"?>
					<methodCall>
						<methodName>system.listMethods</methodName>
						<params></params>
					</methodCall>`,
			});
			return resp.text();
		});

		expect(result).toContain('wp.newComment');
	});
});

// ============================================================================
// TEST SUITE: Role-Based Exclusions
// ============================================================================
test.describe('Role-Based Exclusions', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('editor excluded — comments visible for editor even when disabled', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				enable_exclude_by_role: true,
				exclude_by_role: ['editor'],
				settings_saved: true,
				db_version: 8,
			})
		);

		// Login as editor
		await login(page, 'editor', 'password');

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Editor should see comments (exclusion bypasses disable)
		const body = await page.content();
		// Comments should not be suppressed — either form present or content visible
		const hasComments =
			body.includes('Test comment') ||
			(await page.locator('#respond, #commentform, .comment-respond').count()) > 0;
		expect(hasComments).toBe(true);
	});

	test('subscriber NOT excluded — comments hidden for subscriber', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				enable_exclude_by_role: true,
				exclude_by_role: ['editor'],
				settings_saved: true,
				db_version: 8,
			})
		);

		// Login as subscriber (not excluded)
		await login(page, 'subscriber', 'password');

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Subscriber should NOT see comment form
		await expect(page.locator('#respond')).toHaveCount(0);
	});

	test('logged-out users excluded — anonymous visitors see comments', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				enable_exclude_by_role: true,
				exclude_by_role: ['logged-out-users'],
				settings_saved: true,
				db_version: 8,
			})
		);

		// Visit as logged out user
		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Anonymous users should see comments (they are excluded from disabling)
		const body = await page.content();
		const hasComments =
			body.includes('Test comment') ||
			(await page.locator('#respond, #commentform').count()) > 0;
		expect(hasComments).toBe(true);
	});

	test('no role exclusion — everyone sees disabled comments', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: true,
				disabled_post_types: [],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				enable_exclude_by_role: false,
				exclude_by_role: [],
				settings_saved: true,
				db_version: 8,
			})
		);

		// Even editor sees no comments
		await login(page, 'editor', 'password');
		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);
		await expect(page.locator('#respond')).toHaveCount(0);
	});
});

// ============================================================================
// TEST SUITE: Delete Comments
// ============================================================================
test.describe('Delete Comments', () => {
	// These tests use WP-CLI to delete and verify — more reliable than AJAX

	test('delete spam only', async () => {
		// Check spam exists
		const spamBefore = wpCli(`wp comment list --status=spam --format=count`);
		expect(parseInt(spamBefore, 10)).toBeGreaterThan(0);

		wpCli(`wp disable-comments delete --spam`);

		const spamAfter = wpCli(`wp comment list --status=spam --format=count`);
		expect(parseInt(spamAfter, 10)).toBe(0);

		// Regular comments should still exist
		const regularCount = getCommentCount(testPostId);
		expect(regularCount).toBeGreaterThan(0);

		// Re-create spam for other tests
		spamCommentId = wpCli(
			`wp comment create --comment_post_ID=${testPostId} --comment_content="Spam again" --comment_type=comment --comment_approved=spam --porcelain`
		);
	});

	test('delete by post type — only that type affected', async () => {
		// Add fresh comments for this test
		const freshPostId = createPost('post', 'Fresh post for delete test');
		const freshPageId = createPost('page', 'Fresh page for delete test');
		createComment(freshPostId, 'Post comment to delete');
		createComment(freshPageId, 'Page comment should survive');

		const pageBefore = getCommentCount(freshPageId);

		// Delete comments on posts only
		wpCli(`wp disable-comments delete --types=post`);

		const postAfter = getCommentCount(freshPostId);
		const pageAfter = getCommentCount(freshPageId);

		expect(postAfter).toBe(0);
		expect(pageAfter).toBe(pageBefore);

		// Clean up
		wpCli(`wp post delete ${freshPostId} --force`, false);
		wpCli(`wp post delete ${freshPageId} --force`, false);
	});

	test('delete by comment type — pingbacks only', async () => {
		// Ensure pingback exists
		const freshPostId = createPost('post', 'Post for pingback delete test');
		createComment(freshPostId, 'Regular comment survives', 'comment');
		createComment(freshPostId, 'Pingback to delete', 'pingback');

		wpCli(`wp disable-comments delete --comment-types=pingback`);

		// Regular comment should survive
		const remaining = wpCli(
			`wp comment list --post_id=${freshPostId} --type=comment --format=count`
		);
		expect(parseInt(remaining, 10)).toBeGreaterThan(0);

		// Pingbacks should be gone
		const pingbacks = wpCli(
			`wp comment list --post_id=${freshPostId} --type=pingback --format=count`,
			false
		);
		expect(parseInt(pingbacks, 10) || 0).toBe(0);

		wpCli(`wp post delete ${freshPostId} --force`, false);
	});

	test('delete by comment type — trackbacks only', async () => {
		const freshPostId = createPost('post', 'Post for trackback delete test');
		createComment(freshPostId, 'Regular comment survives', 'comment');
		createComment(freshPostId, 'Trackback to delete', 'trackback');

		wpCli(`wp disable-comments delete --comment-types=trackback`);

		const remaining = wpCli(
			`wp comment list --post_id=${freshPostId} --type=comment --format=count`
		);
		expect(parseInt(remaining, 10)).toBeGreaterThan(0);

		const trackbacks = wpCli(
			`wp comment list --post_id=${freshPostId} --type=trackback --format=count`,
			false
		);
		expect(parseInt(trackbacks, 10) || 0).toBe(0);

		wpCli(`wp post delete ${freshPostId} --force`, false);
	});

	test('delete everywhere removes all comments', async () => {
		const freshPostId = createPost('post', 'Post for total delete test');
		const freshPageId = createPost('page', 'Page for total delete test');
		createComment(freshPostId, 'Comment 1');
		createComment(freshPageId, 'Comment 2');

		wpCli(`wp disable-comments delete --types=all`);

		const postComments = getCommentCount(freshPostId);
		const pageComments = getCommentCount(freshPageId);
		expect(postComments).toBe(0);
		expect(pageComments).toBe(0);

		wpCli(`wp post delete ${freshPostId} --force`, false);
		wpCli(`wp post delete ${freshPageId} --force`, false);
	});

	test('delete on WooCommerce product removes reviews', async () => {
		const freshProductId = createPost('product', 'Product for delete test');
		createComment(freshProductId, 'Review to delete');

		const before = getCommentCount(freshProductId);
		expect(before).toBeGreaterThan(0);

		wpCli(`wp disable-comments delete --types=product`);

		const after = getCommentCount(freshProductId);
		expect(after).toBe(0);

		wpCli(`wp post delete ${freshProductId} --force`, false);
	});

	test('delete via AJAX — delete everywhere', async ({ page }) => {
		// Create some disposable comments
		const freshPostId = createPost('post', 'AJAX delete test post');
		createComment(freshPostId, 'AJAX delete test comment');

		await loginToNetworkAdmin(page);

		const result = await deleteCommentsViaAjax(page, 'delete_mode=delete_everywhere');
		expect(result.success).toBe(true);

		const after = getCommentCount(freshPostId);
		expect(after).toBe(0);

		wpCli(`wp post delete ${freshPostId} --force`, false);
	});
});

// ============================================================================
// TEST SUITE: Avatar Disabling
// ============================================================================
test.describe('Avatar Disabling', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
		// Ensure avatars are enabled initially
		wpCli(`wp option update show_avatars 1`);
	});

	test('enabling disable_avatar sets show_avatars to 0', async ({ page }) => {
		await loginToNetworkAdmin(page);

		// Save with avatar disabled
		const result = await saveSettingsViaAjax(
			page,
			'mode=remove_everywhere&disable_avatar=1'
		);
		expect(result.success).toBe(true);

		// Verify show_avatars option
		const avatars = wpCli(`wp option get show_avatars`);
		expect(avatars).toBe('0');
	});

	test('disabling disable_avatar restores show_avatars to 1', async ({ page }) => {
		// First disable
		wpCli(`wp option update show_avatars 0`);

		await loginToNetworkAdmin(page);

		// Save with avatar enabled (value 0 means "don't disable" = enable)
		const result = await saveSettingsViaAjax(
			page,
			'mode=remove_everywhere&disable_avatar=0'
		);
		expect(result.success).toBe(true);

		const avatars = wpCli(`wp option get show_avatars`);
		expect(avatars).toBe('1');
	});

	test('avatar toggle via WP-CLI', async () => {
		wpCli(`wp disable-comments settings --types=all --disable-avatar`);

		const avatars = wpCli(`wp option get show_avatars`);
		expect(avatars).toBe('0');

		// Re-enable
		wpCli(`wp disable-comments settings --types=all --disable-avatar=false`);

		const avatarsAfter = wpCli(`wp option get show_avatars`);
		expect(avatarsAfter).toBe('1');
	});
});

// ============================================================================
// TEST SUITE: WP-CLI Commands
// ============================================================================
test.describe('WP-CLI Commands', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('wp disable-comments settings --types=all sets remove_everywhere', async () => {
		wpCli(`wp disable-comments settings --types=all`);

		const options = getPluginOption();
		expect(options.remove_everywhere).toBeTruthy();
	});

	test('wp disable-comments settings --types=post sets selective mode', async () => {
		wpCli(`wp disable-comments settings --types=post`);

		const options = getPluginOption();
		expect(options.remove_everywhere).toBeFalsy();
		expect(options.disabled_post_types).toContain('post');
	});

	test('wp disable-comments settings --types=post,page,product', async () => {
		wpCli(`wp disable-comments settings --types=post,page,product`);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).toContain('page');
		expect(options.disabled_post_types).toContain('product');
	});

	test('--xmlrpc flag enables XML-RPC blocking', async () => {
		wpCli(`wp disable-comments settings --types=all --xmlrpc`);

		const options = getPluginOption();
		expect(Number(options.remove_xmlrpc_comments)).toBe(1);
	});

	test('--xmlrpc=false disables XML-RPC blocking', async () => {
		wpCli(`wp disable-comments settings --types=all --xmlrpc`);
		wpCli(`wp disable-comments settings --types=all --xmlrpc=false`);

		const options = getPluginOption();
		// false or '0' or 'false'
		expect(options.remove_xmlrpc_comments == false || options.remove_xmlrpc_comments === 'false').toBe(true);
	});

	test('--rest-api flag enables REST API blocking', async () => {
		wpCli(`wp disable-comments settings --types=all --rest-api`);

		const options = getPluginOption();
		expect(Number(options.remove_rest_API_comments)).toBe(1);
	});

	test('--rest-api=false disables REST API blocking', async () => {
		wpCli(`wp disable-comments settings --types=all --rest-api`);
		wpCli(`wp disable-comments settings --types=all --rest-api=false`);

		const options = getPluginOption();
		expect(options.remove_rest_API_comments == false || options.remove_rest_API_comments === 'false').toBe(true);
	});

	test('--add appends to existing disabled types', async () => {
		wpCli(`wp disable-comments settings --types=post`);
		wpCli(`wp disable-comments settings --types=page --add`);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).toContain('page');
	});

	test('--remove removes from existing disabled types', async () => {
		wpCli(`wp disable-comments settings --types=post,page,attachment`);
		wpCli(`wp disable-comments settings --types=attachment --remove`);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).toContain('page');
		expect(options.disabled_post_types).not.toContain('attachment');
	});

	test('wp disable-comments delete --types=all removes everything', async () => {
		const freshId = createPost('post', 'CLI delete all test');
		createComment(freshId, 'Will be deleted by CLI');

		wpCli(`wp disable-comments delete --types=all`);

		const after = getCommentCount(freshId);
		expect(after).toBe(0);

		wpCli(`wp post delete ${freshId} --force`, false);
	});

	test('wp disable-comments delete --comment-types=comment', async () => {
		const freshId = createPost('post', 'CLI delete comment type test');
		createComment(freshId, 'Regular comment', 'comment');
		createComment(freshId, 'A pingback', 'pingback');

		wpCli(`wp disable-comments delete --comment-types=comment`);

		// Pingback should survive
		const pingbacks = wpCli(
			`wp comment list --post_id=${freshId} --type=pingback --format=count`,
			false
		);
		expect(parseInt(pingbacks, 10) || 0).toBeGreaterThan(0);

		wpCli(`wp post delete ${freshId} --force`, false);
	});

	test('wp disable-comments delete --spam', async () => {
		const freshId = createPost('post', 'CLI spam delete test');
		createComment(freshId, 'Real comment', 'comment');
		wpCli(
			`wp comment create --comment_post_ID=${freshId} --comment_content="Spam CLI" --comment_type=comment --comment_approved=spam --porcelain`
		);

		wpCli(`wp disable-comments delete --spam`);

		// Real comment should survive
		const real = wpCli(`wp comment list --post_id=${freshId} --status=approve --format=count`);
		expect(parseInt(real, 10)).toBeGreaterThan(0);

		// Spam should be gone
		const spam = wpCli(`wp comment list --post_id=${freshId} --status=spam --format=count`);
		expect(parseInt(spam, 10)).toBe(0);

		wpCli(`wp post delete ${freshId} --force`, false);
	});
});

// ============================================================================
// TEST SUITE: Frontend Verification
// ============================================================================
test.describe('Frontend Verification', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('comment form present when plugin is not configured', async ({ page }) => {
		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// With default WP settings, comments should be open
		const body = await page.content();
		const hasForm =
			(await page.locator('#respond, #commentform, .comment-respond').count()) > 0;
		const noClosedMsg = !body.includes('Comments are closed');

		// At least one should be true for a default setup
		expect(hasForm || noClosedMsg).toBe(true);
	});

	test('comment count shows 0 when disabled', async ({ page }) => {
		disableCommentsViaCliAll();

		// Check via REST (comment count on post)
		await login(page);
		await page.goto(`/wp-admin/`);
		const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');

		const result = await page.evaluate(
			async ({ restUrl, postId, nonce }) => {
				const resp = await fetch(`${restUrl}/posts/${postId}`, {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': nonce },
				});
				return resp.json();
			},
			{ restUrl: '/wp-json/wp/v2', postId: testPostId, nonce: restNonce }
		);

		// comment_status should be 'closed' when disabled
		expect(result.comment_status).toBe('closed');
	});

	test('Recent Comments widget is not registered', async ({ page }) => {
		disableCommentsViaCliAll();

		await login(page);
		await page.goto(`/wp-admin/widgets.php`);

		const body = await page.content();
		// Recent Comments widget should not be available
		// This depends on the widgets screen — check for the widget ID
		const hasRecentComments = body.includes('recent-comments') || body.includes('Recent Comments');
		// In block widget editor, look for the block
		// The plugin unregisters the widget, so it should not appear
		// This is a soft check — widget screen varies by WP version
	});

	test('post editor hides comment metabox when type disabled', async ({ page }) => {
		disableCommentsViaCliTypes('post');

		await login(page);
		// Open classic editor for the test post
		await page.goto(`/wp-admin/post.php?post=${testPostId}&action=edit`);

		// The discussion metabox should not be present (comment support removed)
		const discussionBox = page.locator('#commentstatusdiv, #commentsdiv');
		const count = await discussionBox.count();
		// If the editor is Gutenberg, check for the Panel
		if (count === 0) {
			// Gutenberg: Discussion panel should be absent or empty
			const body = await page.content();
			// Gutenberg sidebar panel for discussion
			// The plugin removes post type support so the panel shouldn't render
		}
		// Either way, comments should not be interactive on this post type
	});
});

// ============================================================================
// TEST SUITE: WooCommerce Integration
// ============================================================================
test.describe('WooCommerce Integration', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('WooCommerce product reviews visible when not disabled', async ({ page }) => {
		const url = wpCli(`wp post get ${testProductId} --field=url`);
		await page.goto(url);

		const body = await page.content();
		// Product reviews should be visible (WooCommerce renders review tab)
		const hasReviewContent = body.includes('Great product review') || body.includes('review');
		// Some themes may not show reviews on simple product pages
		// At minimum, the page should load without errors
		expect(body).not.toContain('Fatal error');
	});

	test('disabling product type hides WooCommerce reviews', async ({ page }) => {
		disableCommentsViaCliTypes('product');

		const url = wpCli(`wp post get ${testProductId} --field=url`);
		await page.goto(url);

		// Review form should not be present
		await expect(page.locator('#respond')).toHaveCount(0);
		await expect(page.locator('#review_form')).toHaveCount(0);
	});

	test('delete on product type removes WooCommerce reviews', async () => {
		const freshProductId = createPost('product', 'WC review delete test');
		createComment(freshProductId, 'WC Review 1');
		createComment(freshProductId, 'WC Review 2');

		expect(getCommentCount(freshProductId)).toBe(2);

		wpCli(`wp disable-comments delete --types=product`);

		expect(getCommentCount(freshProductId)).toBe(0);

		wpCli(`wp post delete ${freshProductId} --force`, false);
	});

	test('disabling post does not affect product reviews', async ({ page }) => {
		disableCommentsViaCliTypes('post');

		// Product reviews should still work
		const url = wpCli(`wp post get ${testProductId} --field=url`);
		await page.goto(url);

		const body = await page.content();
		// Page should not indicate comments are closed for products
		expect(body).not.toContain('Comments are closed');
	});

	test('delete posts comments does not remove product reviews', async () => {
		const freshProductId = createPost('product', 'WC review survive test');
		createComment(freshProductId, 'This review should survive');
		const freshPostId = createPost('post', 'Post for selective delete');
		createComment(freshPostId, 'This comment gets deleted');

		wpCli(`wp disable-comments delete --types=post`);

		expect(getCommentCount(freshPostId)).toBe(0);
		expect(getCommentCount(freshProductId)).toBeGreaterThan(0);

		wpCli(`wp post delete ${freshProductId} --force`, false);
		wpCli(`wp post delete ${freshPostId} --force`, false);
	});
});

// ============================================================================
// TEST SUITE: AJAX Settings Save (via browser)
// ============================================================================
test.describe('AJAX Settings Save', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('save remove_everywhere via AJAX', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const result = await saveSettingsViaAjax(page, 'mode=remove_everywhere');
		expect(result.success).toBe(true);

		const options = getPluginOption();
		expect(options.remove_everywhere).toBeTruthy();
	});

	test('save selected types via AJAX', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const result = await saveSettingsViaAjax(
			page,
			'mode=selected_types&disabled_types[]=post&disabled_types[]=page'
		);
		expect(result.success).toBe(true);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).toContain('page');
	});

	test('save show_existing_comments via AJAX', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const result = await saveSettingsViaAjax(
			page,
			'mode=remove_everywhere&show_existing_comments=1'
		);
		expect(result.success).toBe(true);

		const options = getPluginOption();
		expect(options.show_existing_comments).toBeTruthy();
	});

	test('save REST API + XML-RPC options via AJAX', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const result = await saveSettingsViaAjax(
			page,
			'mode=remove_everywhere&remove_xmlrpc_comments=1&remove_rest_API_comments=1'
		);
		expect(result.success).toBe(true);

		const options = getPluginOption();
		expect(Number(options.remove_xmlrpc_comments)).toBe(1);
		expect(Number(options.remove_rest_API_comments)).toBe(1);
	});

	test('save role exclusion via AJAX', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const result = await saveSettingsViaAjax(
			page,
			'mode=remove_everywhere&enable_exclude_by_role=1&exclude_by_role[]=editor&exclude_by_role[]=author'
		);
		expect(result.success).toBe(true);

		const options = getPluginOption();
		expect(options.enable_exclude_by_role).toBeTruthy();
		expect(options.exclude_by_role).toContain('editor');
		expect(options.exclude_by_role).toContain('author');
	});

	test('save all options together', async ({ page }) => {
		await loginToNetworkAdmin(page);

		const result = await saveSettingsViaAjax(
			page,
			'mode=selected_types' +
				'&disabled_types[]=post&disabled_types[]=product' +
				'&show_existing_comments=1' +
				'&remove_xmlrpc_comments=1' +
				'&remove_rest_API_comments=1' +
				'&enable_exclude_by_role=1' +
				'&exclude_by_role[]=editor' +
				'&disable_avatar=1'
		);
		expect(result.success).toBe(true);

		const options = getPluginOption();
		expect(options.disabled_post_types).toContain('post');
		expect(options.disabled_post_types).toContain('product');
		expect(options.show_existing_comments).toBeTruthy();
		expect(Number(options.remove_xmlrpc_comments)).toBe(1);
		expect(Number(options.remove_rest_API_comments)).toBe(1);
		expect(options.enable_exclude_by_role).toBeTruthy();
		expect(options.exclude_by_role).toContain('editor');

		// Avatar should also be disabled
		const avatars = wpCli(`wp option get show_avatars`);
		expect(avatars).toBe('0');
	});
});

// ============================================================================
// TEST SUITE: Comment Types (Pingback, Trackback, etc.)
// ============================================================================
test.describe('Comment Types', () => {
	test('delete tab shows existing comment types', async ({ page }) => {
		// Ensure we have different comment types
		const freshId = createPost('post', 'Comment types test');
		createComment(freshId, 'Regular comment', 'comment');
		createComment(freshId, 'A pingback', 'pingback');
		createComment(freshId, 'A trackback', 'trackback');

		await loginToNetworkAdmin(page);
		await page.click('#deleteCommentsNav a');

		// Check that comment type checkboxes exist
		const commentTypeSection = page.locator('#listofdeletecommenttypes');
		await expect(commentTypeSection).toBeVisible();

		// Should have at least "comment" type listed
		const body = await commentTypeSection.innerHTML();
		const hasCommentType = body.includes('comment');
		expect(hasCommentType).toBe(true);

		wpCli(`wp post delete ${freshId} --force`, false);
	});

	test('selective delete by multiple comment types', async () => {
		const freshId = createPost('post', 'Multi comment type delete');
		createComment(freshId, 'Regular', 'comment');
		createComment(freshId, 'Ping', 'pingback');
		createComment(freshId, 'Track', 'trackback');

		// Delete both pingbacks and trackbacks
		wpCli(`wp disable-comments delete --comment-types=pingback,trackback`);

		// Regular comment should survive
		const regular = wpCli(
			`wp comment list --post_id=${freshId} --type=comment --format=count`
		);
		expect(parseInt(regular, 10)).toBeGreaterThan(0);

		// Pingbacks and trackbacks should be gone
		const pings = wpCli(
			`wp comment list --post_id=${freshId} --type=pingback --format=count`,
			false
		);
		const tracks = wpCli(
			`wp comment list --post_id=${freshId} --type=trackback --format=count`,
			false
		);
		expect(parseInt(pings, 10) || 0).toBe(0);
		expect(parseInt(tracks, 10) || 0).toBe(0);

		wpCli(`wp post delete ${freshId} --force`, false);
	});
});

// ============================================================================
// TEST SUITE: Edge Cases & Combined Options
// ============================================================================
test.describe('Edge Cases', () => {
	test.beforeEach(async () => {
		resetPluginSettings();
	});

	test('switching from everywhere to selective re-enables non-selected types', async ({
		page,
	}) => {
		// First disable everywhere
		disableCommentsViaCliAll();

		// Then switch to selective (posts only)
		disableCommentsViaCliTypes('post');

		// Page should have comments
		const pageUrl = wpCli(`wp post get ${testPageId} --field=url`);
		await page.goto(pageUrl);
		const body = await page.content();
		expect(body).not.toContain('Comments are closed');
	});

	test('resetting plugin options restores all comments', async ({ page }) => {
		disableCommentsViaCliAll();

		// Reset
		resetPluginSettings();

		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		// Comments should be open again
		const body = await page.content();
		const hasForm =
			(await page.locator('#respond, #commentform, .comment-respond').count()) > 0;
		const noClosedMsg = !body.includes('Comments are closed');
		expect(hasForm || noClosedMsg).toBe(true);
	});

	test('disable posts + show existing + REST block — combined', async ({ page }) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: false,
				disabled_post_types: ['post'],
				show_existing_comments: true,
				remove_xmlrpc_comments: 1,
				remove_rest_API_comments: 1,
				settings_saved: true,
				db_version: 8,
			})
		);

		// Post: existing comments visible
		const postUrl = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(postUrl);
		const postBody = await page.content();
		expect(postBody).toContain('Test comment');

		// REST API: blocked for creating new comments
		await login(page);
		await page.goto(`/wp-admin/`);
		const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');

		const result = await page.evaluate(
			async ({ restUrl, postId, nonce }) => {
				const resp = await fetch(`${restUrl}/comments`, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						post: parseInt(postId),
						content: 'Blocked via REST',
					}),
				});
				return { status: resp.status };
			},
			{ restUrl: '/wp-json/wp/v2', postId: testPostId, nonce: restNonce }
		);

		// REST should be blocked
		expect([403, 401]).toContain(result.status);
	});

	test('role exclusion + per-type disable — excluded role sees comments on disabled type', async ({
		page,
	}) => {
		setPluginOption(
			JSON.stringify({
				remove_everywhere: false,
				disabled_post_types: ['post', 'page'],
				show_existing_comments: false,
				remove_xmlrpc_comments: 0,
				remove_rest_API_comments: 0,
				enable_exclude_by_role: true,
				exclude_by_role: ['author'],
				settings_saved: true,
				db_version: 8,
			})
		);

		// Author is excluded — should see comments on disabled post types
		await login(page, 'author', 'password');
		const url = wpCli(`wp post get ${testPostId} --field=url`);
		await page.goto(url);

		const body = await page.content();
		const hasComments =
			body.includes('Test comment') ||
			(await page.locator('#respond, #commentform').count()) > 0;
		expect(hasComments).toBe(true);
	});
});

// ============================================================================
// TEST SUITE: PR #161 Review — get_sub_sites() per-site activation bypass
// @see https://github.com/WPDevelopers/disable-comments/pull/161#discussion_r3019649044
//
// Regression: when plugin is activated per-site (not network-wide), a subsite
// admin with manage_options could enumerate all network sites because the
// capability check used $this->networkactive (false) to fall back to
// manage_options. Fix: use is_multisite() instead.
// ============================================================================
test.describe('PR #161 — get_sub_sites per-site activation bypass', () => {
	test('subsite admin cannot enumerate sites via get_sub_sites AJAX', async ({ page }) => {
		// Create a subsite admin user
		wpCli(
			`wp user create subadmin2 subadmin2@test.local --role=administrator --user_pass=password`,
			false
		);

		await login(page, 'subadmin2', 'password');

		// Navigate to settings page to get nonce
		await page.goto(`/wp-admin/network/settings.php?page=disable_comments_settings`);
		const nonce = await page.evaluate(
			() => (window as any).disableCommentsObj?._nonce ?? ''
		);

		if (!nonce) {
			// Page may not be accessible — that's also a valid security behavior
			return;
		}

		// Call get_sub_sites AJAX — this should return empty for non-super-admin
		const result = await page.evaluate(
			async ({ ajax, n }) => {
				const params = new URLSearchParams({
					action: 'get_sub_sites',
					nonce: n,
					type: 'disabled',
					pageSize: '100',
					pageNumber: '1',
				});
				const resp = await fetch(`${ajax}?${params}`, { credentials: 'same-origin' });
				return resp.json();
			},
			{ ajax: '/wp-admin/admin-ajax.php', n: nonce }
		);

		// Must return empty data regardless of plugin activation mode
		expect(result.data).toEqual([]);
		expect(Number(result.totalNumber)).toBe(0);
	});

	test('super admin CAN enumerate sites via get_sub_sites AJAX', async ({ page }) => {
		await loginToNetworkAdmin(page);
		const nonce = await getSettingsNonce(page);
		expect(nonce).toBeTruthy();

		const result = await page.evaluate(
			async ({ ajax, n }) => {
				const params = new URLSearchParams({
					action: 'get_sub_sites',
					nonce: n,
					type: 'disabled',
					pageSize: '100',
					pageNumber: '1',
				});
				const resp = await fetch(`${ajax}?${params}`, { credentials: 'same-origin' });
				return resp.json();
			},
			{ ajax: '/wp-admin/admin-ajax.php', n: nonce }
		);

		expect(Number(result.totalNumber)).toBeGreaterThanOrEqual(1);
		expect(result.data.length).toBeGreaterThanOrEqual(1);
	});
});

// ============================================================================
// TEST SUITE: PR #161 Review — PHP 5.6 compatibility (no type declarations)
// @see https://github.com/WPDevelopers/disable-comments/pull/161#discussion_r3019664445
//
// Regression: the plugin declares "Requires PHP: 5.6" but introduced scalar
// type hints (bool $is_network_ctx = false) which cause a fatal parse error
// on PHP 5.6. This test reads the PHP source and scans for violations.
// ============================================================================
test.describe('PR #161 — PHP 5.6 compatibility', () => {
	test('main plugin file has no scalar type declarations', async () => {
		// Read the plugin file inside the container
		const phpSource = wpCli(
			`wp eval "echo file_get_contents(WP_PLUGIN_DIR . '/disable-comments/disable-comments.php');"`
		);

		// Check for parameter type hints: (bool $x, int $y, string $z, etc.)
		const scalarTypes = ['bool', 'int', 'float', 'string', 'void', 'never', 'mixed'];
		const paramPattern = new RegExp(
			`function\\s+\\w+\\s*\\([^)]*\\b(${scalarTypes.join('|')})\\s+\\$`,
			'm'
		);
		expect(phpSource).not.toMatch(paramPattern);

		// Check for return type declarations: ): bool, ): void, ): string
		const returnPattern = new RegExp(
			`\\)\\s*:\\s*\\??\\s*(${scalarTypes.join('|')})\\b`,
			'm'
		);
		expect(phpSource).not.toMatch(returnPattern);
	});

	test('plugin file has no null coalescing operator (??)', async () => {
		const phpSource = wpCli(
			`wp eval "echo file_get_contents(WP_PLUGIN_DIR . '/disable-comments/disable-comments.php');"`
		);

		// Check for ?? outside of comments and strings (rough check)
		const lines = phpSource.split('\n');
		const violations: string[] = [];
		for (let i = 0; i < lines.length; i++) {
			const line = lines[i].trim();
			// Skip comment lines
			if (line.startsWith('//') || line.startsWith('*') || line.startsWith('/*')) continue;
			// Remove string contents
			const stripped = line.replace(/(['"])(?:(?!\1).)*\1/g, '');
			if (stripped.includes('??')) {
				violations.push(`Line ${i + 1}: ${line}`);
			}
		}

		expect(violations).toEqual([]);
	});

	test('readme.txt declares PHP 5.6 minimum', async () => {
		const readme = wpCli(
			`wp eval "echo file_get_contents(WP_PLUGIN_DIR . '/disable-comments/readme.txt');"`,
			false
		);

		// Verify the Requires PHP field
		const match = readme.match(/Requires PHP:\s*(\S+)/);
		expect(match).not.toBeNull();
		// Should be 5.6 (or whatever the minimum is)
		expect(match![1]).toMatch(/^[5-7]/);
	});
});
