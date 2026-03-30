---
id: TC-063
title: "XML-RPC comment methods are removed from the method list"
feature: xml-rpc
priority: medium
tags: [xml-rpc, xmlrpc_methods, system.listMethods, integration, security]
type: integration
automation_status: manual
automation_file: ""
created: 2026-03-30
updated: 2026-03-30
---

# TC-063 — XML-RPC Comment Methods Are Removed from the Method List

## Summary

Verifies that all comment-related XML-RPC methods are absent from the `system.listMethods` response when the "Disable XML-RPC Comments" setting is enabled. The plugin removes these methods via the `xmlrpc_methods` filter, ensuring they are not callable by XML-RPC clients.

---

## Prerequisites

- [ ] WordPress site is running (local or staging)
- [ ] Disable Comments plugin is activated
- [ ] Logged in as Administrator
- [ ] WordPress XML-RPC is enabled (not disabled by `add_filter('xmlrpc_enabled', '__return_false')` or server config)
- [ ] "Disable XML-RPC Comments" is enabled and saved
- [ ] The `/xmlrpc.php` endpoint is accessible from the test environment

---

## Test Data

| Field | Value |
|-------|-------|
| XML-RPC endpoint | `/xmlrpc.php` |
| XML-RPC call | `system.listMethods` |
| HTTP method | `POST` |
| Content-Type | `text/xml` |
| Methods that MUST be absent | `wp.newComment`, `wp.editComment`, `wp.deleteComment`, `wp.getComments`, `wp.getComment`, `blogger.newComment`, `blogger.deleteComment` |
| Methods that MUST remain | `wp.getPosts`, `wp.getPost`, `wp.newPost`, `system.listMethods` (non-comment methods) |

---

## Steps

| # | Action | Expected Result |
|---|--------|----------------|
| 1 | Navigate to `/wp-admin/admin.php?page=disable_comments_settings` | Settings page loads. |
| 2 | Enable "Disable XML-RPC Comments" checkbox | Checkbox is checked. |
| 3 | Click Save Changes and wait for success notice | Settings are saved. `remove_xmlrpc_comments = true`. |
| 4 | Construct a `system.listMethods` XML-RPC request body: `<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName><params></params></methodCall>` | Request body is ready. |
| 5 | Send a POST request to `/xmlrpc.php` with the `system.listMethods` payload and `Content-Type: text/xml` header | Request is dispatched to the WordPress XML-RPC endpoint. |
| 6 | Inspect the HTTP response status code | Status is `200 OK` — the endpoint is reachable and returns a valid XML-RPC response. |
| 7 | Parse the XML response and extract the list of method names from the `<array>` value | A list of method name strings is extracted from the response. |
| 8 | Check that `wp.newComment` is NOT in the method list | `wp.newComment` is absent. |
| 9 | Check that `wp.editComment`, `wp.deleteComment`, `wp.getComments`, `wp.getComment` are NOT in the method list | All four methods are absent. |
| 10 | Check that `blogger.newComment` and `blogger.deleteComment` are NOT in the method list | Both Blogger comment methods are absent. |
| 11 | Check that non-comment methods (e.g. `wp.getPosts`, `wp.getPost`, `system.listMethods`) ARE in the method list | Non-comment methods are present, confirming the plugin only removes comment-specific methods. |
| 12 | As a control: disable "Disable XML-RPC Comments", save, and re-run the `system.listMethods` call | All seven comment methods (`wp.newComment` etc.) ARE now present in the method list. Re-enable the setting after. |

---

## Expected Results

- The `system.listMethods` response is `200 OK` with a valid XML-RPC array.
- All seven comment-related methods are absent from the list when the setting is active: `wp.newComment`, `wp.editComment`, `wp.deleteComment`, `wp.getComments`, `wp.getComment`, `blogger.newComment`, `blogger.deleteComment`.
- Non-comment XML-RPC methods remain available and functional.
- When the setting is disabled (control case), all comment methods reappear in the method list.

---

## Negative / Edge Cases

- Attempting to call a removed method directly (e.g. `wp.newComment` with valid credentials) should return an XML-RPC fault, not silently succeed. Verify the fault code and message for a directly invoked removed method.
- If `metaWeblog.newPost` pingback capability is also removed, verify that method behaves accordingly (note: the plugin also targets this method's pingback capability, not necessarily the entire method).
- If an XML-RPC client caches the method list, it may still attempt to call removed methods. The XML-RPC fault response for a missing method is the expected outcome in that scenario.
- Verify that WordPress's built-in XML-RPC security (e.g. Brute Force protection) is not conflicting with repeated test calls to `/xmlrpc.php`.

---

## Playwright Notes

**Page URL:** `API: /xmlrpc.php`

**Key Selectors:**
- N/A — this is a direct HTTP/XML-RPC API test.

**Implementation hints:**
- Use `page.request.post('/xmlrpc.php', { data: '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName><params></params></methodCall>', headers: { 'Content-Type': 'text/xml' } })` to send the request.
- Parse the XML response body: `const text = await response.text();` then use a DOM parser or regex to extract method names from the `<string>` elements within the `<array>`.
- Example check: `expect(text).not.toContain('<value><string>wp.newComment</string></value>')`.
- Also check: `expect(text).not.toContain('wp.editComment')` and similarly for each of the 7 removed methods.
- For non-comment methods: `expect(text).toContain('wp.getPosts')` to confirm partial removal only.
- Use `DOMParser` in Node.js via a helper, or use a lightweight XML parsing library if available in the test project.
- The control test can use WP-CLI: `wp option patch update disable_comments_options remove_xmlrpc_comments false` then re-run and assert the methods are present.

---

## Related

- **WordPress Filters:** `xmlrpc_methods`
- **Removed Methods:** `wp.newComment`, `wp.editComment`, `wp.deleteComment`, `wp.getComments`, `wp.getComment`, `blogger.newComment`, `blogger.deleteComment`
- **AJAX Action:** `wp_ajax_disable_comments_save_settings`
- **XML-RPC Endpoint:** `/xmlrpc.php`
- **Plugin Option Key:** `disable_comments_options.remove_xmlrpc_comments`
