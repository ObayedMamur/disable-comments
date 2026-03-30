---
id: TC-200
title: "Latest Comments block is unavailable in block editor when globally disabled"
feature: gutenberg
priority: high
tags: [gutenberg, block-editor, latest-comments, block-inserter]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-200 — Latest Comments Block Is Unavailable in Block Editor When Globally Disabled

## Summary
When "Remove Everywhere" is active, the `core/latest-comments` Gutenberg block must not appear in the block inserter. The plugin unregisters it client-side via `wp.blocks.unregisterBlockType('core/latest-comments')` registered through `enqueue_block_editor_assets`.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] WordPress with Gutenberg block editor active (WP 5.0+)
- [ ] "Remove Everywhere" mode is enabled and saved in plugin settings
- [ ] At least one post exists to edit

## Test Data

| Field | Value |
|-------|-------|
| Plugin mode | Remove Everywhere |
| Block to verify absent | `core/latest-comments` (block title: "Latest Comments") |
| Block editor URL | `/wp-admin/post-new.php` or `/wp-admin/post.php?post=1&action=edit` |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Ensure "Remove Everywhere" is enabled in plugin settings | Settings page confirms Remove Everywhere is the active mode |
| 2 | Navigate to the block editor: create a new post at `/wp-admin/post-new.php` | Block editor (Gutenberg interface) loads |
| 3 | Wait for the editor to fully load (Gutenberg interface visible) | Editor toolbar, content area, and sidebar are visible |
| 4 | Click the "+" block inserter button (in the toolbar or sidebar) | Block inserter panel opens |
| 5 | In the block search/filter field, type "Latest Comments" | Search query is entered in the inserter search box |
| 6 | Verify: the "Latest Comments" block does NOT appear in the search results | No block labeled "Latest Comments" is present in the filtered results |
| 7 | Verify: no block with "Latest Comments" label is shown anywhere in the inserter | Inserter panel contains zero results matching "Latest Comments" |
| 8 | (Optional) Disable the plugin, reload, search again — "Latest Comments" should appear | Confirms the absence was caused by the plugin, not a missing block registration |
| 9 | Navigate to block editor for a different content type if possible and verify the block is also absent there | Block is absent across all post types when Remove Everywhere is active |

## Expected Results
- "Latest Comments" block is absent from the block inserter when Remove Everywhere is active
- Searching for "Latest Comments" returns 0 results in the inserter
- No JavaScript errors in the console from the `unregisterBlockType` call

## Negative / Edge Cases
- If Remove Everywhere is OFF, the "Latest Comments" block must be available in the inserter
- The unregistration must happen before the block can be inserted (race condition check)

## Playwright Notes
**Page URL:** `/wp-admin/post-new.php`

**Key Selectors:**
- `.block-editor-inserter__toggle` — block inserter "+" button
- `.block-editor-inserter__search-input` — search box in inserter panel
- `.block-editor-block-types-list__item[aria-label="Latest Comments"]` — the block item (should be absent)

**Implementation hints:**
- `await page.click('.block-editor-inserter__toggle')`
- `await page.fill('.block-editor-inserter__search-input', 'Latest Comments')`
- `await page.waitForTimeout(500)` — wait for search results to render
- `await expect(page.locator('[aria-label="Latest Comments"]')).toHaveCount(0)`
- Or: `await expect(page.locator('.block-editor-block-types-list__item:has-text("Latest Comments")')).not.toBeVisible()`

## Related
- **WordPress Action:** `enqueue_block_editor_assets`
- **WordPress Function:** `wp.blocks.unregisterBlockType()`
- **Block Name:** `core/latest-comments`
- **Plugin Method:** `enqueue_block_editor_assets()` or similar
