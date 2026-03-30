import { test as base, expect } from '@wordpress/e2e-test-utils-playwright';
import { wpCli } from './wp-cli';

/**
 * Extended Playwright test with an auto-running `freshDB` fixture.
 *
 * Before every test, the DB is restored from the snapshot taken during
 * global-setup. This guarantees a clean, deterministic starting state
 * regardless of what a previous test may have changed.
 */
export const test = base.extend< { freshDB: void } >( {
	freshDB: [
		async ( {}, use ) => {
			wpCli( 'db import /var/www/html/e2e-backup.sql --quiet' );
			await use();
		},
		{ auto: true, scope: 'test' },
	],
} );

export { expect };
