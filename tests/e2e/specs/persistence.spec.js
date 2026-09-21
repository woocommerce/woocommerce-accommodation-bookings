import { test, expect } from '@playwright/test';
import {
	fillBookingStartDate,
	fillBookingEndDate,
	addToCart,
	fillBillingDetails,
	placeOrder,
	visitProductPage,
} from '../utils';
import { customer } from '../config';
import { wp, read, seed, customerLogin, savedStays } from '../utils/local';

let fixture;
let originalOptions;
test.beforeAll( () => {
	// These settings also belong to the existing browser suite running after this file.
	originalOptions = read(
		'array_reduce(array("woocommerce_coming_soon","woocommerce_currency","woocommerce_default_country","timezone_string","date_format","time_format","woocommerce_calc_taxes","woocommerce_enable_guest_checkout","woocommerce_cod_settings","woocommerce_accommodation_bookings_times_settings","woocommerce_checkout_page_id"),function($saved,$key){$saved[$key]=get_option($key,null);return $saved;},array())'
	);
} );
test.afterAll( () => {
	if ( ! originalOptions ) {
		return;
	}
	const options = Buffer.from( JSON.stringify( originalOptions ) ).toString(
		'base64'
	);
	wp(
		'eval',
		`foreach(json_decode(base64_decode('${ options }'),true) as $key=>$value){if(null===$value){delete_option($key);}else{update_option($key,$value);}}`
	);
} );
test.beforeEach( async ( { context } ) => {
	fixture = seed();
	await context.route( '**/*', ( route ) => {
		const url = new URL( route.request().url() );
		return [ 'localhost', '127.0.0.1' ].includes( url.hostname )
			? route.continue()
			: route.abort();
	} );
} );
function dateParts( date ) {
	const [ year, month, day ] = date.split( '-' );
	return { year, month, date: day };
}
async function selectStay( page, stay ) {
	await visitProductPage( page, stay.product );
	await fillBookingStartDate( page, dateParts( stay.start ), false );
	await fillBookingEndDate( page, dateParts( stay.end ), false );
}
async function checkout( page, stay, mode, customerId ) {
	wp(
		'option',
		'update',
		'woocommerce_checkout_page_id',
		String( fixture.pages[ mode ] )
	);
	await page.goto( `/?page_id=${ fixture.pages[ mode ] }` );
	if ( mode === 'block' ) {
		await expect(
			page.locator( '.wc-block-components-checkout-place-order-button' )
		).toBeVisible();
	}
	await fillBillingDetails( page, customer.billing, mode === 'block' );
	// Capture only mail emitted by this checkout, including the replacement stay.
	wp( 'option', 'update', 'accommodation_e2e_mail', '[]', '--format=json' );
	const orderId = Number( await placeOrder( page, mode === 'block' ) );
	const saved = savedStays( stay.product ).find(
		( booking ) => booking.order?.id === orderId
	);
	expect( saved ).toMatchObject( {
		status: 'unpaid',
		product: stay.product,
		resource: stay.resource,
		start: `${ stay.start } 14:00`,
		end: `${ stay.end } 11:00`,
		cost: stay.cost,
		order: {
			id: orderId,
			status: 'processing',
			customer: customerId,
			total: stay.cost,
			currency: 'EUR',
		},
	} );
	expect( saved.order.item ).toBeGreaterThan( 0 );
	const startDisplay = await page
		.locator( '.booking-start-date' )
		.first()
		.textContent();
	const endDisplay = await page
		.locator( '.booking-end-date' )
		.first()
		.textContent();
	const expectedStart = new Date(
		`${ stay.start }T00:00:00Z`
	).toLocaleDateString( 'en-US', {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
		timeZone: 'UTC',
	} );
	const expectedEnd = new Date(
		`${ stay.end }T00:00:00Z`
	).toLocaleDateString( 'en-US', {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
		timeZone: 'UTC',
	} );
	expect( startDisplay ).toContain( expectedStart );
	expect( startDisplay ).toContain( '2:00 pm' );
	expect( endDisplay ).toContain( expectedEnd );
	expect( endDisplay ).toContain( '11:00 am' );
	const mail = read( 'get_option("accommodation_e2e_mail", array())' )
		.map( ( message ) => message.message )
		.join( '\n' );
	expect( mail ).toContain( expectedStart );
	expect( mail ).toContain( expectedEnd );
	expect( mail ).toContain( '2:00 pm' );
	expect( mail ).toContain( '11:00 am' );
	return saved;
}
for ( const [ boundary, role, mode ] of [
	[ 'year', 'guest', 'classic' ],
	[ 'dst', 'customer', 'block' ],
] ) {
	test( `${ role } ${ mode } stay crosses ${ boundary } boundary with saved rates and dates`, async ( {
		page,
	} ) => {
		if ( role === 'customer' ) {
			await customerLogin( page );
		}
		const stay = fixture.stays[ boundary ];
		if ( boundary === 'dst' ) {
			const offset = new Intl.DateTimeFormat( 'en-US', {
				timeZone: 'Europe/Berlin',
				timeZoneName: 'shortOffset',
			} );
			const zone = ( date ) =>
				offset
					.formatToParts( new Date( `${ date }T12:00:00Z` ) )
					.find( ( part ) => part.type === 'timeZoneName' ).value;
			expect( zone( stay.start ) ).not.toBe( zone( stay.end ) );
		}
		await selectStay( page, stay );
		await expect(
			page.locator( '.wc-bookings-booking-cost' )
		).toContainText( String( stay.cost ) );
		await addToCart( page );
		const booking = await checkout(
			page,
			stay,
			mode,
			role === 'guest' ? 0 : fixture.customer
		);
		expect(
			savedStays( stay.product ).filter( ( saved ) => saved.order )
		).toHaveLength( 1 );
		// Calendar nights differ from elapsed hours across DST; assert the calendar count.
		expect(
			( Date.parse( booking.end.slice( 0, 10 ) ) -
				Date.parse( booking.start.slice( 0, 10 ) ) ) /
				86400000
		).toBe( stay.nights );
	} );
}

test( 'independent customers cannot oversell; cancellation permits a new booking', async ( {
	browser,
	page,
} ) => {
	await customerLogin( page );
	const otherContext = await browser.newContext( {
		baseURL: process.env.ACCOM_E2E_URL || 'http://localhost:8889',
	} );
	await otherContext.route( '**/*', ( route ) =>
		[ 'localhost', '127.0.0.1' ].includes(
			new URL( route.request().url() ).hostname
		)
			? route.continue()
			: route.abort()
	);
	try {
		const guest = await otherContext.newPage();
		const stay = fixture.stays.year;
		await selectStay( page, stay );
		await addToCart( page );
		const reserved = savedStays( stay.product );
		expect( reserved ).toHaveLength( 1 );
		expect( reserved[ 0 ] ).toMatchObject( {
			status: 'in-cart',
			product: stay.product,
			resource: stay.resource,
			order: null,
		} );
		await selectStay( guest, stay );
		await expect(
			guest.locator( '.wc-bookings-booking-cost' )
		).toContainText( /This block cannot be booked\./ );
		await expect(
			guest.locator( '.single_add_to_cart_button' )
		).toBeDisabled();
		await guest.goto( '/cart/' );
		await expect(
			guest.getByText( /Your cart is (currently )?empty/i )
		).toBeVisible();
		expect( savedStays( stay.product ) ).toHaveLength( 1 );
		const first = await checkout( page, stay, 'classic', fixture.customer );
		await page.goto( '/my-account/bookings/' );
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page
			.locator( '.my_account_bookings tr', {
				has: page.locator( 'td.order-number', {
					hasText: String( first.order.id ),
				} ),
			} )
			.locator( 'a.cancel' )
			.click();
		await expect
			.poll(
				() =>
					savedStays( stay.product ).find(
						( booking ) => booking.id === first.id
					)?.status
			)
			.toBe( 'cancelled' );
		await selectStay( guest, stay );
		await expect(
			guest.locator( '.wc-bookings-booking-cost' )
		).toContainText( String( stay.cost ) );
		await addToCart( guest );
		const replacement = await checkout( guest, stay, 'classic', 0 );
		expect( replacement.id ).not.toBe( first.id );
		const final = savedStays( stay.product );
		expect( final ).toHaveLength( 2 );
		expect(
			final.filter( ( booking ) => booking.status !== 'cancelled' )
		).toHaveLength( 1 );
		expect(
			new Set( final.map( ( booking ) => booking.order.id ) ).size
		).toBe( 2 );
	} finally {
		await otherContext.close();
	}
} );
