import { defineConfig, devices } from '@playwright/test';

// Must be set before @wordpress/e2e-test-utils-playwright loads its config,
// which reads WP_BASE_URL from process.env at module initialisation time.
process.env.WP_BASE_URL ??= 'http://localhost:8080';

const WP_BASE_URL = process.env.WP_BASE_URL;

export default defineConfig( {
	testDir: './specs',
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	reporter: [ [ 'html' ], [ 'list' ] ],
	globalSetup: './global-setup.ts',

	use: {
		baseURL: WP_BASE_URL,
		trace: 'on-first-retry',
		video: 'on-first-retry',
		storageState: 'artifacts/storage-states/admin.json',
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
