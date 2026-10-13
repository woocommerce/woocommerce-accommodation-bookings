/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { unBlockUI } = require( '../utils' );
const { wp } = require( '../utils/local' );

const CHECK_IN = 'Select check-in';
const STAY_SELECTED = 'Selected! Re-select to change your check-in date.';

test.describe( 'Single Product blocks', () => {
	let fixture;

	test.beforeAll( () => {
		fixture = JSON.parse(
			wp(
				'eval-file',
				'wp-content/plugins/woocommerce-accommodation-bookings/tests/e2e/fixtures/single-product-blocks.php'
			)
				.split( 'ACCOM_RESULT:' )
				.pop()
		);
	} );

	test.afterAll( () => {
		if ( fixture ) {
			wp(
				'post',
				'delete',
				...Object.values( fixture ).map( String ),
				'--force'
			);
		}
	} );

	test( 'Two rooms and an ordinary product on one page keep their own forms', async ( {
		page,
	} ) => {
		const block = ( productName ) =>
			page.locator( '.wp-block-woocommerce-single-product' ).filter( {
				has: page.getByRole( 'heading', { name: productName } ),
			} );
		const title = ( room ) =>
			room.locator( 'p.wc-bookings-accommodation-bookings-title' );
		const cost = ( room ) => room.locator( '.wc-bookings-booking-cost' );
		const checkInDay = ( room ) => room.locator( 'input.booking_date_day' );
		const checkOutDay = ( room ) =>
			room.locator( 'input.booking_to_date_day' );
		// Every month has these days, and next month is always bookable.
		const selectStay = async ( room, checkIn, checkOut ) => {
			await room.locator( '.ui-datepicker-next' ).click();
			for ( const dayOfMonth of [ checkIn, checkOut ] ) {
				await room
					.locator(
						`.ui-datepicker-calendar td:not(.ui-datepicker-other-month) a[data-date="${ dayOfMonth }"]`
					)
					.click();
				await unBlockUI( room );
			}
		};

		await page.goto( `/?page_id=${ fixture.page }` );
		const first = block( 'Block page first room' );
		const second = block( 'Block page second room' );
		const ordinary = block( 'Block page ordinary product' );

		// The prompt is added by this plugin's script, once per accommodation form.
		await expect( title( first ) ).toHaveText( CHECK_IN );
		await expect( title( second ) ).toHaveText( CHECK_IN );
		await expect(
			page.locator( 'link#wc-accommodation-bookings-styles-css' )
		).toBeAttached();

		// Two nights at 100 in the first room.
		await selectStay( first, 10, 12 );
		await expect( title( first ) ).toHaveText( STAY_SELECTED );
		await expect( cost( first ) ).toContainText( '200.00' );
		await expect( title( second ) ).toHaveText( CHECK_IN );
		await expect( cost( second ) ).toBeHidden();
		await expect( checkInDay( second ) ).toHaveValue( '' );

		// Three nights at 70 in the second room leave the first stay alone.
		await selectStay( second, 20, 23 );
		await expect( title( second ) ).toHaveText( STAY_SELECTED );
		await expect( cost( second ) ).toContainText( '210.00' );
		await expect( title( first ) ).toHaveText( STAY_SELECTED );
		await expect( cost( first ) ).toContainText( '200.00' );
		await expect( checkInDay( first ) ).toHaveValue( '10' );
		await expect( checkOutDay( first ) ).toHaveValue( '12' );

		// The ordinary product is not treated as an accommodation.
		await expect( ordinary ).not.toHaveClass(
			/product-type-accommodation-booking/
		);
		await expect(
			ordinary.locator( 'button[name="add-to-cart"]' )
		).toBeVisible();
	} );
} );
