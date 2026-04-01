#!/usr/bin/env node
/**
 * Start wp-env with --auto-port and write the assigned port to .wp-env-port
 * so Playwright tests can discover the dynamic port at runtime.
 *
 * Usage: node scripts/wp-env-start.js [extra wp-env flags]
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const PORT_FILE = path.join(__dirname, '../.wp-env-port');
const args = ['wp-env', 'start', '--update', '--auto-port', ...process.argv.slice(2)];
const child = spawn('npx', args, { stdio: ['inherit', 'pipe', 'pipe'] });

let output = '';

function pipe(stream, dest) {
	stream.on('data', function (chunk) {
		dest.write(chunk);
		output += chunk.toString();
	});
}

pipe(child.stdout, process.stdout);
pipe(child.stderr, process.stderr);

child.on('close', function (code) {
	var match = output.match(/development site started at http:\/\/localhost:(\d+)/i);
	if (!match) {
		// Fallback: try the "WordPress development site" phrasing used by newer wp-env
		match = output.match(/WordPress development site started at http:\/\/localhost:(\d+)/i);
	}

	if (match) {
		var port = parseInt(match[1], 10);
		fs.writeFileSync(PORT_FILE, JSON.stringify({ port: port }, null, 2));
		console.log('\nwp-env port ' + port + ' written to .wp-env-port');
	} else {
		console.warn('\nCould not determine wp-env port — tests will use the fallback port from .config/playwright.json');
	}

	if (code !== 0) {
		process.exit(code);
	}

	// mappings-mounted plugins are not auto-activated by wp-env — activate manually.
	// Check whether this is a multisite install first so we use --network only when appropriate.
	console.log('\nActivating disable-comments plugin...');
	var isMultisite = spawn(
		'npx',
		['wp-env', 'run', 'cli', 'wp', 'core', 'is-installed', '--network'],
		{ stdio: 'ignore' }
	);
	isMultisite.on('close', function (msCode) {
		var activateArgs = ['wp-env', 'run', 'cli', 'wp', 'plugin', 'activate', 'disable-comments'];
		if (msCode === 0) {
			activateArgs.push('--network');
		}
		var activate = spawn('npx', activateArgs, { stdio: 'inherit' });
		activate.on('close', function (activateCode) {
			if (activateCode !== 0) {
				console.warn('\nPlugin activation exited with code ' + activateCode);
			}
			process.exit(activateCode || 0);
		});
	});
});
