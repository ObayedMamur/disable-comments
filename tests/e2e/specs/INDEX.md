# E2E Test Case Index — Disable Comments Plugin

Master index of all E2E test cases. Updated as new test cases are added or automated.

**Legend — Priority:** 🔴 smoke | 🟠 high | 🟡 medium | 🟢 low
**Legend — Status:** `manual` | `in-progress` | `automated`

---

## 01 — Disable Everywhere

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-001](01-disable-everywhere/TC-001-remove-everywhere-lifecycle.md) | Remove Everywhere lifecycle: enable, verify disabled (Posts + Pages), restore | 🔴 smoke | functional | automated |
| ~~TC-002~~ | _(merged into TC-001)_ | — | — | retired |
| ~~TC-003~~ | _(merged into TC-001)_ | — | — | retired |
| ~~TC-004~~ | _(merged into TC-001)_ | — | — | retired |
| [TC-005](01-disable-everywhere/TC-005-comment-count-shows-zero.md) | Comment count returns 0 for all posts when globally disabled | 🟠 high | functional | manual |
| [TC-006](01-disable-everywhere/TC-006-comment-feed-returns-403.md) | Comment feed URL returns 403 when globally disabled | 🟠 high | functional | manual |
| [TC-007](01-disable-everywhere/TC-007-x-pingback-header-removed.md) | X-Pingback HTTP header is removed when globally disabled | 🟡 medium | functional | manual |
| [TC-008](01-disable-everywhere/TC-008-admin-bar-comment-menu-removed.md) | Admin bar "Comments" shortcut is removed when globally disabled | 🟡 medium | functional | manual |
| [TC-009](01-disable-everywhere/TC-009-admin-sidebar-comments-removed.md) | Admin sidebar "Comments" menu item is removed when globally disabled | 🟡 medium | functional | manual |
| [TC-010](01-disable-everywhere/TC-010-dashboard-recent-comments-widget-hidden.md) | Dashboard "Recent Comments" widget is hidden when globally disabled | 🟡 medium | functional | manual |
| [TC-011](01-disable-everywhere/TC-011-show-existing-comments-option.md) | "Show existing comments" displays old comments while blocking new ones | 🟠 high | functional | manual |
| [TC-012](01-disable-everywhere/TC-012-existing-comments-hidden-without-option.md) | Existing comments are hidden when "Show existing comments" is disabled | 🟡 medium | functional | manual |

---

## 02 — Disable by Post Type

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-020](02-disable-by-post-type/TC-020-disable-posts-only.md) | Disable comments for "Posts" post type only | 🔴 smoke | functional | manual |
| [TC-021](02-disable-by-post-type/TC-021-disable-pages-only.md) | Disable comments for "Pages" post type only | 🟠 high | functional | manual |
| [TC-022](02-disable-by-post-type/TC-022-disable-attachments-only.md) | Disable comments for "Media/Attachments" post type only | 🟡 medium | functional | manual |
| [TC-023](02-disable-by-post-type/TC-023-disable-multiple-post-types.md) | Disable comments for multiple post types simultaneously | 🟠 high | functional | manual |
| [TC-024](02-disable-by-post-type/TC-024-enabled-type-retains-comment-form.md) | Enabled post type retains comment form when others are disabled | 🔴 smoke | negative | manual |
| [TC-025](02-disable-by-post-type/TC-025-comment-count-zero-for-disabled-type.md) | Comment count shows 0 for disabled type, unchanged for enabled | 🟠 high | functional | manual |
| [TC-026](02-disable-by-post-type/TC-026-existing-comments-hidden-for-disabled-type.md) | Existing comments are hidden for disabled post type | 🟠 high | functional | manual |
| [TC-027](02-disable-by-post-type/TC-027-switch-post-type-to-everywhere.md) | Switch from "Disable by Post Type" to "Remove Everywhere" mode | 🟡 medium | functional | manual |
| [TC-028](02-disable-by-post-type/TC-028-switch-everywhere-to-post-type.md) | Switch from "Remove Everywhere" to "Disable by Post Type" mode | 🟡 medium | functional | manual |
| [TC-029](02-disable-by-post-type/TC-029-settings-reflect-saved-post-types.md) | Settings page correctly reflects previously saved post type selections | 🟠 high | functional | manual |

---

## 03 — REST API

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-040](03-rest-api/TC-040-enable-disable-rest-api-setting.md) | Enable "Disable REST API Comments" setting and save | 🟠 high | functional | manual |
| [TC-041](03-rest-api/TC-041-post-comment-via-rest-blocked.md) | Creating a comment via REST API is blocked when globally disabled | 🔴 smoke | functional | manual |
| [TC-042](03-rest-api/TC-042-get-comments-via-rest-blocked.md) | Listing comments via REST API is blocked when globally disabled | 🟠 high | functional | manual |
| [TC-043](03-rest-api/TC-043-rest-api-works-when-setting-disabled.md) | REST API comments work normally when restriction is not enabled | 🟠 high | negative | manual |
| [TC-044](03-rest-api/TC-044-allowed-comment-type-passes-rest.md) | Allowed comment types bypass REST API restriction | 🟡 medium | integration | manual |
| [TC-045](03-rest-api/TC-045-rest-blocked-for-disabled-post-type.md) | REST API comment creation blocked for disabled post type | 🟡 medium | integration | manual |

---

## 04 — XML-RPC

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-060](04-xml-rpc/TC-060-enable-disable-xmlrpc-setting.md) | Enable "Disable XML-RPC Comments" setting and save | 🟠 high | functional | manual |
| [TC-061](04-xml-rpc/TC-061-x-pingback-header-absent.md) | X-Pingback HTTP header is absent when XML-RPC comments are disabled | 🟡 medium | functional | manual |
| [TC-062](04-xml-rpc/TC-062-pingback-disabled-by-default-option.md) | Default pingback flag is disabled when XML-RPC comments setting is active | 🟢 low | edge-case | manual |
| [TC-063](04-xml-rpc/TC-063-xmlrpc-comment-methods-unavailable.md) | XML-RPC comment methods are removed from the method list | 🟡 medium | integration | manual |

---

## 05 — Delete Comments

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-080](05-delete-comments/TC-080-delete-all-comments-everywhere.md) | Delete all comments from all post types (Delete Everywhere flow) | 🔴 smoke | functional | manual |
| [TC-081](05-delete-comments/TC-081-delete-comments-by-post-type.md) | Delete comments for a specific post type only | 🟠 high | functional | manual |
| [TC-082](05-delete-comments/TC-082-delete-spam-comments.md) | Delete spam comments only | 🟠 high | functional | manual |
| [TC-083](05-delete-comments/TC-083-delete-by-comment-type.md) | Delete comments by comment type (e.g. pingbacks only) | 🟡 medium | functional | manual |
| [TC-084](05-delete-comments/TC-084-confirmation-dialog-shown.md) | Confirmation dialog is shown before deletion proceeds | 🟠 high | functional | manual |
| [TC-085](05-delete-comments/TC-085-cancel-confirmation-aborts-delete.md) | Cancelling the confirmation dialog aborts the delete operation | 🟠 high | negative | manual |
| [TC-086](05-delete-comments/TC-086-comment-count-updated-after-delete.md) | Post comment count is updated to 0 after comments are deleted | 🟠 high | functional | manual |
| [TC-087](05-delete-comments/TC-087-delete-success-message-shown.md) | Success message shows count of deleted comments after deletion | 🟡 medium | functional | manual |
| [TC-088](05-delete-comments/TC-088-allowed-comment-types-protected-from-delete.md) | Comment types in the allowlist are NOT deleted during bulk delete | 🟡 medium | edge-case | manual |

---

## 06 — Role Exclusions

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-100](06-role-exclusions/TC-100-enable-role-exclusion-toggle.md) | Enable role exclusion toggle and save | 🟠 high | functional | manual |
| [TC-101](06-role-exclusions/TC-101-administrator-excluded-sees-comment-form.md) | Administrator role excluded — comment form visible when globally disabled | 🟠 high | functional | manual |
| [TC-102](06-role-exclusions/TC-102-editor-excluded-sees-comment-form.md) | Editor role excluded — comment form visible when globally disabled | 🟡 medium | functional | manual |
| [TC-103](06-role-exclusions/TC-103-subscriber-not-excluded-cannot-comment.md) | Subscriber not excluded — cannot see comment form when globally disabled | 🟠 high | negative | manual |
| [TC-104](06-role-exclusions/TC-104-logged-out-user-cannot-bypass.md) | Logged-out users cannot bypass comment restriction via role exclusion | 🟠 high | negative | manual |
| [TC-105](06-role-exclusions/TC-105-multiple-roles-excluded-simultaneously.md) | Multiple roles excluded simultaneously all bypass the restriction | 🟡 medium | functional | manual |

---

## 07 — Avatar

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-120](07-avatar/TC-120-disable-avatars-globally.md) | Disable avatars globally via plugin settings | 🟡 medium | functional | manual |
| [TC-121](07-avatar/TC-121-enable-avatars-globally.md) | Enable avatars via plugin settings (re-enable after disabling) | 🟡 medium | functional | manual |
| [TC-122](07-avatar/TC-122-avatar-setting-independent-of-comment-disable.md) | Avatar setting works independently of comment disable setting | 🟡 medium | edge-case | manual |

---

## 08 — Frontend Behavior

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-140](08-frontend-behavior/TC-140-comment-section-absent-globally-disabled.md) | Comment section is absent from post frontend when globally disabled | 🔴 smoke | functional | manual |
| [TC-141](08-frontend-behavior/TC-141-comment-feed-returns-403.md) | Post comment RSS feed URL returns 403 when globally disabled | 🟠 high | functional | manual |
| [TC-142](08-frontend-behavior/TC-142-comment-count-shows-zero.md) | Comment count in post metadata shows 0 when globally disabled | 🟠 high | functional | manual |
| [TC-143](08-frontend-behavior/TC-143-no-leave-comment-link.md) | "Leave a comment" / comment count link is absent from post metadata | 🟡 medium | functional | manual |
| [TC-144](08-frontend-behavior/TC-144-comment-reply-script-not-loaded.md) | comment-reply.js is not enqueued when comments are globally disabled | 🟢 low | edge-case | manual |

---

## 09 — Allowed Comment Types

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-160](09-allowed-comment-types/TC-160-available-comment-types-listed.md) | Available comment types from database are listed in the settings UI | 🟡 medium | functional | manual |
| [TC-161](09-allowed-comment-types/TC-161-add-comment-type-to-allowlist.md) | Add a comment type to the allowlist and verify it persists after save | 🟠 high | functional | manual |
| [TC-162](09-allowed-comment-types/TC-162-allowed-type-not-blocked-by-global-disable.md) | REST API requests for allowed comment types pass through when globally disabled | 🟠 high | integration | manual |
| [TC-163](09-allowed-comment-types/TC-163-non-allowed-type-blocked-via-rest.md) | Non-allowed comment types are blocked via REST API when globally disabled | 🟡 medium | negative | manual |
| [TC-164](09-allowed-comment-types/TC-164-remove-comment-type-from-allowlist.md) | Removing a comment type from the allowlist re-enables blocking for that type | 🟡 medium | functional | manual |

---

## 10 — Settings UI

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-180](10-settings-ui/TC-180-settings-page-accessible-to-admin.md) | Settings page is accessible to Administrator at Tools > Disable Comments | 🔴 smoke | functional | manual |
| [TC-181](10-settings-ui/TC-181-settings-page-blocked-for-non-admin.md) | Settings page is not accessible to non-admin users | 🟠 high | negative | manual |
| [TC-182](10-settings-ui/TC-182-settings-saved-successfully.md) | Settings are saved successfully with AJAX success response | 🔴 smoke | functional | manual |
| [TC-183](10-settings-ui/TC-183-settings-persist-after-reload.md) | Settings persist correctly after browser page reload | 🟠 high | functional | manual |
| [TC-184](10-settings-ui/TC-184-delete-tab-accessible-and-functional.md) | Delete Comments tab is accessible and shows delete UI | 🟠 high | functional | manual |
| [TC-185](10-settings-ui/TC-185-switching-tabs-works-correctly.md) | Switching between Disable and Delete tabs shows correct content | 🟡 medium | functional | manual |
| [TC-186](10-settings-ui/TC-186-plugin-setup-notice-shown.md) | Plugin setup notice is shown on admin pages when plugin is not yet configured | 🟡 medium | functional | manual |
| [TC-187](10-settings-ui/TC-187-plugin-action-link-in-plugins-list.md) | Settings action link appears in Plugins list for quick navigation | 🟡 medium | functional | manual |

---

## 11 — Gutenberg

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-200](11-gutenberg/TC-200-latest-comments-block-unavailable.md) | "Latest Comments" block is unavailable in block editor when globally disabled | 🟠 high | functional | manual |
| [TC-201](11-gutenberg/TC-201-discussion-panel-hidden-in-block-editor.md) | Discussion panel is hidden in block editor for disabled post types | 🟡 medium | functional | manual |

---

## 12 — Multisite

| ID | Title | Priority | Type | Status |
|----|-------|----------|------|--------|
| [TC-220](12-multisite/TC-220-plugin-network-activation.md) | Plugin can be network-activated from Network Admin Plugins page | 🔴 smoke | functional | manual |
| [TC-221](12-multisite/TC-221-network-admin-settings-page-accessible.md) | Network admin settings page is accessible and loads correctly | 🔴 smoke | functional | manual |
| [TC-222](12-multisite/TC-222-remove-everywhere-applied-network-wide.md) | Remove Everywhere at network level disables comments on all sub-sites | 🟠 high | functional | manual |
| [TC-223](12-multisite/TC-223-sitewide-control-on-blocks-subsite-override.md) | Sub-site admins cannot change settings when sitewide control is enabled | 🟠 high | negative | manual |
| [TC-224](12-multisite/TC-224-sitewide-control-off-allows-subsite-override.md) | Sub-site admins can configure their own settings when sitewide control is disabled | 🟠 high | functional | manual |
| [TC-225](12-multisite/TC-225-site-selection-ui-lists-subsites.md) | Site selection UI lists all sub-sites in the network | 🟡 medium | functional | manual |
| [TC-226](12-multisite/TC-226-disable-comments-for-selected-sites-only.md) | Disable comments for specific selected sub-sites only | 🟠 high | functional | manual |
| [TC-227](12-multisite/TC-227-bulk-delete-comments-all-sites.md) | Bulk delete comments across all sites in the network | 🟡 medium | functional | manual |
| [TC-228](12-multisite/TC-228-bulk-delete-comments-selected-sites.md) | Bulk delete comments for selected sub-sites only | 🟡 medium | functional | manual |
| [TC-229](12-multisite/TC-229-network-admin-bar-comment-removed.md) | Comments link is removed from network admin bar when globally disabled | 🟡 medium | functional | manual |
| [TC-230](12-multisite/TC-230-subsite-admin-sees-network-config.md) | Sub-site admin can view (but not change) network-configured settings when sitewide control is ON | 🟡 medium | functional | manual |

---

## Summary

| Feature Area | Total TCs | 🔴 Smoke | 🟠 High | 🟡 Medium | 🟢 Low | Automated |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| 01 Disable Everywhere | 12 | 2 | 4 | 6 | 0 | 0 |
| 02 Disable by Post Type | 10 | 2 | 4 | 4 | 0 | 0 |
| 03 REST API | 6 | 1 | 2 | 3 | 0 | 0 |
| 04 XML-RPC | 4 | 0 | 1 | 2 | 1 | 0 |
| 05 Delete Comments | 9 | 1 | 4 | 3 | 0 | 0 |
| 06 Role Exclusions | 6 | 0 | 4 | 2 | 0 | 0 |
| 07 Avatar | 3 | 0 | 0 | 3 | 0 | 0 |
| 08 Frontend Behavior | 5 | 1 | 2 | 1 | 1 | 0 |
| 09 Allowed Comment Types | 5 | 0 | 2 | 3 | 0 | 0 |
| 10 Settings UI | 8 | 2 | 3 | 3 | 0 | 0 |
| 11 Gutenberg | 2 | 0 | 1 | 1 | 0 | 0 |
| 12 Multisite | 11 | 2 | 4 | 5 | 0 | 0 |
| **Total** | **81** | **11** | **31** | **36** | **2** | **0** |

### Smoke Test Suite (run first — 11 tests)

Quick reference for the critical path:

| ID | Feature | Title |
|----|---------|-------|
| TC-001 | Disable Everywhere | Enable "Remove Everywhere" and verify global disable |
| TC-003 | Disable Everywhere | Comment form absent on Posts |
| TC-020 | Disable by Post Type | Disable Posts only |
| TC-024 | Disable by Post Type | Enabled type retains comment form |
| TC-041 | REST API | POST comment via REST blocked |
| TC-080 | Delete Comments | Delete all comments (full flow) |
| TC-140 | Frontend Behavior | Comment section absent on frontend |
| TC-180 | Settings UI | Settings page accessible to admin |
| TC-182 | Settings UI | Settings saved successfully |
| TC-220 | Multisite | Plugin network activation |
| TC-221 | Multisite | Network admin settings page accessible |
