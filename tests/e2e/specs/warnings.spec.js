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
const { readPhpDebugLog } = require( '@woocommerce/e2e-utils-playwright' );
const filterPhpWarnings = require( '@woocommerce/extension-tooling/php-warning-filter' );

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
	const log = await readPhpDebugLog( request, {
		queryParameter: 'e2e_log_marker',
		logPrefix: 'e2e-log-marker-',
	} );

	const offending = filterPhpWarnings( log, PLUGIN_PATHS );

	expect( offending ).toEqual( [] );
} );
