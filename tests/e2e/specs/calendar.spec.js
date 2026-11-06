/**
 * External dependencies
 */
const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const { wp } = require( '../utils/local' );
// wp-env mounts the checkout under its folder name, so this also works in worktrees.
const fixture = `wp-content/plugins/${ path.basename(
	path.resolve( __dirname, '../../..' )
) }/tests/e2e/fixtures/calendar.php`;

test( 'Booked accommodation nights are unavailable in the Calendar block - @foundational', async ( {
	page,
} ) => {
	try {
		wp( 'eval-file', fixture );
		await page.goto( '/?pagename=accommodation-calendar-e2e' );
		await page.getByRole( 'button', { name: 'Go to next month' } ).click();

		const soldOut = {
			name: 'Accommodation calendar E2E (Sold out)',
			exact: true,
		};
		const bookable = { name: 'Accommodation calendar E2E', exact: true };
		// Each month-view cell labels its date with an id ending in -date-YYYY-MM-DD.
		const today = new Date();
		const next = new Date( today.getFullYear(), today.getMonth() + 1, 1 );
		const month = `${ next.getFullYear() }-${ String(
			next.getMonth() + 1
		).padStart( 2, '0' ) }`;
		const day = ( dayOfMonth ) =>
			page.locator( 'td' ).filter( {
				has: page.locator(
					`span[id$="-date-${ month }-${ dayOfMonth }"]`
				),
			} );

		await expect( page.getByRole( 'button', soldOut ) ).toHaveCount( 7 );
		for ( let night = 10; night <= 16; night++ ) {
			await expect(
				day( night ).getByRole( 'button', soldOut )
			).toBeDisabled();
		}
		// The check-out day starts a free night.
		await expect( day( 17 ).getByRole( 'button', bookable ) ).toBeEnabled();
	} finally {
		wp( 'eval-file', fixture, 'cleanup' );
	}
} );
