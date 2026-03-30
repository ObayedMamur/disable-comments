import type { Page } from '@playwright/test';
import type { Admin } from '@wordpress/e2e-test-utils-playwright';

/**
 * Page Object Model for the Disable Comments settings page.
 * `/wp-admin/admin.php?page=disable_comments_settings`
 */
export class SettingsPage {
	readonly page: Page;
	readonly admin: Admin;

	// ── Locators ──────────────────────────────────────────────────────────────

	/** "Remove Everywhere" radio — name="mode" value="remove_everywhere" */
	readonly removeEverywhereRadio;

	/** "On Specific Post Types" radio — name="mode" value="selected_types" */
	readonly selectedTypesRadio;

	/** Post-type checkboxes shown when "Specific Types" is selected */
	readonly postTypeCheckboxes;

	/** Save Changes submit button */
	readonly saveButton;

	/** SweetAlert success popup that appears after a successful save */
	readonly successPopup;

	constructor( page: Page, admin: Admin ) {
		this.page = page;
		this.admin = admin;

		this.removeEverywhereRadio = page.locator( '#remove_everywhere' );
		this.selectedTypesRadio = page.locator( '#selected_types' );
		this.postTypeCheckboxes = page.locator( 'input[name="disabled_types[]"]' );
		this.saveButton = page.locator(
			'#disableCommentSaveSettings button[type="submit"]'
		);
		this.successPopup = page.locator( '.swal2-popup' );
	}

	// ── Navigation ────────────────────────────────────────────────────────────

	async navigate() {
		await this.admin.visitAdminPage(
			'admin.php?page=disable_comments_settings'
		);
	}

	// ── Actions ───────────────────────────────────────────────────────────────

	async selectRemoveEverywhere() {
		// The radio input is CSS-hidden; click the associated visible label.
		await this.page.locator( 'label[for="remove_everywhere"]' ).click();
	}

	async selectDisableByPostType() {
		await this.page.locator( 'label[for="selected_types"]' ).click();
	}

	/** Uncheck every post-type checkbox that is currently checked. */
	async uncheckAllPostTypes() {
		// Checkboxes use the same custom-style pattern — click their labels.
		const checkedInputs = this.postTypeCheckboxes.and(
			this.page.locator( ':checked' )
		);
		let count = await checkedInputs.count();
		while ( count > 0 ) {
			const inputId = await checkedInputs.first().getAttribute( 'id' );
			await this.page.locator( `label[for="${ inputId }"]` ).click();
			count = await checkedInputs.count();
		}
	}

	/**
	 * Click Save and wait for the SweetAlert success popup to appear.
	 * The popup auto-dismisses after 3 s — callers need not wait for it
	 * unless they want to interact with the page while it is open.
	 */
	async saveAndWaitForSuccess() {
		await this.saveButton.click();
		await this.successPopup.waitFor( { state: 'visible', timeout: 10_000 } );
	}
}
