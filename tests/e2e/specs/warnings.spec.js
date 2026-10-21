/**
 * PHP warnings check.
 *
 * Runs last, even when other specs fail: `warnings` is the teardown project
 * of the projects that run every other spec. Fails when debug.log cannot be read,
 * when it is not the log WordPress writes to (the marker is missing), and
 * when it holds PHP errors, warnings, notices or deprecations from this plugin.
 */

/**
 * External dependencies
 */
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

/*
 * wp-env mounts the checkout under its directory name: the slug in CI, but it
 * can differ locally. The slug also covers explicit plugin mappings, and the
 * checkout path covers a native site that symlinks this checkout.
 */
const PLUGIN_PATHS = [
	'/plugins/woocommerce-accommodation-bookings/',
	`/plugins/${ path.basename( process.cwd() ) }/`,
	`${ process.cwd() }/`,
];

test( 'debug.log contains no PHP issues from the plugin', async ( {
	request,
} ) => {
	const marker = `${ Date.now() }`;
	const markerResponse = await request.get( `/?e2e_log_marker=${ marker }` );
	expect( markerResponse.ok(), 'the marker request must succeed' ).toBe(
		true
	);

	const response = await request.get( '/wp-content/debug.log' );
	expect( response.status(), 'debug.log must be readable' ).toBe( 200 );

	const log = await response.text();
	expect( log, 'debug.log must be the log WordPress writes to' ).toContain(
		`e2e-log-marker-${ marker }`
	);

	const offending = log
		.split( '\n' )
		.filter(
			( line ) =>
				/PHP (Fatal error|Parse error|Warning|Notice|Deprecated)/.test(
					line
				) &&
				PLUGIN_PATHS.some( ( pluginPath ) =>
					line.includes( pluginPath )
				)
		);

	expect( offending ).toEqual( [] );
} );
