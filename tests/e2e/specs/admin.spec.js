/* eslint-disable jest/no-done-callback */
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');
const { pluginConfig } = require('../config');
const { updateSettings } = require('../utils');

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

	test('Store admin can see a notice when the WooCommerce Bookings is not active - @foundational', async ({
		page,
	}) => {
		await page.goto('/wp-admin/plugins.php');

		// Deactivate Bookings plugin
		page.getByRole('link', {
			name: 'Deactivate WooCommerce Bookings',
			exact: true,
		}).click();

		await expect(
			page.locator('.error p', {
				hasText:
					'Accommodation Bookings requires Bookings plugin activated.',
			})
		).toBeVisible();

		await page.goto('/wp-admin/plugins.php');
		// Activate Bookings plugin,
		page.getByRole('link', {
			name: 'Activate WooCommerce Bookings',
			exact: true,
		}).click();
		await expect(
			page.locator('#message.updated.notice.is-dismissible', {
				hasText: 'Plugin activated.',
			})
		).toBeVisible();
	});

	test('Store admin can configure accommodation settings - @foundational', async ({
		page,
	}) => {
		await page.goto(
			'/wp-admin/edit.php?post_type=wc_booking&page=wc_bookings_settings&tab=accommodation'
		);

		await expect(
			page
				.getByRole('heading', {
					name: 'Accommodation',
					exact: true,
				})
				.last()
		).toBeVisible();

		await updateSettings(page, pluginConfig);
	});
});
