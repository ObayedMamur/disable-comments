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

// Destroy first so stale containers / volumes from a previous run are gone,
// preventing duplicate MySQL containers and startup race conditions.
console.log('Destroying previous wp-env environment...');
const destroy = spawn('npx', ['wp-env', 'destroy', '--force'], { stdio: 'inherit' });
destroy.on('close', function () {
	// Ignore the exit code — destroy fails harmlessly when nothing exists yet.
	startEnv();
});

function startEnv() {
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

	child.on('close', function afterStart(code) {
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

	// wp-env only applies "multisite": true on a fresh volume creation.
	// If volumes were created before that setting existed the install stays
	// single-site. Detect that and convert automatically so tests always get
	// the environment they expect.
	var isMultisite = spawn(
		'npx',
		['wp-env', 'run', 'cli', 'wp', 'core', 'is-installed', '--network'],
		{ stdio: 'ignore' }
	);
	isMultisite.on('close', function (msCode) {
		if (msCode !== 0) {
			ensureMultisite(activatePlugin);
		} else {
			activatePlugin();
		}
	});

	function wpEnvCli(args, label, cb) {
		console.log('\n' + label);
		var child = spawn('npx', ['wp-env', 'run', 'cli', '--'].concat(args), { stdio: 'inherit' });
		child.on('close', cb);
	}

	function ensureMultisite(done) {
		console.log('\nMultisite not detected — converting install...');
		wpEnvCli(['wp', 'core', 'multisite-convert'], 'Converting to multisite...', function () {
			// Read the port we resolved earlier to build DOMAIN_CURRENT_SITE.
			var portData = {};
			try { portData = JSON.parse(fs.readFileSync(PORT_FILE, 'utf8')); } catch (e) {}
			var domain = 'localhost' + (portData.port ? ':' + portData.port : '');

			var configSets = [
				['wp', 'config', 'set', 'MULTISITE', 'true', '--raw', '--type=constant'],
				['wp', 'config', 'set', 'SUBDOMAIN_INSTALL', 'false', '--raw', '--type=constant'],
				['wp', 'config', 'set', 'DOMAIN_CURRENT_SITE', domain, '--type=constant'],
				['wp', 'config', 'set', 'PATH_CURRENT_SITE', '/', '--type=constant'],
				['wp', 'config', 'set', 'SITE_ID_CURRENT_SITE', '1', '--raw', '--type=constant'],
				['wp', 'config', 'set', 'BLOG_ID_CURRENT_SITE', '1', '--raw', '--type=constant'],
			];

			function next(i) {
				if (i >= configSets.length) { return done(); }
				wpEnvCli(configSets[i], 'Setting ' + configSets[i][3] + '...', function () { next(i + 1); });
			}
			next(0);
		});
	}

	// mappings-mounted plugins are not auto-activated by wp-env — activate manually.
	function activatePlugin() {
		console.log('\nActivating disable-comments plugin...');
		var activateArgs = ['wp-env', 'run', 'cli', 'wp', 'plugin', 'activate', 'disable-comments', '--network'];
		var activate = spawn('npx', activateArgs, { stdio: 'inherit' });
		activate.on('close', function (activateCode) {
			if (activateCode !== 0) {
				console.warn('\nPlugin activation exited with code ' + activateCode);
			}
			process.exit(activateCode || 0);
		});
	}
	}); // end afterStart / child.on('close')
}
