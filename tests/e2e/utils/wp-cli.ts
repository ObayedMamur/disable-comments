import { execSync } from 'child_process';
import path from 'path';

/**
 * Resolve the tests/e2e/ directory.
 * PW_E2E_DIR is set by playwright.config.ts so this works regardless of CWD.
 */
const E2E_DIR: string = process.env.PW_E2E_DIR ?? path.resolve( process.cwd() );

/**
 * Run a WP-CLI command inside the running `wpcli` Docker container.
 *
 * @param command - Everything after `wp`, e.g. `'option get siteurl'`
 * @returns Trimmed stdout of the command
 * @throws If the command exits with a non-zero status
 *
 * @example
 * const url = wpCli( 'option get siteurl' );
 * wpCli( 'eval \'update_option("foo", "bar");\'' );
 */
export function wpCli( command: string ): string {
	return execSync( `docker compose exec -T wpcli wp ${ command }`, {
		cwd: E2E_DIR,
		encoding: 'utf-8',
	} ).trim();
}
