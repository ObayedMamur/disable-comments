---
id: TC-201
title: "Discussion panel is hidden in block editor for disabled post types"
feature: gutenberg
priority: medium
tags: [gutenberg, block-editor, discussion-panel, post-type-support]
type: functional
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-201 — Discussion Panel Is Hidden in Block Editor for Disabled Post Types

## Summary
When comments are disabled for a post type, the "Discussion" panel in the block editor sidebar (which controls per-post comment/pingback settings) must be hidden. The plugin calls `remove_post_type_support($type, 'comments')` which causes WordPress to hide the discussion metabox/panel in the editor.

## Prerequisites
- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] "Disable by Post Type" mode is active with "Posts" selected (Pages NOT selected)
- [ ] At least one Post exists to edit
- [ ] At least one Page exists to edit

## Test Data

| Field | Value |
|-------|-------|
| Plugin mode | Disable by Post Type |
| Disabled post type | `post` |
| Enabled post type | `page` |
| Discussion panel title | "Discussion" (in the Post sidebar under Settings) |

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Ensure "Disable by Post Type" is enabled for Posts only (not Pages) in plugin settings | Settings page confirms Posts are disabled; Pages are not |
| 2 | Navigate to edit a Post in the block editor: `/wp-admin/post.php?post=1&action=edit` (substitute a real post ID) | Block editor loads for the Post |
| 3 | Wait for the Gutenberg interface to fully load | Editor toolbar, content area, and sidebar are visible |
| 4 | Open the Settings sidebar (gear icon in the top right toolbar, or press Ctrl+Shift+,) | Settings sidebar panel opens on the right |
| 5 | Click the "Post" tab in the sidebar (not the "Block" tab) | Post-level settings panels are shown in the sidebar |
| 6 | Scroll through the post panels in the sidebar | All available panels are visible |
| 7 | Verify: "Discussion" panel is NOT present in the sidebar for this Post | No panel titled "Discussion" appears in the Post sidebar |
| 8 | Navigate to edit a Page: `/wp-admin/post.php?post=2&action=edit` (substitute a real page ID) | Block editor loads for the Page |
| 9 | Open Settings sidebar and click the "Page" tab | Page-level settings panels are shown |
| 10 | Scroll through the panels in the sidebar | All available panels are visible |
| 11 | Verify: "Discussion" panel IS present in the Page sidebar (since Pages are not disabled) | A panel titled "Discussion" is visible in the Page sidebar |
| 12 | Open the Discussion panel on the Page and verify it shows checkboxes for "Allow comments" and "Allow pingbacks & trackbacks" | Discussion panel expands and reveals the expected comment/pingback controls |

## Expected Results
- Discussion panel is absent from the editor sidebar when editing Posts (disabled type)
- Discussion panel is present when editing Pages (enabled type)
- No JavaScript errors from the panel removal

## Negative / Edge Cases
- If Remove Everywhere is used (not By Post Type), Discussion should be absent for ALL post types
- Switching from By Post Type to Remove Everywhere should immediately affect the editor on next load

## Playwright Notes
**Page URL:** `/wp-admin/post.php?post={ID}&action=edit`

**Key Selectors:**
- `.editor-sidebar__panel-tabs .components-button` — sidebar tab buttons
- `.components-panel__body:has(button:text("Discussion"))` — Discussion panel container
- `button:text("Discussion")` — Discussion panel toggle button

**Implementation hints:**
- `await page.goto('/wp-admin/post.php?post=1&action=edit')`
- `await page.waitForSelector('.editor-sidebar', { state: 'visible' })` — wait for editor
- `await page.click('.interface-pinned-items button[aria-label="Settings"]')` — open sidebar if closed
- `await expect(page.locator('button:text("Discussion")')).toHaveCount(0)` — absent for Post
- Then navigate to page: `await page.goto('/wp-admin/post.php?post=2&action=edit')`
- `await expect(page.locator('button:text("Discussion")')).toBeVisible()` — present for Page

## Related
- **WordPress Function:** `remove_post_type_support()`, `post_type_supports()`
- **WordPress Action:** `wp_loaded` → `init_wploaded_filters()`
- **Plugin Method:** `init_wploaded_filters()`
