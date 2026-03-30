import { defineConfig, devices } from '@playwright/test';
import { fileURLToPath } from 'url';
import path from 'path';

// Must be set before @wordpress/e2e-test-utils-playwright loads its config,
// which reads WP_BASE_URL from process.env at module initialisation time.
process.env.WP_BASE_URL ??= 'http://localhost:8080';

// Expose the e2e root dir so utils/wp-cli.ts can find docker-compose.yml
// regardless of what directory the user runs `playwright test` from.
process.env.PW_E2E_DIR = path.dirname( fileURLToPath( import.meta.url ) );

const WP_BASE_URL = process.env.WP_BASE_URL;

export default defineConfig( {
	testDir: './specs',
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	reporter: [ [ 'html' ], [ 'list' ] ],
	globalSetup: './global-setup.ts',
	globalTeardown: './global-teardown.ts',

	use: {
		baseURL: WP_BASE_URL,
		screenshot: 'on',
		trace: 'retain-on-failure',
		video: 'on',
		storageState: 'artifacts/storage-states/admin.json',
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
