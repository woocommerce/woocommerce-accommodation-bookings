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
 * @param {Page}   page Playwright page object
 * @param {string} slug Product slug
 */
export async function addToCart(page, slug) {
	await page.goto(`/product/${slug}/`);
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
	const { stdout, stderr } = await execAsync(
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
