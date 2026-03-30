import { wpCli } from './utils/wp-cli';

async function globalTeardown() {
	console.log( '🔄 Restoring DB to clean baseline after test run...' );
	wpCli( 'db import /var/www/html/e2e-backup.sql --quiet' );
	console.log( '✅ DB restored to clean state.' );
}

export default globalTeardown;
