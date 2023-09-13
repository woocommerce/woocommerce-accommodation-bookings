/* eslint-disable jest/no-done-callback */
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');
const { createProduct, visitProductPage } = require('../utils');

test.describe('Product Tests', () => {
	// Set admin as logged-in user.
	test.use({ storageState: process.env.ADMINSTATE });

	test('Store admin can create an Accommodation product - @foundational', async ({
		page,
	}) => {
		const productDetails = {
			title: 'Accomodation product #1',
			baseCost: '10.00',
		};
		const productId = await createProduct(page, productDetails);

		await visitProductPage(page, productId);

		await expect(page.locator('h1.product_title ')).toContainText(
			productDetails.title
		);

		await expect(
			page.locator('p.price', { hasText: 'per night' })
		).toContainText(`From $${productDetails.baseCost} per night`);

		await expect(
			page.locator('#tab-accommodation_booking_time ul li', {
				hasText: 'Check-in time',
			})
		).toContainText('Check-in time 2:00 pm');
		await expect(
			page.locator('#tab-accommodation_booking_time ul li', {
				hasText: 'Check-out time',
			})
		).toContainText('Check-out time 11:00 am');
	});
});
