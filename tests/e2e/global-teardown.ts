/**
 * Global teardown for Playwright tests.
 *
 * Runs after all tests and prints a summary from the JSON reporter output.
 */

import * as fs from 'fs';
import * as path from 'path';

async function globalTeardown() {
	console.log('Starting Playwright global teardown');

	const testResultsDir = path.join(__dirname, '../test-results');

	try {
		const resultsPath = path.join(testResultsDir, 'results.json');
		if (fs.existsSync(resultsPath)) {
			const results = JSON.parse(fs.readFileSync(resultsPath, 'utf8'));
			const stats = results.stats || {};

			console.log('Test Summary:');
			console.log('  Total: ' + (stats.total || 0));
			console.log('  Passed: ' + (stats.expected || 0));
			console.log('  Failed: ' + (stats.unexpected || 0));
			console.log('  Skipped: ' + (stats.skipped || 0));
			console.log('  Duration: ' + (stats.duration || 0) + 'ms');

			if ((stats.unexpected || 0) === 0) {
				console.log('All tests passed');
			} else {
				console.log(stats.unexpected + ' test(s) failed — review the results');
			}
		}

		// Clean up transient setup error file.
		const errorFile = path.join(testResultsDir, 'setup-error.json');
		if (fs.existsSync(errorFile)) {
			fs.unlinkSync(errorFile);
		}
	} catch (error) {
		console.error('Global teardown error: ' + (error as Error).message);
	}

	console.log('Global teardown completed');
}

export default globalTeardown;
