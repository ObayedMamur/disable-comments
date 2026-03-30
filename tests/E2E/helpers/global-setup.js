/**
 * Playwright global setup — authenticates as admin and saves storage state
 * so all spec files reuse the session without re-logging in.
 */
const { chromium } = require( '@playwright/test' );
const path = require( 'path' );
const fs   = require( 'fs' );

const { BASE_URL } = require( './wp-urls' );
const WP_USER   = process.env.WP_USERNAME || 'admin';
const WP_PASS   = process.env.WP_PASSWORD || 'password';
const AUTH_FILE = path.join( __dirname, '..', '.auth', 'admin.json' );

module.exports = async function globalSetup() {
	fs.mkdirSync( path.dirname( AUTH_FILE ), { recursive: true } );

	const browser = await chromium.launch();
	const page    = await browser.newPage();

	await page.goto( `${ BASE_URL }/wp-login.php` );
	await page.fill( '#user_login', WP_USER );
	await page.fill( '#user_pass', WP_PASS );
	await page.click( '#wp-submit' );
	await page.waitForURL( `${ BASE_URL }/wp-admin/**` );

	await page.context().storageState( { path: AUTH_FILE } );
	await browser.close();
};
