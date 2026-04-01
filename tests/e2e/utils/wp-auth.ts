/**
 * WordPress Authentication Utility for Playwright Tests.
 *
 * Reusable WP login helper for both single-site and multisite environments.
 */

import { expect, type Page } from '@playwright/test';

/**
 * Log in to WordPress admin dashboard.
 */
export async function wpLogin(
	page: Page,
	user: string = 'admin',
	pass: string = 'password',
): Promise<void> {
	await page.goto('/wp-login.php');

	// Only fill if we're actually on the login page.
	if (page.url().includes('wp-login.php') || await page.$('#user_login')) {
		await page.fill('#user_login', user);
		await page.fill('#user_pass', pass);
		await page.click('#wp-submit');
		await page.waitForLoadState('networkidle');
	}

	await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: 10000 });
}
