/**
 * External dependencies
 */
import { expect, Page } from '@playwright/test';

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
		.locator('.wc-tabs > li > a', { hasText: tabName })
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

	if (productDetails.minimumNight || productDetails.maximumNight) {
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
	}

	if (productDetails.rooms) {
		await switchTab(page, 'Availability');
		await page
			.locator('#_wc_accommodation_booking_qty')
			.fill(productDetails.rooms);
	}

	await switchTab(page, 'Rates');
	await page
		.locator('#_wc_accommodation_booking_base_cost')
		.fill(productDetails.baseCost || '10');
	await publishProduct(page);

	const postId = await page.locator('#post_ID').inputValue();
	return postId;
}

/**
 * Save admin settings.
 *
 * @param {Page} page Playwright page object
 */
export async function saveSettings(page) {
	await page.getByRole('button', { name: 'Save changes' }).click();
	await expect(page.locator('.updated').last()).toContainText(
		'Your settings have been saved.'
	);
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
 * @param {Page}   page                   Playwright page object
 * @param {Object} customerBillingDetails Customer billing details
 * @param {boolean} isBlock               Whether to use block checkout
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
	await page.locator('#email').fill(customerDetails.email);
	await page.locator('#billing-first_name').fill(customerDetails.firstname);
	await page.locator('#billing-last_name').fill(customerDetails.lastname);
	await page
		.locator('#billing .wc-block-components-country-input input')
		.fill(customerDetails.countryName);
	await page
		.locator('#billing .wc-block-components-country-input ul li')
		.first()
		.click();
	await page
		.locator('#billing-address_1')
		.fill(customerDetails.addressfirstline);
	await page
		.locator('#billing-address_2')
		.fill(customerDetails.addresssecondline);
	await page.locator('#billing-city').fill(customerDetails.city);
	if (customerDetails.state) {
		await page
			.locator('#billing-state input')
			.fill(customerDetails.stateName);
		await page.locator('#billing-state ul li').first().click();
	}
	await page.locator('#billing-postcode').fill(customerDetails.postcode);
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
	console.error(stderr);
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
 * @param {number} days Number of days to add to current date
 */
export function getFutureDate(days) {
	const today = new Date();
	const futureDate = new Date(today.getTime() + days * 24 * 60 * 60 * 1000);

	const futureDateObject = {
		date: futureDate.getDate(),
		month: futureDate.getMonth() + 1, // Months are 0-indexed
		year: futureDate.getFullYear(),
	};

	return futureDateObject;
}

/**
 * Fill Booking start date details on product page
 *
 * @param {Page}   page      Playwright page object
 * @param {Object} startDate Booking start date details
 */
export async function fillBookingStartDate(page, startDate) {
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
}

/**
 * Fill Booking end date details on product page
 *
 * @param {Page}   page    Playwright page object
 * @param {Object} endDate Booking end date details
 */
export async function fillBookingEndDate(page, endDate) {
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
	await page
		.locator('input[name="wc_bookings_field_start_date_to_day"]')
		.click();
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
		await rows.nth(i).click();
		await page.locator('.woocommerce-message').waitFor();
	}
}
