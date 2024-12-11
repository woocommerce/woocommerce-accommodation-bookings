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
	updateProduct,
	unBlockUI,
	fillBillingDetails,
	placeOrder,
	updateSettings,
} = require('../utils');
const { customer, pluginConfig } = require('../config');

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

		await updateSettings(adminPage, pluginConfig);
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
			getFutureDate(parseInt(productDetails.minimumNight, 10))
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

		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(
			page,
			getFutureDate(parseInt(productDetails.minimumNight, 10) + 1)
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
			getFutureDate(parseInt(productDetails.maximumNight, 10) + 2)
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

		await visitProductPage(page, productId);
		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(
			page,
			getFutureDate(parseInt(productDetails.maximumNight, 10))
		);
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(
				(parseInt(productDetails.maximumNight, 10) - 1) *
					productDetails.baseCost
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
		for (let i = 0; i < productDetails.rooms; i++) {
			await visitProductPage(page, productId);
			await fillBookingStartDate(page, getFutureDate(1));
			await fillBookingEndDate(
				page,
				getFutureDate(+productDetails.minimumNight + 1)
			);
			await addToCart(page);
		}

		await visitProductPage(page, productId);
		await fillBookingStartDate(page, getFutureDate(1), false);
		await fillBookingEndDate(
			page,
			getFutureDate(+productDetails.minimumNight + 1),
			false
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
			page.locator(
				'button.wc-block-components-checkout-place-order-button'
			)
		).toContainText('Request Confirmation');
		await fillBillingDetails(page, customer.billing, true);
		await page
			.locator('button.wc-block-components-checkout-place-order-button')
			.click();
		await expect(
			page
				.locator('.wc-booking-summary .status-pending-confirmation')
				.first()
		).toContainText('Pending Confirmation');
	});

	test('Verify "Can be cancelled" Setting - @foundational', async ({
		page,
	}) => {
		const productData = {
			title: 'Accomodation product #3',
			baseCost: '10.00',
			canBeCancelled: true,
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);
		await unBlockUI(page);

		await fillBookingStartDate(page, getFutureDate(5, 1));
		await fillBookingEndDate(page, getFutureDate(7, 1));
		await addToCart(page);

		// Place order
		await page.goto('/checkout');
		await fillBillingDetails(page, customer.billing, true);
		const orderId = await placeOrder(page, true);

		page.goto('/my-account/bookings/');
		page.on('dialog', (dialog) => dialog.accept());
		await page
			.locator('.my_account_bookings tr', {
				has: page.locator('td.order-number', { hasText: orderId }),
			})
			.locator('td.booking-cancel a.cancel')
			.click();

		await expect(
			page.getByText('Your booking was cancelled').first()
		).toBeVisible();
	});

	test('Availability > Bookings Can Be Made Starting - Month/Week/Day - into the future Setting - @foundational', async ({
		page,
	}) => {
		const productData = {
			title: 'Accomodation product #4',
			baseCost: '10.00',
			availabilityStart: '4',
			availabilityStartUnit: 'day',
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);
		await unBlockUI(page);

		const addToCardButton = await page.locator(
			'.single_add_to_cart_button'
		);

		await expect(
			page.locator('.ui-datepicker-calendar .ui-datepicker-today')
		).toHaveClass(/ui-state-disabled/);

		// TODO: UNCOMMENT THIS. (commented due to bug)
		// Verify Booking can't be made before 4 days into the future
		// await fillBookingStartDate(page, getFutureDate(1));
		// await fillBookingEndDate(page, getFutureDate(4));
		// await expect(
		// 	page.locator('.wc-bookings-booking-cost .booking-error')
		// ).not.toContainText('Booking cost:');
		// await expect(addToCardButton).toHaveClass(/disabled/);

		await visitProductPage(page, product);
		await fillBookingStartDate(page, getFutureDate(1), false);
		await fillBookingEndDate(page, getFutureDate(3), false);
		await expect(
			page.locator('.wc-bookings-booking-cost .booking-error')
		).not.toContainText('Booking cost:');
		await expect(addToCardButton).toHaveClass(/disabled/);

		await visitProductPage(page, product);
		await fillBookingStartDate(page, getFutureDate(4), false);
		await fillBookingEndDate(page, getFutureDate(6), false);
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			'Booking cost:'
		);
		await expect(addToCardButton).not.toHaveClass(/disabled/);
	});

	// eslint-disable-next-line jest/no-disabled-tests
	test('Availability > Booking Can Be Booked Till Setting - @foundational', async ({
		page,
	}) => {
		const productData = {
			title: 'Accomodation product #5',
			baseCost: '10.00',
			availabilityEnd: '1',
			availabilityEndUnit: 'month',
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);
		await unBlockUI(page);

		const addToCardButton = await page.locator(
			'.single_add_to_cart_button'
		);

		// TODO: UNCOMMENT THIS. (commented due to bug)
		// Verify Booking can't be made after 1 month into the future
		// await fillBookingStartDate(page, getFutureDate(-1, 1));
		// await fillBookingEndDate(page, getFutureDate(1, 1));
		// await expect(
		// 	page.locator('.wc-bookings-booking-cost .booking-error')
		// ).not.toContainText('Booking cost:');
		// await expect(addToCardButton).toHaveClass(/disabled/);

		await visitProductPage(page, product);
		await fillBookingStartDate(page, getFutureDate(1, 1), false);
		await fillBookingEndDate(page, getFutureDate(2, 1), false);
		await expect(
			page.locator('.wc-bookings-booking-cost .booking-error')
		).not.toContainText('Booking cost:');
		await expect(addToCardButton).toHaveClass(/disabled/);

		// Booking can be made till 1 month into the future
		await visitProductPage(page, product);
		await fillBookingStartDate(page, getFutureDate(1), false);
		await fillBookingEndDate(page, getFutureDate(2), false);
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			'Booking cost:'
		);
		await expect(addToCardButton).not.toHaveClass(/disabled/);
	});

	test('Verify Rate Setting should work as expected - @foundational', async ({
		page,
	}) => {
		const productData = {
			title: 'Accomodation product #5',
			baseCost: '10.00',
			displayCost: '20.00',
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);
		await unBlockUI(page);

		await expect(
			page.locator('p.price', { hasText: 'per night' })
		).toContainText(`From $${productData.displayCost} per night`);

		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(page, getFutureDate(2));

		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(productDetails.baseCost)}`
		);
	});

	test('Verify "Rates" Setting for Accommodation Booking with Range Types with Range Type "Range of certain night" - @foundational', async ({
		page,
	}) => {
		const startDate = getFutureDate(1);
		const endDate = getFutureDate(7);
		const productData = {
			title: 'Accomodation product #5',
			baseCost: '10.00',
			displayCost: '20.00',
			range: {
				type: 'custom',
				from: `${startDate.year}-${startDate.month}-${startDate.date}`,
				to: `${endDate.year}-${endDate.month}-${endDate.date}`,
				cost: '50.00',
			},
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);
		await unBlockUI(page);

		await expect(
			page.locator('p.price', { hasText: 'per night' })
		).toContainText(`From $${productData.displayCost} per night`);

		await fillBookingStartDate(page, getFutureDate(1));
		await fillBookingEndDate(page, getFutureDate(2));
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(productData.range.cost)}` // 50.00
		);

		await visitProductPage(page, product);
		await fillBookingStartDate(page, getFutureDate(8));
		await fillBookingEndDate(page, getFutureDate(9));
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(productDetails.baseCost)}` // 10.00
		);
	});

	test('Verify "Rates" Setting for Accommodation Booking with Range Types with Range Type "Range of months" - @foundational', async ({
		page,
	}) => {
		let month = new Date().getMonth() + 2;
		if (month > 12) {
			month = month - 12;
		}
		const productData = {
			title: 'Accomodation product #6',
			baseCost: '10.00',
			displayCost: '20.00',
			range: {
				type: 'months',
				from: `${month}`,
				to: `${month}`,
				cost: '50.00',
			},
		};
		const product = await createProduct(adminPage, productData);

		await visitProductPage(page, product);
		await unBlockUI(page);

		await expect(
			page.locator('p.price', { hasText: 'per night' })
		).toContainText(`From $${productData.displayCost} per night`);

		await fillBookingStartDate(page, {
			...getFutureDate(0, 1),
			date: '01',
		});
		await fillBookingEndDate(page, { ...getFutureDate(0, 1), date: '02' });
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(productData.range.cost)}` // 50.00
		);

		await visitProductPage(page, product);
		await fillBookingStartDate(page, {
			...getFutureDate(0, 2),
			date: '01',
		});
		await fillBookingEndDate(page, { ...getFutureDate(0, 2), date: '02' });
		await expect(page.locator('.wc-bookings-booking-cost')).toContainText(
			`Booking cost: $${parseFloat(productDetails.baseCost)}` // 10.00
		);
	});
});
