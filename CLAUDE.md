# Disable Comments — Plugin Development Guide

WordPress plugin by WPDeveloper. Allows administrators to globally disable comments by post type, with multisite network support.

- **WordPress.org:** <https://wordpress.org/plugins/disable-comments/>
- **Current version:** 2.6.2
- **Main class:** `Disable_Comments` (singleton) in `disable-comments.php`

---

## Project Structure

```text
disable-comments.php          Main plugin file (~2000 lines), single class
includes/
  cli.php                     WP-CLI command definitions
  class-plugin-usage-tracker.php
views/
  settings.php                Main settings page shell
  comments.php                Tools/delete page shell
  partials/
    _disable.php              Disable-comments form (main settings form)
    _delete.php               Delete comments form
    _sites.php                Multisite sub-site list
    _menu.php / _footer.php / _sidebar.php
assets/
  js/disable-comments-settings-scripts.js   Settings page JS (role exclusion UI, AJAX calls)
  js/disable-comments.js
  css/ scss/
tests/
  test-plugin.php             PHPUnit tests (Brain/Monkey mocking)
  bootstrap.php
```

---

## Key AJAX Handlers

All three AJAX handlers are registered in `__construct()` (~line 49):

| Action | Handler | Line |
| ------ | ------- | ---- |
| `disable_comments_save_settings` | `disable_comments_settings()` | ~1217 |
| `disable_comments_delete_comments` | `delete_comments_settings()` | ~1324 |
| `get_sub_sites` | `get_sub_sites()` | ~1157 |

**Nonce:** All handlers verify nonce `disable_comments_save_settings`. The nonce is created in `admin_enqueue_scripts()` (~line 799) and exposed to JS as `disableCommentsObj._nonce`.

**POST data parsing:** `get_form_array_escaped()` (~line 1202) reads `$_POST['data']` as a URL-encoded string, parses with `wp_parse_args()`, and sanitizes all values with `map_deep(..., 'sanitize_text_field')`.

**Network admin flag:** `$formArray['is_network_admin']` comes from POST data and controls network-wide operations — always verify server-side capability before acting on it.

---

## Multisite Terminology

These terms have specific meanings in this codebase. Use them precisely to avoid confusion.

| Term | Meaning |
| ---- | ------- |
| `$this->networkactive` | Plugin is activated network-wide. Set at construct time from `get_site_option('active_sitewide_plugins')`. Server-side fact — never forgeable. Does **not** mean the current request is from the network admin screen. |
| `$this->sitewide_settings` | Super admin has toggled "apply settings to all sites". Stored in `get_site_option('disable_comments_sitewide_settings')`. Controls whether per-site options are overridden. |
| `is_network_admin()` | WordPress built-in. True only when the URL is under `/wp-admin/network/`. **Always false during AJAX requests.** |
| `$this->is_network_admin_ajax_context()` | Private. Returns true when the request is from a network admin screen — checks WP's `is_network_admin()` first, then `$_GET['is_network_admin']` for AJAX. **Routing hint only — never use for capability decisions.** |
| `$this->can_network_admin_ajax_context()` | Private. Returns true when `is_network_admin_ajax_context()` AND `current_user_can('manage_network_plugins')`. **Use this for capability-gated decisions.** |
| "network admin context" | The current request originates from the network admin screen. Detected via `is_network_admin()` (page loads) or the `is_network_admin=1` GET param appended to the AJAX URL by JS (AJAX). |
| "subsite admin context" | The current request originates from a single-site admin screen inside the network. |
| `update_site_option()` | Writes to network-wide option storage. Used when saving settings that apply across all sites. |
| `update_option()` | Writes to the current site's option storage only. |

---

## Development

```bash
npm install        # Install JS build deps
npm run build      # Compile JS/CSS via Grunt + Babel
npm run release    # Build + generate .pot + package release
```

```bash
composer install              # Install PHP dev deps (Brain/Monkey for tests)
./vendor/bin/phpunit          # Run tests
```

**Linting:** `phpcs.ruleset.xml` is configured for WordPress Coding Standards.

---

## Worktrees

All git worktrees **must** be created inside `.claude/worktrees/`. This directory is already in `.gitignore` and `.distignore`.

```bash
git worktree add .claude/worktrees/<name> <branch>
```

---

## PHP Compatibility

**Target range: PHP 5.6 – 8.x.** Code must run without fatal errors, warnings, or deprecation notices across the full range. Two kinds of problems to avoid:

### Must not break on PHP 5.6 (do not use these newer features)

| Feature | Introduced |
| ------- | ---------- |
| Scalar type hints (`bool $x`, `int $x`, `: string`, `: void`) | PHP 7.0 |
| Null coalescing `??` and `??=` | PHP 7.0 / 7.4 |
| Spaceship operator `<=>` | PHP 7.0 |
| Anonymous classes `new class` | PHP 7.0 |
| `declare(strict_types=1)` | PHP 7.0 |
| Nullable types `?string` | PHP 7.1 |
| Array destructuring `[$a, $b] = $arr` — use `list()` | PHP 7.1 |
| Typed class properties `public int $x` | PHP 7.4 |
| Arrow functions `fn(` | PHP 7.4 |
| Named arguments `func(name: val)` | PHP 8.0 |
| Match expression `match (` | PHP 8.0 |
| Nullsafe operator `?->` | PHP 8.0 |
| Union types `int\|string` | PHP 8.0 |
| `str_contains()`, `str_starts_with()`, `str_ends_with()` | PHP 8.0 |
| Enum declarations | PHP 8.1 |
| Intersection types | PHP 8.1 |
| Readonly properties | PHP 8.1 |
| `never` return type | PHP 8.1 |

Use `isset($x) ? $x : $default` instead of `$x ?? $default`.

### Must not trigger warnings/deprecations on PHP 7.x or 8.x (avoid these old patterns)

| Pattern | Removed / Deprecated |
| ------- | -------------------- |
| `mysql_*` functions | Removed PHP 7.0 |
| `ereg()`, `split()`, `eregi()` | Removed PHP 7.0 |
| Call-time pass-by-reference `foo(&$bar)` | Removed PHP 7.0 |
| `/e` modifier in `preg_replace` | Removed PHP 7.0 |
| `each()` | Removed PHP 8.0 |
| Passing `null` to non-nullable built-in parameters | Deprecated PHP 8.1 |
| Dynamic properties on non-`stdClass` objects without `#[AllowDynamicProperties]` | Deprecated PHP 8.2 |
| Calling `count()` on non-countable value | Warning PHP 7.2+ |

Short array syntax `[]` is fine — it was introduced in PHP 5.4.

---

## Architecture Notes

- **Singleton pattern:** Always access via `Disable_Comments::get_instance()`.
- **CLI support:** `includes/cli.php` calls the same handler methods with `$_args` to bypass nonce (expected for WP-CLI context; nonce bypass is gated on `$this->is_CLI`).
- **Multisite vs single-site:** Plugin behaviour branches heavily on `$this->networkactive` (set in constructor) and `$this->sitewide_settings`.
- **Database queries:** Use `$wpdb->prepare()` throughout `delete_comments()`. Safe against SQL injection.
- **Input sanitization:** `get_form_array_escaped()` uses `wp_parse_args()` + `map_deep(sanitize_text_field)`.
- **`is_multisite()` vs `$this->networkactive` — know the difference:**
  - `$this->networkactive` = plugin is activated network-wide. Use this for **routing** decisions: which options table to read/write, which admin menu to register, whether to show network-wide UI.
  - `is_multisite()` = WordPress is a multisite install (regardless of plugin activation mode). Use this for **capability guards** on any operation that touches network-level data (e.g. enumerating all sites). A per-site-activated plugin on multisite must never allow a regular subsite admin to list or access all network sites.
  - Rule of thumb: if the question is "where do I save this?" use `$this->networkactive`. If the question is "can this user touch network data?" use `is_multisite()`.
