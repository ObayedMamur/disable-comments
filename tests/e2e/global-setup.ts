import type { FullConfig } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

async function globalSetup( config: FullConfig ) {
	const { baseURL } = config.projects[ 0 ].use;

	const requestUtils = await RequestUtils.setup( {
		user: {
			username: 'admin',
			password: 'password',
		},
		baseURL: baseURL ?? 'http://localhost:8080',
		storageStatePath: 'artifacts/storage-states/admin.json',
	} );

	await requestUtils.setupRest();
}

export default globalSetup;
