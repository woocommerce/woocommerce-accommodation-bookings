const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { expect } = require( '@playwright/test' );
// wp-env mounts the checkout under its folder name, so this also works in worktrees.
const pluginDir = `wp-content/plugins/${ path.basename(
	path.resolve( __dirname, '../../..' )
) }`;
function wp( ...args ) {
	const native = process.env.ACCOM_E2E_WP_PATH;
	if ( args[ 0 ] === 'eval-file' ) {
		args[ 1 ] = native
			? path.join( native, args[ 1 ] )
			: args[ 1 ].replace(
					`${ pluginDir }/tests/e2e/`,
					'wp-content/e2e-fixtures/e2e/'
				);
	}
	return execFileSync(
		native ? 'wp' : 'pnpm',
		native
			? [ `--path=${ native }`, ...args ]
			: [ '--silent', 'run', 'wp-env:test', 'run', 'cli', 'wp', ...args ],
		{
			cwd: path.resolve( __dirname, '../../..' ),
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		}
	).trim();
}
function read( expression ) {
	return JSON.parse(
		wp( 'eval', `echo "ACCOM_RESULT:" . wp_json_encode(${ expression });` )
			.split( 'ACCOM_RESULT:' )
			.pop()
	);
}
function seed() {
	wp( 'eval-file', `${ pluginDir }/tests/e2e/fixtures/seed.php` );
	return read( 'get_option("accommodation_e2e_fixture")' );
}
async function customerLogin( page ) {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( 'accommodation_customer' );
	await page.locator( '#user_pass' ).fill( 'accommodation-test-password' );
	await page.locator( '#wp-submit' ).click();
	await page.goto( '/my-account/' );
	await expect(
		page.locator(
			'.woocommerce-MyAccount-navigation-link--customer-logout'
		)
	).toBeVisible();
}
function savedStays( product ) {
	return read(
		`array_map(function($id){$b=new WC_Booking($id);$o=wc_get_order($b->get_order_id());return array("id"=>$b->get_id(),"status"=>$b->get_status(),"product"=>$b->get_product_id(),"resource"=>$b->get_resource_id(),"start"=>gmdate("Y-m-d H:i",$b->get_start()),"end"=>gmdate("Y-m-d H:i",$b->get_end()),"cost"=>(float)$b->get_cost(),"order"=>$o?array("id"=>$o->get_id(),"status"=>$o->get_status(),"customer"=>$o->get_customer_id(),"total"=>(float)$o->get_total(),"currency"=>$o->get_currency(),"item"=>$b->get_order_item_id()):null);},get_posts(array("post_type"=>"wc_booking","post_status"=>array_merge(get_wc_booking_statuses(),array("cancelled","was-in-cart")),"numberposts"=>-1,"fields"=>"ids","meta_key"=>"_booking_product_id","meta_value"=>${ Number( product ) })))`
	);
}
module.exports = { pluginDir, wp, read, seed, customerLogin, savedStays };
