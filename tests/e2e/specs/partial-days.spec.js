/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { visitProductPage } = require( '../utils' );
const { wp } = require( '../utils/local' );

const CHECK_OUT_ONLY = 'Available for check-out only.';
const CHECK_IN_ONLY = 'Available for check-in only.';
const DISABLED = /(^|\s)ui-datepicker-unselectable(\s|$)/;

test.describe( 'Partially booked days', () => {
	// One room, booked from the 10th to the 12th of the month after next: the
	// 10th can only end a stay, the 11th is full, the 12th can only start one.
	let fixture;

	test.beforeAll( () => {
		fixture = JSON.parse(
			wp(
				'eval-file',
				'wp-content/plugins/woocommerce-accommodation-bookings/tests/e2e/fixtures/partial-days.php'
			)
				.split( 'ACCOM_RESULT:' )
				.pop()
		);
	} );

	test.afterAll( () => {
		if ( fixture ) {
			wp(
				'eval',
				`( new WC_Booking( ${ fixture.booking } ) )->delete( true ); wp_delete_post( ${ fixture.product }, true );`
			);
		}
	} );

	const day = ( page, date ) =>
		page.locator(
			`.ui-datepicker-calendar td.date-date-${ date }:not(.ui-datepicker-other-month)`
		);
	const title = ( page ) =>
		page.locator( '.wc-bookings-accommodation-bookings-title' );
	const dateInput = ( page, name ) =>
		page.locator( `input[name="wc_bookings_field_start_date_${ name }"]` );
	const fullDate = ( date ) =>
		new Date(
			Date.UTC( fixture.year, fixture.month - 1, date )
		).toLocaleDateString( 'en-US', {
			weekday: 'long',
			month: 'long',
			day: 'numeric',
			year: 'numeric',
			timeZone: 'UTC',
		} );

	/**
	 * Open the fixture product and page the calendar to the booked month.
	 *
	 * @param {import('@playwright/test').Page} page Playwright page object.
	 */
	async function openBookedMonth( page ) {
		await visitProductPage( page, fixture.product );
		const month = page.locator( '.ui-datepicker-title' );
		const booked = new Date(
			Date.UTC( fixture.year, fixture.month - 1, 1 )
		).toLocaleDateString( 'en-US', {
			month: 'long',
			year: 'numeric',
			timeZone: 'UTC',
		} );
		await expect( month ).toBeVisible();
		for ( let clicks = 0; clicks < 3; clicks++ ) {
			const shown = ( await month.textContent() ).replace( /\s+/g, ' ' );
			if ( shown === booked ) {
				break;
			}
			await page.locator( '.ui-datepicker-next' ).click();
			await expect( month ).not.toHaveText( shown );
		}
		await expect( month ).toHaveText( booked );
		await expect( day( page, 12 ) ).toHaveClass( /fully_booked_end_days/ );
		await expect( day( page, 10 ) ).toHaveClass( DISABLED );
		await expect( day( page, 12 ) ).not.toHaveClass( DISABLED );
	}

	test( 'Both accommodation forms retain restrictions after month navigation', async ( {
		page,
	} ) => {
		const second = JSON.parse(
			wp(
				'eval-file',
				'wp-content/plugins/woocommerce-accommodation-bookings/tests/e2e/fixtures/partial-day-forms.php',
				String( fixture.product ),
				String( fixture.booking )
			)
				.split( 'ACCOM_RESULT:' )
				.pop()
		);
		try {
			await page.goto( `/?page_id=${ second.page }` );
			const forms = page.locator( 'form.cart' );
			await expect( forms ).toHaveCount( 2 );
			for ( const form of await forms.all() ) {
				const month = form.locator( '.ui-datepicker-title' );
				const booked = new Date(
					Date.UTC( fixture.year, fixture.month - 1, 1 )
				).toLocaleDateString( 'en-US', {
					month: 'long',
					year: 'numeric',
					timeZone: 'UTC',
				} );
				for (
					let clicks = 0;
					clicks < 3 &&
					( await month.textContent() ).replace( /\s+/g, ' ' ) !==
						booked;
					clicks++
				) {
					const shown = await month.textContent();
					await form.locator( '.ui-datepicker-next' ).click();
					await expect( month ).not.toHaveText( shown );
				}
				await expect( day( form, 12 ) ).toHaveClass(
					/fully_booked_end_days/
				);
				await expect( day( form, 10 ) ).toHaveClass( DISABLED );
				await form.locator( '.ui-datepicker-next' ).click();
				await form.locator( '.ui-datepicker-prev' ).click();
				await expect( day( form, 10 ) ).toHaveClass( DISABLED );
				await expect( day( form, 12 ) ).not.toHaveClass( DISABLED );
				await day( form, 8 ).locator( 'a' ).click();
				await expect( day( form, 12 ) ).toHaveClass( DISABLED );
				await form.locator( '.ui-datepicker-next' ).click();
				await form.locator( '.ui-datepicker-prev' ).click();
				await expect( day( form, 10 ) ).not.toHaveClass( DISABLED );
				await expect( day( form, 12 ) ).toHaveClass( DISABLED );
			}
		} finally {
			wp(
				'eval',
				`(new WC_Booking(${ second.booking }))->delete(true); wp_delete_post(${ second.product },true); wp_delete_post(${ second.page },true);`
			);
		}
	} );

	/**
	 * Each partial day carries exactly one info button, wired to its own tooltip.
	 *
	 * @param {import('@playwright/test').Page} page Playwright page object.
	 */
	async function expectOneTooltipPerPartialDay( page ) {
		await expect
			.poll( () =>
				page
					.locator( '.partial-availability-info-wrapper' )
					.evaluateAll( ( wrappers ) =>
						wrappers.map( ( wrapper ) => {
							const button = wrapper.querySelector(
								'.partial-availability-info'
							);
							const controlled =
								wrapper.ownerDocument.querySelectorAll(
									`[id="${ button.getAttribute(
										'aria-controls'
									) }"]`
								);
							return {
								day: wrapper
									.closest( 'td' )
									.className.match( /date-date-(\d+)/ )[ 1 ],
								buttons:
									wrapper.querySelectorAll(
										'[role="button"]'
									).length,
								label: button.getAttribute( 'aria-label' ),
								tooltip:
									wrapper.querySelector( '[role="tooltip"]' )
										.textContent,
								controlsOnlyItsTooltip:
									controlled.length === 1 &&
									wrapper.contains( controlled[ 0 ] ) &&
									controlled[ 0 ].matches(
										'[role="tooltip"]'
									),
							};
						} )
					)
			)
			.toEqual( [
				{
					day: '10',
					buttons: 1,
					label: CHECK_OUT_ONLY,
					tooltip: CHECK_OUT_ONLY,
					controlsOnlyItsTooltip: true,
				},
				{
					day: '12',
					buttons: 1,
					label: CHECK_IN_ONLY,
					tooltip: CHECK_IN_ONLY,
					controlsOnlyItsTooltip: true,
				},
			] );
	}

	test( 'Screen readers get the full date and availability of booked days in a later month', async ( {
		page,
	} ) => {
		await openBookedMonth( page );

		await expect(
			day( page, 10 ).locator( '.screen-reader-text' )
		).toHaveText( [ `${ fullDate( 10 ) } ${ CHECK_OUT_ONLY }` ] );
		await expect(
			day( page, 11 ).locator( '.screen-reader-text' )
		).toHaveText( [ `${ fullDate( 11 ) } Fully booked and unavailable.` ] );
		await expect(
			day( page, 12 ).locator( '.screen-reader-text' )
		).toHaveText( [ `${ fullDate( 12 ) } ${ CHECK_IN_ONLY }` ] );
	} );

	test( 'A check-out-only day ends a stay and a check-in-only day starts one', async ( {
		page,
	} ) => {
		await openBookedMonth( page );

		// Cached month redraws must keep check-out-only days disabled for check-in.
		await page.locator( '.ui-datepicker-next' ).click();
		await page.locator( '.ui-datepicker-prev' ).click();
		await expect( day( page, 10 ) ).toHaveClass( DISABLED );
		await day( page, 10 ).locator( '> a' ).press( 'Enter' );
		await expect( dateInput( page, 'day' ) ).toHaveValue( '' );

		// Check in two nights before the existing stay.
		await day( page, 8 ).locator( 'a' ).click();
		await expect( title( page ) ).toHaveText( 'Select check-out' );
		await expect( day( page, 12 ) ).toHaveClass( DISABLED );
		await expect( day( page, 10 ) ).not.toHaveClass( DISABLED );
		await page.locator( '.ui-datepicker-next' ).click();
		await page.locator( '.ui-datepicker-prev' ).click();
		await expect( day( page, 12 ) ).toHaveClass( DISABLED );
		await expect( day( page, 10 ) ).not.toHaveClass( DISABLED );

		// The check-in-only day cannot end the stay, by keyboard either.
		await day( page, 12 ).locator( '> a' ).press( 'Enter' );
		await expect( dateInput( page, 'day' ) ).toHaveValue( /^0?8$/ );
		await expect( dateInput( page, 'to_day' ) ).toHaveValue( '' );
		await expect( title( page ) ).toHaveText( 'Select check-out' );

		// The check-out-only day can.
		await day( page, 10 )
			.locator( '> a' )
			.click( { position: { x: 4, y: 12 } } );
		await expect( title( page ) ).toHaveText(
			'Selected! Re-select to change your check-in date.'
		);
		await expect( dateInput( page, 'day' ) ).toHaveValue( /^0?8$/ );
		await expect( dateInput( page, 'to_day' ) ).toHaveValue( '10' );

		// With the stay complete, the check-out-only day cannot start a new one.
		await expect( day( page, 10 ) ).toHaveClass( DISABLED );
		await expect( day( page, 12 ) ).not.toHaveClass( DISABLED );
		await day( page, 10 ).locator( '> a' ).press( 'Enter' );
		await expect( dateInput( page, 'day' ) ).toHaveValue( /^0?8$/ );
		await expect( dateInput( page, 'to_day' ) ).toHaveValue( '10' );

		// The check-in-only day can.
		await day( page, 12 )
			.locator( '> a' )
			.click( { position: { x: 4, y: 12 } } );
		await expect( title( page ) ).toHaveText( 'Select check-out' );
		await expect( dateInput( page, 'day' ) ).toHaveValue( '12' );
		await expect( dateInput( page, 'to_day' ) ).toHaveValue( '' );
	} );

	test( 'Partial-day tooltips open on focus and hover and close with the keyboard', async ( {
		page,
	} ) => {
		await openBookedMonth( page );
		await expectOneTooltipPerPartialDay( page );

		const button = day( page, 12 ).locator( '.partial-availability-info' );
		const tooltip = day( page, 12 ).locator(
			'.partial-availability-tooltip'
		);
		const expectTooltip = async ( open ) => {
			await expect( button ).toHaveAttribute(
				'aria-expanded',
				String( open )
			);
			await expect( tooltip ).toHaveAttribute(
				'aria-hidden',
				String( ! open )
			);
			await expect( tooltip ).toBeVisible( { visible: open } );
		};

		await expectTooltip( false );
		await button.focus();
		await expectTooltip( true );
		await page.keyboard.press( 'Escape' );
		await expectTooltip( false );
		await expect( button ).toBeFocused();
		await page.keyboard.press( 'Enter' );
		await expectTooltip( true );
		await page.keyboard.press( 'Space' );
		await expectTooltip( false );

		// Leaving the button forgets that the tooltip was closed.
		await button.blur();
		await button.focus();
		await expectTooltip( true );
		await button.blur();
		await expectTooltip( false );

		// Hover opens it too, Escape closes it, and hovering again reopens it.
		await button.hover();
		await expectTooltip( true );
		await page.keyboard.press( 'Escape' );
		await expectTooltip( false );
		await page.locator( '.product_title' ).hover();
		await button.hover();
		await expectTooltip( true );

		// Paging away and back draws the icons again.
		await page.locator( '.ui-datepicker-prev' ).click();
		await expect( day( page, 12 ) ).not.toHaveClass(
			/fully_booked_end_days/
		);
		await page.locator( '.ui-datepicker-next' ).click();
		await expectOneTooltipPerPartialDay( page );
	} );
} );
