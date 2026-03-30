import type { FullConfig } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { wpCli } from './utils/wp-cli';

async function globalSetup( config: FullConfig ) {
	const { baseURL } = config.projects[ 0 ].use;

	// Authenticate admin and persist session cookies to storage state.
	const requestUtils = await RequestUtils.setup( {
		user: {
			username: 'admin',
			password: 'password',
		},
		baseURL: baseURL ?? 'http://localhost:8080',
		storageStatePath: 'artifacts/storage-states/admin.json',
	} );

	await requestUtils.setupRest();

	// Take a full DB snapshot *after* auth setup so each test can restore to
	// a clean state that already contains valid session tokens.
	console.log( '📦 Taking DB snapshot for per-test restore...' );
	wpCli( 'db export /var/www/html/e2e-backup.sql --quiet' );
	console.log( '✅ DB snapshot ready.' );
}

export default globalSetup;
