/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');

test.describe('Admin Tests', () => {
	// Set admin as logged-in user.
	test.use({ storageState: process.env.ADMINSTATE });

	test('Store admin can login and make sure extension is activated - @foundational', async ({
		page,
	}) => {
		await page.goto('/wp-admin/plugins.php');

		// Addon is active by default in the test environment, so we need to validate that it is activated.
		await expect(
			page.getByRole('link', {
				name: 'Deactivate WooCommerce Accommodation Bookings',
				exact: true,
			})
		).toBeVisible();
	});
});
