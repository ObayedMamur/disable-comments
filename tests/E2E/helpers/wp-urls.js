/**
 * Shared URL helpers for E2E tests.
 *
 * Import from this module instead of re-declaring BASE_URL in every spec file.
 * The default port matches .wp-env.json "port": 8890.
 */

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8890';

const SETTINGS_URL         = `${ BASE_URL }/wp-admin/options-general.php?page=disable_comments_settings`;
const DELETE_URL           = `${ BASE_URL }/wp-admin/options-general.php?page=disable_comments_settings#delete`;
const NETWORK_SETTINGS_URL = `${ BASE_URL }/wp-admin/network/settings.php?page=disable_comments_settings`;

module.exports = { BASE_URL, SETTINGS_URL, DELETE_URL, NETWORK_SETTINGS_URL };
