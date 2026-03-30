---
# Unique test case ID. Use the next available ID in this feature folder's reserved range.
# See tests/e2e/specs/README.md for the ID range table. Never reuse a retired ID.
id: TC-XXX

# Short, imperative title: what is being verified (not how).
# Example: "Global disable hides comment form on single post"
title: "Short, imperative title: what is being verified"

# The feature folder name (without the number prefix).
# Examples: disable-everywhere, rest-api, avatar, multisite
feature: folder-name (e.g. disable-everywhere)

# Priority: smoke | high | medium | low
# smoke  = plugin is broken without this passing
# high   = core feature, run every release
# medium = less-common path, run on major releases
# low    = edge case / negative test, run periodically
priority: smoke | high | medium | low

# Free-form tags for filtering. Use lowercase kebab-case.
# Examples: [global, toggle, frontend], [rest-api, permissions, auth]
tags: [tag1, tag2]

# Test type:
# functional  = verifies a feature works as documented
# negative    = verifies correct behavior when input is invalid or disallowed
# edge-case   = boundary conditions, unusual combinations, large data
# integration = crosses plugin boundaries (theme, REST API, other plugins)
type: functional | negative | edge-case | integration

# automation_status:
# manual      = no spec file exists yet
# in-progress = spec file is being written
# automated   = spec file exists and is passing in CI
automation_status: manual

# Path to the Playwright spec file, relative to the repo root.
# Leave as empty string ("") until the test is automated.
# Example: "tests/e2e/specs/01-disable-everywhere/TC-001-global-disable-enable.spec.ts"
automation_file: ""

# ISO 8601 date this test case was first created.
created: YYYY-MM-DD

# ISO 8601 date this test case was last modified (frontmatter or body).
updated: YYYY-MM-DD
---

# TC-XXX — Title

## Summary

<!-- One or two sentences: what this test verifies and why it matters.
     Example: "Verifies that enabling 'Remove Everywhere' hides the comment form
     on single post pages. This is the plugin's primary value proposition." -->

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated and network-enabled (if multisite)
- [ ] Logged in as Administrator
- [ ] <!-- Add any additional prerequisites specific to this test -->

---

## Test Data

| Field | Value |
|-------|-------|
| <!-- key --> | <!-- value --> |

<!-- Remove this section entirely if no specific test data is needed.
     Otherwise replace placeholder rows with real data.
     Examples:
     | Post type   | post (standard WordPress post) |
     | Test post   | Title: "TC-XXX Test Post", Status: Published |
     | User role   | Editor (non-admin) |
-->

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | <!-- describe the action clearly --> | <!-- what the tester should see/verify --> |

<!--
Guidelines for writing steps:
- One action per row. Do not bundle multiple clicks into one step.
- Write actions in present tense imperative: "Click", "Navigate to", "Enter", "Select"
- Expected results should be observable: what text appears, what element is missing,
  what HTTP status code is returned, what happens to the page.
- Example rows:
  | 1 | Navigate to Settings > Disable Comments | The Disable Comments settings page loads. The "Remove Everywhere" toggle is visible. |
  | 2 | Toggle "Remove Everywhere" to ON | The toggle changes state. A success notice appears: "Settings saved." |
  | 3 | Navigate to a published Post on the frontend | The comment form and comments section are not present in the page HTML. |
-->

---

## Expected Results

<!-- Bullet list of everything that MUST be true once all steps are complete.
     These are the final acceptance criteria for the test. -->
- <!-- e.g. Comment form is absent from all post types when Remove Everywhere is enabled -->
- <!-- e.g. wp_head does not output comment-reply.min.js -->

---

## Negative / Edge Cases

<!-- What should NOT happen. Boundary conditions. Error states to verify.
     Examples:
     - Disabling comments should not affect post saving or publishing
     - The settings page should not be accessible to non-admin users (403 expected)
     - Submitting the comment form via direct POST should return a 403
     Remove this section entirely if not applicable. -->
-

---

## Playwright Notes

**Page URL:** `<!-- e.g. /wp-admin/admin.php?page=disable_comments_settings -->`

**Key Selectors:**
- `<!-- CSS selector or aria role -->` — description
- <!-- Example: `#disable_comments_remove_everywhere` — "Remove Everywhere" checkbox -->
- <!-- Example: `[role="alert"].notice-success` — Settings saved notice -->

**Implementation hints:**
- <!-- Specific Playwright API, pattern, or assertion to use -->
- <!-- e.g. "Use page.waitForResponse() to intercept the AJAX save call" -->
- <!-- e.g. "Use request interception to mock REST API response" -->
- <!-- e.g. "Use expect(page.locator('form#commentform')).not.toBeVisible() for the hidden form" -->

<!-- Remove the entire Playwright Notes section if this test cannot or should not be
     automated (e.g. manual-only environment-specific checks). -->

---

## Related

- **WordPress Filters:** <!-- e.g. comments_open, get_comments_number, comment_status_links -->
- **AJAX Action:** <!-- e.g. wp_ajax_disable_comments_save_settings -->
- **REST Endpoint:** <!-- e.g. /wp/v2/comments -->
- **Plugin Option Key:** <!-- e.g. disable_comments_options.remove_everywhere -->
