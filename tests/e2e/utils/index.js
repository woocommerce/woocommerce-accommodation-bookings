/**
 * External dependencies
 */
import { expect, Page } from '@playwright/test';
import moment from 'moment';
import { pluginConfig } from '../config';

/**
 * Internal dependencies
 */
const { promisify } = require('util');
const execAsync = promisify(require('child_process').exec);
export const api = require('./api');

/**
 * Switch tab in add/edit product page.
 *
 * @param {Page}   page          Playwright page object
 * @param {string} tabName       Tab name
 * @param {string} panelSelector Options Panel selector
 */
export async function switchTab(page, tabName, panelSelector = false) {
	await page
		.locator('.wc-tabs > li:visible > a', { hasText: tabName })
		.last()
		.click();
	if (panelSelector) {
		await expect(page.locator(panelSelector)).toBeVisible();
	}
}

/**
 * Publish product.
 *
 * @param {Page} page Playwright page object
 */
export async function publishProduct(page) {
	// Publish product
	await page.locator('#publish').click();
	await expect(page.locator('.updated.notice')).toBeVisible();
	await expect(page.locator('.updated.notice')).toContainText(
		'Product published.'
	);
}

/**
 * Create a product in WooCommerce with given details and return product ID.
 *
 * @param {Page}   page           Playwright page object.
 * @param {Object} productDetails Product details.
 */
export async function createProduct(page, productDetails) {
	await page.goto('/wp-admin/post-new.php?post_type=product');
	await page
		.locator('#title')
		.fill(productDetails.title || 'Accommodation Product');
	await page.locator('#title').blur();
	await page.locator('#sample-permalink').waitFor();

	await page.locator('#product-type').selectOption('accommodation-booking');

	await switchTab(page, 'General');
	if (productDetails.minimumNight) {
		await page
			.locator('#_wc_accommodation_booking_min_duration')
			.fill(productDetails.minimumNight);
	}
	if (productDetails.maximumNight) {
		await page
			.locator('#_wc_accommodation_booking_max_duration')
			.fill(productDetails.maximumNight);
	}

	if (productDetails.calendarDisplayMode !== undefined) {
		await page
			.locator('#_wc_accommodation_booking_calendar_display_mode')
			.selectOption(productDetails.calendarDisplayMode);
	}

	// Requires confirmation?
	if (productDetails.requireConfirmation !== undefined) {
		if (productDetails.requireConfirmation) {
			await page
				.locator('#_wc_accommodation_booking_requires_confirmation')
				.check();
		} else {
			await page
				.locator('#_wc_accommodation_booking_requires_confirmation')
				.uncheck();
		}
	}

	// Can be cancelled?
	if (productDetails.canBeCancelled !== undefined) {
		if (productDetails.canBeCancelled) {
			await page
				.locator('#_wc_accommodation_booking_user_can_cancel')
				.check();
		} else {
			await page
				.locator('#_wc_accommodation_booking_user_can_cancel')
				.uncheck();
		}
	}

	// Number of rooms available
	if (productDetails.rooms) {
		await switchTab(page, 'Availability');
		await page
			.locator('#_wc_accommodation_booking_qty')
			.fill(productDetails.rooms);
	}

	// Bookings can be made starting from
	if (productDetails.availabilityStart) {
		await switchTab(page, 'Availability');
		await page
			.locator('#_wc_accommodation_booking_min_date')
			.fill(productDetails.availabilityStart);
		if (productDetails.availabilityStartUnit) {
			await page
				.locator('#_wc_accommodation_booking_min_date_unit')
				.selectOption(productDetails.availabilityStartUnit);
		}
	}

	// Bookings can only be made up to
	if (productDetails.availabilityEnd) {
		await switchTab(page, 'Availability');
		await page
			.locator('#_wc_accommodation_booking_max_date')
			.fill(productDetails.availabilityEnd);
		if (productDetails.availabilityEndUnit) {
			await page
				.locator('#_wc_accommodation_booking_max_date_unit')
				.selectOption(productDetails.availabilityEndUnit);
		}
	}

	await switchTab(page, 'Rates');
	await page
		.locator('#_wc_accommodation_booking_base_cost')
		.fill(productDetails.baseCost || '10');
	if (productDetails.displayCost) {
		await page
			.locator('#_wc_accommodation_booking_display_cost')
			.fill(productDetails.displayCost);
	}

	if (productDetails.range) {
		await page.locator('#accommodation_bookings_rates a.add_row').click();
		await page
			.locator('select[name="wc_accommodation_booking_pricing_type[]"]')
			.waitFor();
		await page
			.locator('select[name="wc_accommodation_booking_pricing_type[]"]')
			.selectOption(productDetails.range.type);
		if (productDetails.range.type === 'custom') {
			await page
				.locator(
					'input[name="wc_accommodation_booking_pricing_from_date[]"]'
				)
				.fill(productDetails.range.from);
			await page
				.locator(
					'input[name="wc_accommodation_booking_pricing_to_date[]"]'
				)
				.fill(productDetails.range.to);
		} else {
			await page
				.locator(
					`select[name="wc_accommodation_booking_pricing_from_month[]"]`
				)
				.selectOption(productDetails.range.from);
			await page
				.locator(
					`select[name="wc_accommodation_booking_pricing_to_month[]"]`
				)
				.selectOption(productDetails.range.to);
		}
		await page
			.locator(
				'input[name="wc_accommodation_booking_pricing_block_cost[]"]'
			)
			.fill(productDetails.range.cost);
	}

	await publishProduct(page);

	const postId = await page.locator('#post_ID').inputValue();
	return postId;
}

/**
 * Create a product in WooCommerce with given details and return product ID.
 *
 * @param {Page}   page           Playwright page object.
 * @param {never}  productId      Product ID.
 * @param {Object} productDetails Product details.
 */
export async function updateProduct(page, productId, productDetails) {
	await page.goto(`/wp-admin/post.php?post=${productId}&action=edit`);

	await switchTab(page, 'General');
	if (productDetails.calendarDisplayMode !== undefined) {
		await page
			.locator('#_wc_accommodation_booking_calendar_display_mode')
			.selectOption(productDetails.calendarDisplayMode);
	}

	if (productDetails.requireConfirmation !== undefined) {
		if (productDetails.requireConfirmation) {
			await page
				.locator('#_wc_accommodation_booking_requires_confirmation')
				.check();
		} else {
			await page
				.locator('#_wc_accommodation_booking_requires_confirmation')
				.uncheck();
		}
	}

	// Can be cancelled?
	if (productDetails.canBeCancelled !== undefined) {
		if (productDetails.canBeCancelled) {
			await page
				.locator('#_wc_accommodation_booking_user_can_cancel')
				.check();
		} else {
			await page
				.locator('#_wc_accommodation_booking_user_can_cancel')
				.uncheck();
		}
	}

	// Publish product
	await page.locator('#publish').click();
	await expect(page.locator('.updated.notice')).toBeVisible();
}

/**
 * Save admin settings.
 *
 * @param {Page} page Playwright page object
 */
export async function saveSettings(page) {
	if (await page.getByRole('button', { name: 'Save changes' }).isEnabled()) {
		await page.getByRole('button', { name: 'Save changes' }).click();
		await expect(page.locator('.updated').last()).toContainText(
			'Your settings have been saved.'
		);
	}
}

/**
 * Visit product page in storefront.
 *
 * @param {Page}   page      Playwright page object
 * @param {number} productId Product ID to visit
 */
export async function visitProductPage(page, productId) {
	await page.goto(`/?p=${productId}`);
	await expect(page.locator('.product_title')).toBeVisible();
}

/**
 * Fill billing details on checkout page
 *
 * @param {Page}    page                   Playwright page object
 * @param {Object}  customerBillingDetails Customer billing details
 * @param {boolean} isBlock                Whether to use block checkout
 */
export async function fillBillingDetails(
	page,
	customerBillingDetails,
	isBlock = false
) {
	if (isBlock) {
		await blockFillBillingDetails(page, customerBillingDetails);
		return;
	}
	await page
		.locator('#billing_first_name')
		.fill(customerBillingDetails.firstname);
	await page
		.locator('#billing_last_name')
		.fill(customerBillingDetails.lastname);
	await page
		.locator('#billing_country')
		.selectOption(customerBillingDetails.country);
	await page
		.locator('#billing_address_1')
		.fill(customerBillingDetails.addressfirstline);
	await page
		.locator('#billing_address_2')
		.fill(customerBillingDetails.addresssecondline);
	await page.locator('#billing_city').fill(customerBillingDetails.city);
	if (customerBillingDetails.state) {
		await page
			.locator('#billing_state')
			.selectOption(customerBillingDetails.state);
	}
	await page
		.locator('#billing_postcode')
		.fill(customerBillingDetails.postcode);
	await page.locator('#billing_phone').fill(customerBillingDetails.phone);
	await page.locator('#billing_email').fill(customerBillingDetails.email);
}

/**
 * Add product to cart
 *
 * @param {Page} page Playwright page object
 */
export async function addToCart(page) {
	await page.locator('.single_add_to_cart_button').click();
	await expect(
		page.getByRole('link', { name: 'View cart' }).first()
	).toBeVisible();
}

/**
 * Fill Billing details on block checkout page
 *
 * @param {Page}   page            Playwright page object
 * @param {Object} customerDetails Customer billing details
 */
export async function blockFillBillingDetails(page, customerDetails) {
	await page.waitForTimeout(3000);
	const card = await page.locator('.wc-block-components-address-card');
	if (await card.isVisible()) {
		await card.locator('.wc-block-components-address-card__edit').click();
	}
	await page.locator('#email').fill(customerDetails.email);
	await page.locator('#billing-first_name').fill(customerDetails.firstname);
	await page.locator('#billing-last_name').fill(customerDetails.lastname);
	await page
		.locator('#billing-country')
		.selectOption(customerDetails.country);
	await page
		.locator('#billing-address_1')
		.fill(customerDetails.addressfirstline);
	if (
		await page
			.locator('.wc-block-components-address-form__address_2-toggle')
			.isVisible()
	) {
		await page
			.locator('.wc-block-components-address-form__address_2-toggle')
			.click();
	}
	await page
		.locator('#billing-address_2')
		.fill(customerDetails.addresssecondline);
	await page.locator('#billing-city').fill(customerDetails.city);
	if (customerDetails.state) {
		await page
			.locator('#billing-state')
			.selectOption(customerDetails.state);
	}
	await page.locator('#billing-postcode').fill(customerDetails.postcode);
	await page.locator('#billing-postcode').blur();
	await page.waitForLoadState('networkidle');
}

/**
 * Place order in storefront.
 *
 * @param {Page}    page    Playwright page object
 * @param {boolean} isBlock Whether to use block checkout
 */
export async function placeOrder(page, isBlock = false) {
	if (isBlock) {
		await page.getByRole('button', { name: 'Place Order' }).click();
	} else {
		await page.locator('#place_order').click();
	}

	await expect(
		page.getByRole('heading', { name: 'Order received' })
	).toBeVisible();
	const orderId = await page
		.locator('li.woocommerce-order-overview__order strong')
		.textContent();
	return orderId;
}

/**
 * Run WP CLI command.
 *
 * @param {string} command
 */
export async function runWpCliCommand(command) {
	const { stderr } = await execAsync(
		`npm --silent run env run tests-cli -- ${command}`
	);

	if (!stderr) {
		return true;
	}
	console.error(stderr); // eslint-disable-line no-console
	return false;
}

/**
 * Go to Checkout page.
 *
 * @param {Page}    page    Playwright page object
 * @param {boolean} isBlock Whether to use block checkout
 */
export async function goToCheckout(page, isBlock = false) {
	const slug = isBlock ? 'block-checkout' : 'checkout';
	await page.goto(slug);
}

/**
 * Get Future date in Date, Month and Year object
 *
 * @param {number}  days         Number of days to add to current date
 * @param {number}  months       Number of months to add to current date
 * @param {number}  years        Number of years to add to current date
 * @param {boolean} returnMoment Whether to return moment object
 */
export function getFutureDate(
	days,
	months = 0,
	years = 0,
	returnMoment = false
) {
	const date = moment()
		.add(days, 'days')
		.add(months, 'months')
		.add(years, 'years');

	if (returnMoment) {
		return date;
	}

	const futureDateObject = {
		date: date.format('DD'),
		month: date.format('MM'),
		year: date.format('YYYY'),
	};

	return futureDateObject;
}

/**
 * Get check-in time given format.
 *
 * @param {Object} date   Moment date object
 * @param {string} format Date format
 */
export function getCheckInTime(date, format = 'MMMM D, Y \\a\\t h:mm a') {
	const checkInTime = pluginConfig.checkInTime.split(':');
	return date.hour(checkInTime[0]).minute(checkInTime[1]).format(format);
}

/**
 * Get check-out time given format.
 *
 * @param {Object} date   Moment date object
 * @param {string} format Date format
 */
export function getCheckOutTime(date, format = 'MMMM D, Y \\a\\t h:mm a') {
	const checkOutTime = pluginConfig.checkoutTime.split(':');
	return date.hour(checkOutTime[0]).minute(checkOutTime[1]).format(format);
}

/**
 * Fill Booking start date details on product page
 *
 * @param {Page}    page      Playwright page object
 * @param {Object}  startDate Booking start date details
 * @param {boolean} click     Whether to click on date
 */
export async function fillBookingStartDate(page, startDate, click = true) {
	await unBlockUI(page);
	await fillBookingDate(
		page,
		'input[name="wc_bookings_field_start_date_year"]',
		startDate.year
	);
	await fillBookingDate(
		page,
		'input[name="wc_bookings_field_start_date_month"]',
		startDate.month
	);
	await fillBookingDate(
		page,
		'input[name="wc_bookings_field_start_date_day"]',
		startDate.date
	);
	if (click) {
		await page
			.locator('td.selection-start-date a')
			.first()
			.click({ force: true });
	}
}

/**
 * Fill Booking end date details on product page
 *
 * @param {Page}    page    Playwright page object
 * @param {Object}  endDate Booking end date details
 * @param {boolean} click   Whether to click on date
 */
export async function fillBookingEndDate(page, endDate, click = true) {
	await unBlockUI(page);
	await fillBookingDate(
		page,
		'input[name="wc_bookings_field_start_date_to_year"]',
		endDate.year
	);
	await fillBookingDate(
		page,
		'input[name="wc_bookings_field_start_date_to_month"]',
		endDate.month
	);
	await fillBookingDate(
		page,
		'input[name="wc_bookings_field_start_date_to_day"]',
		endDate.date
	);
	if (
		click &&
		(await page.locator('td.selection-end-date a').first().isVisible())
	) {
		await page
			.locator('td.selection-end-date a')
			.first()
			.click({ force: true });
	}
	await unBlockUI(page);
}

/**
 * Fill Booking date details on product page.
 *
 * @param {Page}   page     Playwright page object
 * @param {string} selector Input selector
 * @param {string} value    Value to fill
 */
async function fillBookingDate(page, selector, value) {
	await page.locator(selector).click();
	await page.locator(selector).fill('');
	await page.locator(selector).type(`${value}`);
	await page.locator(selector).blur();
	await unBlockUI(page);
}

/**
 * Wait for block UI to be hidden.
 *
 * @param {Page} page Playwright page object
 */
export async function unBlockUI(page) {
	await page
		.locator('.blockUI.blockOverlay')
		.last()
		.waitFor({ state: 'hidden' });
}

/**
 * Clear cart.
 *
 * @param {Page} page Playwright page object
 */
export async function clearCart(page) {
	await page.goto('/cart/');
	const rows = await page.locator('.cart td a.remove');
	const count = await rows.count();

	for (let i = 0; i < count; i++) {
		await rows.nth(0).click();
		await page.locator('.woocommerce-message').waitFor();
	}
}

/**
 * Update Check-in and Check-out time settings.
 *
 * @param {Page}   page         Playwright page object
 * @param {Object} timeSettings Check-in and Check-out time settings
 */
export async function updateSettings(page, timeSettings) {
	await page.goto(
		'/wp-admin/edit.php?post_type=wc_booking&page=wc_bookings_settings&tab=accommodation'
	);

	await page
		.locator('#woocommerce_accommodation_bookings_times_check_in')
		.fill(timeSettings.checkInTime);
	await page
		.locator('#woocommerce_accommodation_bookings_times_check_out')
		.fill(timeSettings.checkoutTime);
	if (await page.getByRole('button', { name: 'Save changes' }).isEnabled()) {
		await page.getByRole('button', { name: 'Save changes' }).click();
		await expect(
			page.locator('.updated', { hasText: 'Settings saved' })
		).toBeVisible();
	}
}

/**
 * Confirm the booking.
 *
 * @param {Page}   page      Playwright page object.
 * @param {number} bookingId Booking ID.
 */
export async function confirmBooking(page, bookingId) {
	await page.goto(`/wp-admin/post.php?post=${bookingId}&action=edit`);
	await page.locator('#_booking_status').selectOption('confirmed');
	await page.getByRole('button', { name: 'Save Booking' }).click();
}

/**
 * Clear email Logs.
 *
 * @param {Page} page Playwright page object
 */
export async function clearEmailLogs(page) {
	await page.goto('/wp-admin/admin.php?page=email-log');
	const bulkAction = await page.locator('#bulk-action-selector-top');
	if (await bulkAction.isVisible()) {
		await page.locator('#cb-select-all-1').click();
		await bulkAction.selectOption('el-log-list-delete-all');
		await page.locator('#doaction').click();
		await expect(
			page.locator('#setting-error-deleted-email-logs p').first()
		).toContainText('email logs deleted');
	}
}
