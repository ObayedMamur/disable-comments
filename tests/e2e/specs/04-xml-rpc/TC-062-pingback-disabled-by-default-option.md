---
id: TC-062
title: "Default pingback flag is disabled when XML-RPC comments setting is active"
feature: xml-rpc
priority: low
tags: [xml-rpc, pingback, pre_option_default_pingback_flag, new-post, edge-case]
type: edge-case
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-062 — Default Pingback Flag Is Disabled When XML-RPC Comments Setting Is Active

## Summary

The plugin intercepts the `pre_option_default_pingback_flag` WordPress filter to return `0`, which prevents new posts from having pingbacks enabled by default. This test verifies that when the XML-RPC comments setting is active, newly created posts have the "Allow pingbacks and trackbacks" option unchecked by default in the editor.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Disable XML-RPC Comments" is enabled and saved
- [ ] The WordPress block editor (Gutenberg) or Classic Editor is accessible
- [ ] The Discussion metabox (for pingbacks/trackbacks) is visible in the post editor

---

## Test Data

| Field | Value |
|-------|-------|
| WordPress filter | `pre_option_default_pingback_flag` |
| Expected return value (blocked) | `0` (pingbacks off by default) |
| Expected return value (unblocked) | `1` (pingbacks on by default — WP default) |
| Post type | `post` (standard post) |
| Test post title | `TC-062 Pingback Test Post` |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Enable "Disable XML-RPC Comments" checkbox | Checkbox is checked. |
| 3 | Click Save Changes and wait for the success notice | Settings are saved. `remove_xmlrpc_comments = true`. |
| 4 | Navigate to `/wp-admin/post-new.php` to create a new post | The new post editor opens for a fresh, unsaved post. |
| 5 | If using the Classic Editor: locate the Discussion metabox (enable it via Screen Options if not visible). If using Gutenberg: open the document settings panel and find the Discussion section. | The Discussion panel/metabox is visible. |
| 6 | Inspect the "Allow pingbacks and trackbacks on this page" checkbox in the Discussion section | The checkbox is unchecked (pingbacks disabled by default). |
| 7 | Save the post as a draft (click "Save Draft") | The post is saved. |
| 8 | Reload the post editor for the saved draft | The post editor reloads with the saved draft. |
| 9 | Inspect the "Allow pingbacks and trackbacks" checkbox in the Discussion section after reload | The checkbox remains unchecked, confirming the default was applied at creation time. |
| 10 | As a control: disable "Disable XML-RPC Comments", save settings, then create another new post | The "Allow pingbacks and trackbacks" checkbox is now checked by default (standard WordPress behavior). |

---

## Expected Results

- When `remove_xmlrpc_comments = true`, the "Allow pingbacks and trackbacks" checkbox is unchecked by default for all new posts.
- The `pre_option_default_pingback_flag` filter returns `0`, overriding the WordPress default of `1`.
- After saving the post, the pingback setting remains unchecked.
- When the setting is disabled (control case), the WordPress default behavior is restored and pingbacks are on by default for new posts.

---

## Negative / Edge Cases

- The filter only affects the DEFAULT value for new posts. Existing posts with pingbacks explicitly enabled (`ping_status = open`) are not retroactively changed — verify this.
- If the WordPress Discussion metabox is hidden via Screen Options, this test step requires enabling it first.
- This test does not verify that existing pingback records are deleted — only the default for new posts.
- If a third-party plugin also hooks `pre_option_default_pingback_flag`, the effective value may differ — note any conflicts.
- Block editor (Gutenberg) may display the Discussion panel differently than the Classic Editor — test in both environments if both are supported.

---

## Playwright Notes

**Page URL:** `/wp-admin/post-new.php`

**Key Selectors:**
- `.editor-page-attributes__pingbacks input[type="checkbox"]` — Pingback checkbox in Gutenberg Document Settings (confirm selector)
- `#pingback_flag` — Classic Editor Discussion metabox pingback checkbox
- `.components-panel__body .components-toggle-control` — Gutenberg toggle for pingbacks (may vary by WP version)

**Implementation hints:**
- Use `page.goto('/wp-admin/post-new.php')` and wait for the editor to be ready.
- In Gutenberg, you may need to open the Settings sidebar: `await page.click('[aria-label="Settings"]')` before the Discussion panel is accessible.
- Assert the checkbox is unchecked: `expect(page.locator('#pingback_flag')).not.toBeChecked()` (Classic Editor) or the equivalent Gutenberg toggle assertion.
- Use WP-CLI to validate at the option level: `wp eval 'echo get_option("default_pingback_flag");'` — should return `0` when the plugin filter is active.
- For the control test: `wp option patch update disable_comments_options remove_xmlrpc_comments false` then re-run the assertion expecting the checkbox to be checked.

---

## Related

- **WordPress Filters:** `pre_option_default_pingback_flag`
- **WordPress Option:** `default_pingback_flag`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **XML-RPC Endpoint:** `/xmlrpc.php`
- **Plugin Option Key:** `disable_comments_options.remove_xmlrpc_comments`
