/* eslint-disable jest/no-done-callback */
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');

/**
 * Internal dependencies
 */
const {
	createProduct,
	visitProductPage,
	getFutureDate,
	fillBookingStartDate,
	fillBookingEndDate,
	clearCart,
	addToCart,
	unBlockUI,
	fillBillingDetails,
	placeOrder,
	updateSettings,
	getCheckInTime,
	getCheckOutTime,
	api,
	confirmBooking,
	clearEmailLogs,
} = require('../utils');
const { pluginConfig, customer } = require('../config');

test.describe('Order Tests', () => {
	// Set customer as logged-in user.
	test.use({ storageState: process.env.CUSTOMERSTATE });
	let adminPage;
	let productId;
	const productDetails = {
		title: 'Accomodation product - Order Tests',
		baseCost: '10.00',
	};
	test.beforeAll(async ({ browser }) => {
		adminPage = await browser.newPage({
			storageState: process.env.ADMINSTATE,
		});

		await updateSettings(adminPage, pluginConfig);
		// Create ticket product
		productId = await createProduct(adminPage, productDetails);
	});

	test('Verify Booking Details Check-In and Check-Out Date and Time Display. - @foundational', async ({
		page,
	}) => {
		await clearCart(page);
		await visitProductPage(page, productId);
		await unBlockUI(page);

		const checkInDate = getFutureDate(1, 0, 0, true);
		const checkOutDate = getFutureDate(2, 0, 0, true);

		await fillBookingStartDate(page, {
			date: checkInDate.format('DD'),
			month: checkInDate.format('MM'),
			year: checkInDate.format('YYYY'),
		});
		await fillBookingEndDate(page, {
			date: checkOutDate.format('DD'),
			month: checkOutDate.format('MM'),
			year: checkOutDate.format('YYYY'),
		});
		await addToCart(page);

		const checkInTime = getCheckInTime(checkInDate);
		const checkOutTime = getCheckOutTime(checkOutDate);

		// Verify Check-In and Check-Out Date and Time Display on Cart Page.
		await page.goto('/cart');
		await expect(
			page.locator('dl.variation dd.variation-Check-in p').first()
		).toContainText(checkInTime);
		await expect(
			page.locator('dl.variation dd.variation-Check-out p').first()
		).toContainText(checkOutTime);

		// Verify Check-In and Check-Out Date and Time Display on Checkout Page.
		await page.goto('/checkout');
		await expect(
			page.locator('dl.variation dd.variation-Check-in p').first()
		).toContainText(checkInTime);
		await expect(
			page.locator('dl.variation dd.variation-Check-out p').first()
		).toContainText(checkOutTime);

		// Verify Check-In and Check-Out Date and Time Display on Order Received Page.
		await fillBillingDetails(page, customer.billing);
		await placeOrder(page);
		await expect(
			page
				.locator('ul.wc-booking-summary-list .booking-start-date')
				.first()
		).toContainText(getCheckInTime(checkInDate, 'MMMM D, Y, h:mm a'));
		await expect(
			page.locator('ul.wc-booking-summary-list .booking-end-date').first()
		).toContainText(getCheckOutTime(checkOutDate, 'MMMM D, Y, h:mm a'));
	});

	test('Verify Accommodation Booking Details in Confirmation Emails - @foundational', async ({
		page,
	}) => {
		await clearEmailLogs(adminPage);
		await clearCart(page);
		await visitProductPage(page, productId);
		await unBlockUI(page);

		const checkInDate = getFutureDate(2, 0, 0, true);
		const checkOutDate = getFutureDate(3, 0, 0, true);

		await fillBookingStartDate(page, {
			date: checkInDate.format('DD'),
			month: checkInDate.format('MM'),
			year: checkInDate.format('YYYY'),
		});
		await fillBookingEndDate(page, {
			date: checkOutDate.format('DD'),
			month: checkOutDate.format('MM'),
			year: checkOutDate.format('YYYY'),
		});
		await addToCart(page);

		const checkInTime = getCheckInTime(checkInDate, 'MMMM D, Y, h:mm a');
		const checkOutTime = getCheckOutTime(checkOutDate, 'MMMM D, Y, h:mm a');

		// Place order and verify booking details in confirmation email.
		await page.goto('/checkout');
		await fillBillingDetails(page, customer.billing);
		const orderId = await placeOrder(page);
		await api.update.order({
			id: orderId,
			status: 'completed',
		});

		const bookingList = await page
			.locator('.wc-booking-summary-number')
			.first()
			.textContent();
		const bookingId = bookingList?.split('#')[1]?.split('\t')[0];
		await confirmBooking(adminPage, bookingId);

		await adminPage.goto('/wp-admin/admin.php?page=email-log');
		const emailRow = await adminPage
			.locator('#the-list tr', {
				hasText: ` has been confirmed (Order ${orderId}) -`,
			})
			.first();
		await emailRow.locator('td.sent_date').hover();
		await emailRow.locator('.view-content a').click();

		const startDateTr = await adminPage
			.locator('#body_content_inner tr', {
				hasText: 'Booking Start Date',
			})
			.first();
		const endDateTr = await adminPage
			.locator('#body_content_inner tr', { hasText: 'Booking End Date' })
			.first();
		await expect(startDateTr.locator('td').first()).toContainText(
			checkInTime
		);
		await expect(endDateTr.locator('td').first()).toContainText(
			checkOutTime
		);
	});
});
