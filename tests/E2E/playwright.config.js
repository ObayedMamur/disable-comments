// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './',
	testMatch: '**/*.spec.js',
	timeout: 60_000,
	retries: process.env.CI ? 2 : 0,
	workers: 1, // wp-env is single-instance; run serially

	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8890',
		storageState: 'tests/E2E/.auth/admin.json',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
		trace: 'retain-on-failure',
	},

	projects: [
		{
			name: 'setup',
			testMatch: '**/helpers/global-setup.js',
		},
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
			dependencies: [ 'setup' ],
		},
	],

	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: 'tests/E2E/reports', open: 'never' } ],
	],
} );
