const { test, expect } = require( '@playwright/test' );
const { wp } = require( '../utils/local' );

let owned;
const script =
	'wp-content/plugins/woocommerce-accommodation-bookings/tests/e2e/fixtures/admin-save.php';
function fixture( action, key = '' ) {
	return JSON.parse(
		wp( 'eval-file', script, action, key ).split( 'ACCOM_ADMIN:' ).pop()
	);
}

test.beforeEach( () => {
	owned = fixture( 'create' );
} );
test.afterEach( () => {
	if ( owned ) {
		fixture( 'cleanup', owned.key );
		owned = undefined;
	}
} );

async function login( page, role ) {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( owned.roles[ role ].login );
	await page.locator( '#user_pass' ).fill( owned.roles[ role ].password );
	await page.locator( '#wp-submit' ).click();
}

async function submission( page ) {
	await page.goto(
		`/wp-admin/post.php?post=${ owned.products.target }&action=edit`
	);
	await expect( page.locator( 'form#post' ) ).toBeVisible();
	return Object.fromEntries(
		await page
			.locator( 'form#post' )
			.evaluate( ( form ) => [ ...new FormData( form ).entries() ] )
	);
}

function fields( form ) {
	return {
		...form,
		action: 'editpost',
		post_ID: String( owned.products.target ),
		'product-type': 'accommodation-booking',
		_wc_booking_has_persons: 'yes',
		_wc_booking_has_person_types: 'yes',
		_wc_booking_has_resources: 'yes',
		_wc_booking_resources_assignment: 'customer',
		_wc_booking_resouce_label: '<b>Ocean</b> room',
		_wc_accommodation_booking_base_cost: '22.35',
		_wc_accommodation_booking_qty: '-3',
		_wc_accommodation_booking_min_duration: '2',
		_wc_accommodation_booking_max_duration: '6',
		_wc_accommodation_booking_max_date: '0',
		'person_id[0]': String( owned.persons.target ),
		'person_name[0]': "Adult O'Brien",
		'person_description[0]': 'Saved description',
		'person_menu_order[0]': '2',
		'person_cost[0]': '4.50',
		'person_block_cost[0]': '2.35',
		'person_min[0]': '2',
		'person_max[0]': '5',
		'resource_id[0]': String( owned.resources.target ),
		'resource_menu_order[0]': '-3',
		'resource_cost[0]': '5.55',
		'resource_block_cost[0]': '2.25',
	};
}

async function expectRejectedPersonBatch( page, form, before ) {
	const response = await page.request.post( '/wp-admin/post.php', {
		form,
		maxRedirects: 0,
	} );
	expect( response.status() ).toBe( 302 );
	const after = fixture( 'snapshot', owned.key );
	// The rejected person batch does not reject independently valid product fields.
	expect(
		after[ owned.products.target ].meta._wc_booking_block_cost
	).toEqual( [ '22.35' ] );
	for ( const id of [
		owned.persons.target,
		owned.products.control,
		owned.persons.control,
		owned.resources.control,
	] ) {
		expect( after[ id ] ).toEqual( before[ id ] );
	}
	await page.goto(
		`/wp-admin/post.php?post=${ owned.products.target }&action=edit`
	);
	await expect(
		page.getByText(
			'Person types were not saved. Please reload the product and try again.',
			{ exact: true }
		)
	).toBeVisible();
}

for ( const invalidPerson of [ 'foreign person', 'non-person record' ] ) {
	test( `A later ${ invalidPerson } rejects the whole person batch`, async ( {
		page,
	} ) => {
		await login( page, 'shop_manager' );
		const form = fields( await submission( page ) );
		form[ 'person_id[1]' ] = String(
			invalidPerson === 'non-person record'
				? owned.resources.control
				: owned.persons.control
		);
		form[ 'person_name[1]' ] = 'Must not overwrite';
		const before = fixture( 'snapshot', owned.key );
		await expectRejectedPersonBatch( page, form, before );
	} );
}

test( 'A manager allowed to edit the product cannot change a denied person record', async ( {
	page,
} ) => {
	fixture( 'deny-person', owned.key );
	await login( page, 'shop_manager' );
	const form = fields( await submission( page ) );
	const before = fixture( 'snapshot', owned.key );
	await expectRejectedPersonBatch( page, form, before );
} );
