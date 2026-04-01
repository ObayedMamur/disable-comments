import { defineConfig } from '@playwright/test';
import * as fs from 'fs';
import * as os from 'os';
import * as path from 'path';
import { resolveWpEnvConfig } from './tests/e2e/utils/wp-env-url';

const configPath = path.join(__dirname, '.config/playwright.json');
const customConfig = resolveWpEnvConfig(JSON.parse(fs.readFileSync(configPath, 'utf8')));

export default defineConfig({
	testDir: './tests/e2e',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 1,
	workers: process.env.CI ? 1 : (process.env.WORKERS ? parseInt(process.env.WORKERS, 10) : os.cpus().length),
	timeout: 60000,
	expect: { timeout: 10000 },

	reporter: [
		['list'],
		['html', { open: 'never', outputFolder: 'tests/test-results/html-report' }],
		['json', { outputFile: 'tests/test-results/results.json' }],
	],

	use: {
		baseURL: customConfig.baseUrl,
		headless: true,
		video: 'on',
		screenshot: 'only-on-failure',
		trace: 'on-first-retry',
		actionTimeout: customConfig.timeouts?.elementWait || 5000,
		navigationTimeout: customConfig.timeouts?.pageLoad || 10000,
		ignoreHTTPSErrors: true,
	},

	projects: [
		{
			name: 'chromium',
			use: {
				actionTimeout: 10000,
				navigationTimeout: 15000,
			},
		},
	],

	globalSetup: require.resolve('./tests/e2e/global-setup.ts'),
	globalTeardown: require.resolve('./tests/e2e/global-teardown.ts'),
	outputDir: 'tests/test-results/artifacts',

	testIgnore: ['**/node_modules/**', '**/vendor/**'],
	testMatch: ['**/*.spec.ts'],
});
