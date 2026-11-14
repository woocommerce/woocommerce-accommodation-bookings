/**
 * Link Plugins.
 *
 * Generate the Playwright site configuration (.wp-env.e2e.json) from the tests site
 * (.wp-env.test.json): the packaged plugin that bin/e2e.sh unzipped replaces the checkout,
 * the Bookings ZIPs that bin/hooks/e2e-prepare.sh downloaded are mounted for the seed, and
 * WooCommerce, WordPress and PHP follow the requested versions.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const wpEnv = JSON.parse(
	fs.readFileSync( path.join( __dirname, '..', '.wp-env.test.json' ) )
);

// Mount the built plugin extracted by bin/e2e.sh, and a log of its own for the warnings check.
const checkoutIndex = wpEnv.plugins.indexOf( '.' );
if ( checkoutIndex === -1 ) {
	throw new Error( '.wp-env.test.json must mount the checkout as ".".' );
}
wpEnv.plugins[ checkoutIndex ] =
	'./deploy/e2e-plugin/woocommerce-accommodation-bookings';
wpEnv.mappings[ './wp-content/debug.log' ] = './tests/_output/e2e-debug.log';
// tests/e2e/bin/initialize.sh installs Bookings and Bookings Availability from these ZIPs.
wpEnv.mappings[ 'wp-content/e2e-packages' ] = './deploy/e2e-dependencies';

const isVersionNumber = ( str ) => /^\d+(\.\d+)*(-\w+(\.\w+)*)*$/.test( str );

// WooCommerce: latest stable by default, `nightly` or a release tag otherwise.
const WC_VERSION = process.env.WC_VERSION;
const wooPluginIndex = wpEnv.plugins.indexOf(
	'https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip'
);
if ( WC_VERSION === 'nightly' ) {
	wpEnv.plugins[ wooPluginIndex ] =
		'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
} else if ( WC_VERSION && isVersionNumber( WC_VERSION ) ) {
	wpEnv.plugins[ wooPluginIndex ] =
		`https://github.com/woocommerce/woocommerce/releases/download/${ WC_VERSION }/woocommerce.zip`;
}

// WordPress: latest by default, `nightly` or a release otherwise.
const WP_VERSION = process.env.WP_VERSION;
if ( WP_VERSION === 'nightly' ) {
	wpEnv.core = 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
} else if ( WP_VERSION && isVersionNumber( WP_VERSION ) ) {
	wpEnv.core = `https://wordpress.org/wordpress-${ WP_VERSION }.zip`;
}

// PHP: the .wp-env.test.json version unless one is requested.
const PHP_VERSION = process.env.PHP_VERSION;
if ( PHP_VERSION && isVersionNumber( PHP_VERSION ) ) {
	wpEnv.phpVersion = PHP_VERSION;
}

fs.writeFileSync(
	path.join( __dirname, '..', '.wp-env.e2e.json' ),
	JSON.stringify( wpEnv )
);
