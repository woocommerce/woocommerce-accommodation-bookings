/* eslint-disable jest/no-done-callback */
/**
 * External dependencies
 */
const { test, expect } = require('@playwright/test');
const {
	createProduct,
	visitProductPage,
	getFutureDate,
	fillBookingStartDate,
	fillBookingEndDate,
	clearCart,
	addToCart,
	updateProduct,
	unBlockUI,
	fillBillingDetails,
	placeOrder,
} = require('../utils');
const { customer } = require('../config');

test.describe('Product Tests', () => {
	// Set customer as logged-in user.
	test.use({ storageState: process.env.CUSTOMERSTATE });
	let adminPage;
	let productId;
	const productDetails = {
		title: 'Accomodation product #1',
		baseCost: '10.00',
		minimumNight: '2',
		maximumNight: '5',
		rooms: '2',
	};
	test.beforeAll(async ({ browser }) => {
		adminPage = await browser.newPage({
			storageState: process.env.ADMINSTATE,
		});

		// Create ticket product
		productId = await createProduct(adminPage, productDetails);
	});

	test('Store admin can create an Accommodation product - @foundational', async ({
		page,
	}) => {
		const productData = {
			title: 'Accomodation product #2',
			baseCost: '10.00',
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);

		await expect(page.locator('h1.product_title ')).toContainText(
			productData.title
		);

		await expect(
			page.locator('p.price', { hasText: 'per night' })
		).toContainText(`From $${productData.baseCost} per night`);

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

	test('Verify labels "Select check-in" and "Select check-out" works expected. - @foundational', async ({
		page,
	}) => {
		await visitProductPage(page, productId);

		await expect(
			page.locator('.wc-bookings-date-picker-accommodation-booking')
		).toHaveAttribute('data-content', 'Select check-in');

		const startDate = getFutureDate(1);
		await fillBookingStartDate(page, startDate);

		await page.locator('td.selection-start-date a.ui-state-active').click();
		await expect(
			page.locator('.wc-bookings-date-picker-accommodation-booking')
		).toHaveAttribute('data-content', 'Select check-out');
	});

	test('Verify Setting Minimum Nights Restriction - "Minimum number of nights allowed in a booking" - @foundational', async ({
		page,
	}) => {
		await visitProductPage(page, productId);

		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(
			page,
			getFutureDate(productDetails.minimumNight)
		);

		const addToCardButton = await page.locator(
			'.single_add_to_cart_button'
		);

		await expect(
			page.locator('.wc-bookings-booking-cost .booking-error')
		).toContainText(
			`The minimum duration is ${productDetails.minimumNight} nights`
		);

		await expect(addToCardButton).toHaveClass(/disabled/);

		await fillBookingEndDate(
			page,
			getFutureDate(+productDetails.minimumNight + 1)
		);
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(
				productDetails.minimumNight * productDetails.baseCost
			)}`
		);
		await expect(addToCardButton).not.toHaveClass(/disabled/);
		await addToCart(page);
		await clearCart(page);
	});

	test('Verify Setting Maximum Nights Restriction "Maximum number of nights allowed in a booking" - @foundational', async ({
		page,
	}) => {
		await visitProductPage(page, productId);

		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(
			page,
			getFutureDate(+productDetails.maximumNight + 2)
		);

		const addToCardButton = await page.locator(
			'.single_add_to_cart_button'
		);

		await expect(
			page.locator('.wc-bookings-booking-cost .booking-error')
		).toContainText(
			`The maximum duration is ${productDetails.maximumNight} nights`
		);

		await expect(addToCardButton).toHaveClass(/disabled/);

		await fillBookingEndDate(
			page,
			getFutureDate(productDetails.maximumNight)
		);
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(
				(productDetails.maximumNight - 1) * productDetails.baseCost
			)}`
		);
		await expect(addToCardButton).not.toHaveClass(/disabled/);
		await addToCart(page);
		await clearCart(page);
	});

	test('Verify the Number of Rooms Availability - @foundational', async ({
		page,
	}) => {
		await clearCart(page);
		for (let i = 0; i <= productDetails.rooms; i++) {
			await visitProductPage(page, productId);
			await fillBookingStartDate(page, getFutureDate(1));
			await fillBookingEndDate(
				page,
				getFutureDate(+productDetails.minimumNight + 1)
			);
			await addToCart(page);
		}

		await visitProductPage(page, productId);
		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(
			page,
			getFutureDate(+productDetails.minimumNight + 1)
		);
		await expect(
			page
				.locator('.ui-datepicker-calendar td.fully_booked_start_days')
				.first()
		).toBeVisible();
		await expect(
			page.locator('.wc-bookings-booking-cost .booking-error')
		).toContainText('There are a maximum of 0 places remaining on');
	});

	test('Verify Calendar Display Mode - @foundational', async ({ page }) => {
		await updateProduct(adminPage, productId, {
			calendarDisplayMode: 'always_visible',
		});
		await visitProductPage(page, productId);
		await unBlockUI(page);
		await expect(page.locator('.ui-datepicker-calendar')).toBeVisible();

		await updateProduct(adminPage, productId, {
			calendarDisplayMode: '',
		});
		await visitProductPage(page, productId);
		await unBlockUI(page);
		await expect(page.locator('.ui-datepicker-calendar')).not.toBeVisible();

		await page
			.locator('input[name="wc_bookings_field_start_date_month"]')
			.click();
		await unBlockUI(page);
		await expect(page.locator('.ui-datepicker-calendar')).toBeVisible();
	});

	test('Verify Accommodation Booking with Confirmation Setting - @foundational', async ({
		page,
	}) => {
		await updateProduct(adminPage, productId, {
			requireConfirmation: true,
		});
		await visitProductPage(page, productId);
		await unBlockUI(page);
		await expect(page.locator('.single_add_to_cart_button')).toContainText(
			'Check Availability'
		);

		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(
			page,
			getFutureDate(+productDetails.minimumNight + 1)
		);
		await addToCart(page);

		// Verify Accommodation Booking Process for Slot Requiring Confirmation
		await page.goto('/checkout');
		await expect(
			page.locator('label[for="payment_method_wc-bookings-gateway"]')
		).toContainText('Subject to confirmation');
		await fillBillingDetails(page, customer.billing);
		await placeOrder(page);
		await expect(
			page
				.locator('.wc-booking-summary .status-pending-confirmation')
				.first()
		).toContainText('Pending Confirmation');
	});
});
