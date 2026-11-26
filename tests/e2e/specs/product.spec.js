/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

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
} = require( '../utils' );
const { customer, pluginConfig } = require( '../config' );

test.describe( 'Product Tests', () => {
	// Set customer as logged-in user.
	test.use( { storageState: process.env.CUSTOMERSTATE } );
	let adminPage;
	let productId;
	const productDetails = {
		title: 'Accommodation product #1',
		baseCost: '10.00',
		minimumNight: '2',
		maximumNight: '5',
		rooms: '2',
	};
	test.beforeAll( async ( { browser } ) => {
		adminPage = await browser.newPage( {
			storageState: process.env.ADMINSTATE,
		} );

		await updateSettings( adminPage, pluginConfig );
		// Create ticket product
		productId = await createProduct( adminPage, productDetails );
	} );

	test( 'Store admin can create an Accommodation product - @foundational', async ( {
		page,
	} ) => {
		const productData = {
			title: 'Accommodation product #2',
			baseCost: '10.00',
		};
		const product = await createProduct( adminPage, productData );

		await visitProductPage( page, product );

		await expect( page.locator( 'h1.product_title ' ) ).toContainText(
			productData.title
		);

		await expect(
			page.locator( 'p.price', { hasText: 'per night' } )
		).toContainText( `From $${ productData.baseCost } per night` );

		await expect(
			page.locator( '#tab-accommodation_booking_time ul li', {
				hasText: 'Check-in time',
			} )
		).toContainText( 'Check-in time 2:00 pm' );
		await expect(
			page.locator( '#tab-accommodation_booking_time ul li', {
				hasText: 'Check-out time',
			} )
		).toContainText( 'Check-out time 11:00 am' );
	} );

	test( 'Verify labels "Select check-in" and "Select check-out" works expected. - @foundational', async ( {
		page,
	} ) => {
		const minimumNights = parseInt( productDetails.minimumNight, 10 );

		await visitProductPage( page, productId );

		await expect(
			page.locator( 'p.wc-bookings-accommodation-bookings-title' )
		).toContainText( 'Select check-in' );

		// Keep both dates in one calendar month: the check-out helper only
		// clicks a day that the open calendar shows.
		const checkInOffset =
			getFutureDate( 1, 0, 0, true ).month() ===
			getFutureDate( 1 + minimumNights, 0, 0, true ).month()
				? 1
				: 2 + minimumNights;
		const startDate = getFutureDate( checkInOffset );
		await fillBookingStartDate( page, startDate );

		await expect(
			page.locator( 'p.wc-bookings-accommodation-bookings-title' )
		).toContainText( 'Select check-out' );

		await fillBookingEndDate(
			page,
			getFutureDate( checkInOffset + minimumNights )
		);

		await expect(
			page.locator( 'p.wc-bookings-accommodation-bookings-title' )
		).toContainText( 'Selected! Re-select to change your check-in date.' );
	} );

	test( 'Verify the Number of Rooms Availability - @foundational', async ( {
		page,
	} ) => {
		// Fill the rooms of a product no other test books: bookings held on
		// the shared product would use up rooms before this test starts.
		const product = await createProduct( adminPage, {
			...productDetails,
			title: 'Accommodation product #7',
		} );

		await clearCart( page );
		for ( let i = 0; i < productDetails.rooms; i++ ) {
			await visitProductPage( page, product );
			await fillBookingStartDate( page, getFutureDate( 1 ) );
			await fillBookingEndDate(
				page,
				getFutureDate( +productDetails.minimumNight + 1 )
			);
			await addToCart( page );
		}

		await visitProductPage( page, product );
		await fillBookingStartDate( page, getFutureDate( 1 ), false );
		await fillBookingEndDate(
			page,
			getFutureDate( +productDetails.minimumNight + 1 ),
			false
		);
		await expect(
			page
				.locator( '.ui-datepicker-calendar td.fully_booked_start_days' )
				.first()
		).toBeVisible();
		await expect(
			page.locator( '.wc-bookings-booking-cost .booking-error' )
		).toContainText( 'There are a maximum of 0 places remaining on' );

		// Removing the in-cart bookings releases the rooms they hold.
		await clearCart( page );
	} );

	test( 'Verify Calendar Display Mode - @foundational', async ( {
		page,
	} ) => {
		await updateProduct( adminPage, productId, {
			calendarDisplayMode: 'always_visible',
		} );
		await visitProductPage( page, productId );
		await unBlockUI( page );
		await expect( page.locator( '.ui-datepicker-calendar' ) ).toBeVisible();

		await updateProduct( adminPage, productId, {
			calendarDisplayMode: '',
		} );
		await visitProductPage( page, productId );
		await unBlockUI( page );
		await expect(
			page.locator( '.ui-datepicker-calendar' )
		).not.toBeVisible();

		await page
			.locator( 'input[name="wc_bookings_field_start_date_month"]' )
			.click();
		await unBlockUI( page );
		await expect( page.locator( '.ui-datepicker-calendar' ) ).toBeVisible();
	} );

	test( 'Verify Accommodation Booking with Confirmation Setting - @foundational', async ( {
		page,
	} ) => {
		// Use a product of its own so the shared product keeps its settings
		// and bookings held by other tests cannot make these dates unavailable.
		const product = await createProduct( adminPage, {
			title: 'Accommodation product #8',
			baseCost: '10.00',
			requireConfirmation: true,
		} );

		await clearCart( page );
		await visitProductPage( page, product );
		await unBlockUI( page );
		await expect(
			page.locator( '.single_add_to_cart_button' )
		).toContainText( 'Check Availability' );

		await fillBookingStartDate( page, getFutureDate( 1 ) );
		await fillBookingEndDate(
			page,
			getFutureDate( +productDetails.minimumNight + 1 )
		);
		await addToCart( page );

		// Verify Accommodation Booking Process for Slot Requiring Confirmation
		await page.goto( '/checkout' );
		await expect(
			page.locator(
				'button.wc-block-components-checkout-place-order-button'
			)
		).toContainText( 'Request Confirmation' );
		await fillBillingDetails( page, customer.billing, true );
		await page
			.locator( 'button.wc-block-components-checkout-place-order-button' )
			.click();
		await expect(
			page
				.locator( '.wc-booking-summary .status-pending-confirmation' )
				.first()
		).toContainText( 'Pending Confirmation' );
	} );

	test( 'Verify "Can be cancelled" Setting - @foundational', async ( {
		page,
	} ) => {
		const productData = {
			title: 'Accommodation product #3',
			baseCost: '10.00',
			canBeCancelled: true,
		};
		const product = await createProduct( adminPage, productData );

		await visitProductPage( page, product );
		await unBlockUI( page );

		await fillBookingStartDate( page, getFutureDate( 5, 1 ) );
		await fillBookingEndDate( page, getFutureDate( 7, 1 ) );
		await addToCart( page );

		// Place order
		await page.goto( '/checkout' );
		await page.waitForLoadState( 'networkidle' );
		await fillBillingDetails( page, customer.billing, true );
		await page
			.locator( 'label.wc-block-components-radio-control__option' )
			.first()
			.waitFor();
		const orderId = await placeOrder( page, true );

		page.goto( '/my-account/bookings/' );
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page
			.locator( '.my_account_bookings tr', {
				has: page.locator( 'td.order-number', { hasText: orderId } ),
			} )
			.locator( 'td.booking-cancel a.cancel' )
			.click();

		await expect(
			page.getByText( 'Your booking was cancelled' ).first()
		).toBeVisible();
	} );

	test( 'Only the first eligible check-in or a later date can reach the cart - @foundational', async ( {
		page,
	} ) => {
		const title = 'Accommodation minimum crossing boundary';
		const product = await createProduct( adminPage, {
			title,
			baseCost: '10.00',
			minimumNight: '2',
			maximumNight: '5',
			availabilityStart: '4',
			availabilityStartUnit: 'day',
		} );
		await visitProductPage( page, product );
		const firstBookableDay = page
			.locator( '.ui-datepicker-calendar td[data-handler="selectDay"]' )
			.first();
		const minDate = getFutureDate( 4, 0, 0, true );
		await expect( firstBookableDay ).toHaveAttribute(
			'data-year',
			String( minDate.year() )
		);
		await expect( firstBookableDay ).toHaveAttribute(
			'data-month',
			String( minDate.month() )
		);
		await expect( firstBookableDay.locator( 'a' ) ).toHaveText(
			new RegExp( `^${ minDate.date() }(?!\\d)` )
		);
		await fillBookingStartDate( page, getFutureDate( 1 ), false );
		await fillBookingEndDate( page, getFutureDate( 4 ), false );
		await expect(
			page.locator( '.wc-bookings-booking-cost .booking-error' )
		).toBeVisible();
		await expect(
			page.locator( '.single_add_to_cart_button' )
		).toHaveClass( /disabled/ );

		await visitProductPage( page, product );
		await fillBookingStartDate( page, getFutureDate( 4 ), false );

		await fillBookingEndDate( page, getFutureDate( 5 ), false );
		const error = page.locator(
			'.wc-bookings-booking-cost .booking-error'
		);
		const bookButton = page.locator( '.single_add_to_cart_button' );
		await expect( error ).toHaveText( 'The minimum duration is 2 nights' );
		await expect( bookButton ).toHaveClass( /disabled/ );

		await fillBookingEndDate( page, getFutureDate( 10 ), false );
		await expect( error ).toHaveText( 'The maximum duration is 5 nights' );
		await expect( bookButton ).toHaveClass( /disabled/ );

		// Start from a fresh form: moving check-out back across a month
		// boundary passes through a check-out before check-in.
		await visitProductPage( page, product );
		await fillBookingStartDate( page, getFutureDate( 4 ), false );
		await fillBookingEndDate( page, getFutureDate( 6 ), false );
		await expect(
			page.locator( '.wc-bookings-booking-cost .booking-cost-label' )
		).toHaveText( 'Booking cost: $20.00' );
		await expect( bookButton ).not.toHaveClass( /disabled/ );
		await addToCart( page );
		await page.getByRole( 'link', { name: 'View cart' } ).first().click();
		await expect(
			page.getByRole( 'link', { name: title, exact: true } )
		).toBeVisible();
		await clearCart( page );
	} );

	test( 'Verify Rate Setting should work as expected - @foundational', async ( {
		page,
	} ) => {
		const productData = {
			title: 'Accommodation product #5',
			baseCost: '10.00',
			displayCost: '20.00',
		};
		const product = await createProduct( adminPage, productData );

		await visitProductPage( page, product );
		await unBlockUI( page );

		await expect(
			page.locator( 'p.price', { hasText: 'per night' } )
		).toContainText( `From $${ productData.displayCost } per night` );

		await fillBookingStartDate( page, getFutureDate( 1 ) );
		await fillBookingEndDate( page, getFutureDate( 2 ) );

		await expect(
			page.locator( '.wc-bookings-booking-cost' )
		).toContainText(
			`Booking cost: $${ parseFloat( productDetails.baseCost ) }`
		);
	} );

	test( 'Verify "Rates" Setting for Accommodation Booking with Range Types with Range Type "Range of certain night" - @foundational', async ( {
		page,
	} ) => {
		const startDate = getFutureDate( 1 );
		const endDate = getFutureDate( 7 );
		const productData = {
			title: 'Accommodation product #5',
			baseCost: '10.00',
			displayCost: '20.00',
			range: {
				type: 'custom',
				from: `${ startDate.year }-${ startDate.month }-${ startDate.date }`,
				to: `${ endDate.year }-${ endDate.month }-${ endDate.date }`,
				cost: '50.00',
			},
		};
		const product = await createProduct( adminPage, productData );

		await visitProductPage( page, product );
		await unBlockUI( page );

		await expect(
			page.locator( 'p.price', { hasText: 'per night' } )
		).toContainText( `From $${ productData.displayCost } per night` );

		await fillBookingStartDate( page, getFutureDate( 1 ) );
		await fillBookingEndDate( page, getFutureDate( 2 ) );
		await expect(
			page.locator( '.wc-bookings-booking-cost' )
		).toContainText(
			`Booking cost: $${ parseFloat( productData.range.cost ) }` // 50.00
		);

		await visitProductPage( page, product );
		await fillBookingStartDate( page, getFutureDate( 8 ) );
		await fillBookingEndDate( page, getFutureDate( 9 ) );
		await expect(
			page.locator( '.wc-bookings-booking-cost' )
		).toContainText(
			`Booking cost: $${ parseFloat( productDetails.baseCost ) }` // 10.00
		);
	} );

	test( 'Verify "Rates" Setting for Accommodation Booking with Range Types with Range Type "Range of months" - @foundational', async ( {
		page,
	} ) => {
		let month = new Date().getMonth() + 2;
		if ( month > 12 ) {
			month = month - 12;
		}
		const productData = {
			title: 'Accommodation product #6',
			baseCost: '10.00',
			displayCost: '20.00',
			range: {
				type: 'months',
				from: `${ month }`,
				to: `${ month }`,
				cost: '50.00',
			},
		};
		const product = await createProduct( adminPage, productData );

		await visitProductPage( page, product );
		await unBlockUI( page );

		await expect(
			page.locator( 'p.price', { hasText: 'per night' } )
		).toContainText( `From $${ productData.displayCost } per night` );

		await fillBookingStartDate( page, {
			...getFutureDate( 0, 1 ),
			date: '01',
		} );
		await fillBookingEndDate( page, {
			...getFutureDate( 0, 1 ),
			date: '02',
		} );
		await expect(
			page.locator( '.wc-bookings-booking-cost' )
		).toContainText(
			`Booking cost: $${ parseFloat( productData.range.cost ) }` // 50.00
		);

		await visitProductPage( page, product );
		await fillBookingStartDate( page, {
			...getFutureDate( 0, 2 ),
			date: '01',
		} );
		await fillBookingEndDate( page, {
			...getFutureDate( 0, 2 ),
			date: '02',
		} );
		await expect(
			page.locator( '.wc-bookings-booking-cost' )
		).toContainText(
			`Booking cost: $${ parseFloat( productDetails.baseCost ) }` // 10.00
		);
	} );
} );
