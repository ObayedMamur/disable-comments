/**
 * Resolves the actual wp-env base URL at test runtime.
 *
 * When wp-env starts with --auto-port it may pick a port other than the one
 * in .config/playwright.json. scripts/wp-env-start.js writes the actual port
 * to .wp-env-port after startup. This utility reads that file and patches the
 * config so Playwright and global-setup both connect to the right host.
 *
 * Falls back to the values in .config/playwright.json when the file is absent
 * (e.g. manual wp-env start without the wrapper script).
 */

import * as fs from 'fs';
import * as path from 'path';

const PORT_FILE = path.join(__dirname, '../../../.wp-env-port');

export interface PlaywrightConfig {
	baseUrl: string;
	loginUrl?: string;
	credentials: { username: string; password: string };
	timeouts?: {
		pageLoad?: number;
		elementWait?: number;
		networkRequest?: number;
	};
	testPages?: Record<string, string>;
}

/**
 * Patch a playwright.json config object with the dynamic wp-env port.
 */
export function resolveWpEnvConfig(config: PlaywrightConfig): PlaywrightConfig {
	let port: number;
	try {
		const data = JSON.parse(fs.readFileSync(PORT_FILE, 'utf8'));
		port = data.port;
	} catch {
		throw new Error(
			'Lock file ' + PORT_FILE + ' not found or unreadable.\n' +
			'Run "npm run test:start" before running tests.'
		);
	}
	const base = 'http://localhost:' + port;
	return {
		...config,
		baseUrl: base,
		loginUrl: base + '/wp-login.php',
	};
}
