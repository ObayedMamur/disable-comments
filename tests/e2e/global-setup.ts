/**
 * Global setup for Playwright tests.
 *
 * Runs before all tests: verifies plugin activation, validates WP login,
 * and collects environment info into test-results/setup-info.json.
 */

import { chromium } from '@playwright/test';
import { execSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';
import { resolveWpEnvConfig } from './utils/wp-env-url';

async function globalSetup() {
	console.log('Starting Playwright global setup');

	const configPath = path.join(__dirname, '../../.config/playwright.json');
	const config = resolveWpEnvConfig(JSON.parse(fs.readFileSync(configPath, 'utf8')));

	// Ensure the plugin is network-active inside wp-env.
	console.log('Checking disable-comments plugin activation...');
	try {
		execSync('npx wp-env run cli wp plugin is-active disable-comments --network', { stdio: 'pipe' });
		console.log('Plugin is already network-active');
	} catch {
		console.log('Plugin is inactive — activating...');
		try {
			execSync('npx wp-env run cli wp plugin activate disable-comments --network', { stdio: 'inherit' });
			console.log('Plugin activated');
		} catch {
			throw new Error('disable-comments plugin could not be network-activated — aborting tests');
		}
	}

	// Ensure test users exist.
	console.log('Ensuring test users...');
	const users = [
		{ login: 'admin', role: '', pass: 'password', update: true },
		{ login: 'subadmin', role: 'administrator', pass: 'password', email: 'subadmin@test.local' },
		{ login: 'subscriber', role: 'subscriber', pass: 'password', email: 'subscriber@test.local' },
	];
	for (const u of users) {
		try {
			if (u.update) {
				execSync('npx wp-env run cli wp user update ' + u.login + ' --user_pass=' + u.pass, { stdio: 'pipe' });
			} else {
				// Check if user exists first
				try {
					execSync('npx wp-env run cli wp user get ' + u.login + ' --field=ID', { stdio: 'pipe' });
					execSync('npx wp-env run cli wp user update ' + u.login + ' --user_pass=' + u.pass, { stdio: 'pipe' });
				} catch {
					execSync(
						'npx wp-env run cli wp user create ' + u.login + ' ' + u.email +
						' --role=' + u.role + ' --user_pass=' + u.pass,
						{ stdio: 'pipe' }
					);
				}
			}
		} catch (e) {
			console.warn('Could not set up user ' + u.login + ': ' + (e as Error).message);
		}
	}

	// Ensure a subsite exists for multisite tests.
	try {
		execSync('npx wp-env run cli wp site list --field=url', { stdio: 'pipe' });
		const sites = execSync('npx wp-env run cli wp site list --field=path', { encoding: 'utf-8' }).trim();
		if (!sites.includes('/subsite/')) {
			execSync('npx wp-env run cli wp site create --slug=subsite --title="Sub Site"', { stdio: 'pipe' });
			console.log('Created subsite');
		}
	} catch {
		// Not multisite or command failed — non-fatal
	}

	// Enable sitewide settings for security tests.
	try {
		execSync('npx wp-env run cli wp site option update disable_comments_sitewide_settings 1', { stdio: 'pipe' });
	} catch {
		// non-fatal
	}

	// Verify WordPress login credentials via browser.
	const headless = !process.env.DEBUG_UI;
	const browser = await chromium.launch({ headless });
	const context = await browser.newContext({ ignoreHTTPSErrors: true });
	const page = await context.newPage();

	try {
		console.log('Verifying WordPress login...');
		await page.goto(config.loginUrl!, { timeout: 15000 });
		await page.fill('#user_login', config.credentials.username);
		await page.fill('#user_pass', config.credentials.password);

		await Promise.all([
			page.waitForURL('**/wp-admin/**', { timeout: 15000 }),
			page.click('#wp-submit'),
		]);

		console.log('WordPress login successful');

		// Create test results directory.
		const testResultsDir = path.join(__dirname, '../test-results');
		if (!fs.existsSync(testResultsDir)) {
			fs.mkdirSync(testResultsDir, { recursive: true });
		}

		const setupInfo = {
			timestamp: new Date().toISOString(),
			wordpressVersion: getCliOutput('npx wp-env run cli wp core version'),
			phpVersion: getCliOutput('npx wp-env run cli php -r "echo PHP_VERSION;"'),
			testEnvironment: config.baseUrl,
			setupStatus: 'success',
		};

		fs.writeFileSync(
			path.join(testResultsDir, 'setup-info.json'),
			JSON.stringify(setupInfo, null, 2)
		);

		console.log('WordPress ' + setupInfo.wordpressVersion + ', PHP ' + setupInfo.phpVersion);
		console.log('Global setup completed');
	} catch (error) {
		console.error('Global setup failed: ' + (error as Error).message);

		try {
			const dumpDir = path.join(__dirname, '../test-results');
			if (!fs.existsSync(dumpDir)) fs.mkdirSync(dumpDir, { recursive: true });
			await page.screenshot({ path: path.join(dumpDir, 'global-setup-failure.png'), fullPage: true });
		} catch {
			// screenshot failed — non-fatal
		}

		throw error;
	} finally {
		await browser.close();
	}
}

function getCliOutput(cmd: string): string {
	try {
		return execSync(cmd, { stdio: 'pipe', encoding: 'utf-8' }).trim().split('\n').pop() || 'Unknown';
	} catch {
		return 'Unknown';
	}
}

export default globalSetup;
