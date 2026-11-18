const path = require( 'node:path' );
const writeE2ESiteConfig = require( '@woocommerce/extension-tooling/e2e-site-config' );

writeE2ESiteConfig( {
	root: path.resolve( __dirname, '..' ),
	pluginPath: './deploy/e2e-plugin/woocommerce-accommodation-bookings',
	configure( config ) {
		// The seed installs Bookings and Bookings Availability from these ZIPs.
		config.mappings[ 'wp-content/e2e-packages' ] =
			'./deploy/e2e-dependencies';
	},
} );
