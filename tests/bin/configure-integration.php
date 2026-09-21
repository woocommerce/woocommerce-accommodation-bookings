<?php
/**
 * Create the integration database and WordPress test configuration.
 *
 * Called by setup-integration.sh after it validates the environment.
 *
 * @package WooCommerceAccommodationBookings\Tests
 */

// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_report -- WordPress is not installed yet.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

$host = explode( ':', getenv( 'ACCOM_TEST_DB_HOST' ), 2 );
// phpcs:ignore WordPress.DB.RestrictedClasses.mysql__mysqli -- Create the database before WordPress can connect to it.
$database = new mysqli(
	$host[0],
	getenv( 'ACCOM_TEST_DB_USER' ),
	getenv( 'ACCOM_TEST_DB_PASSWORD' ),
	'',
	isset( $host[1] ) ? (int) $host[1] : 3306
);
$database->query( 'CREATE DATABASE IF NOT EXISTS `' . getenv( 'ACCOM_TEST_DB_NAME' ) . '`' );

$constants = array(
	'ABSPATH'         => getenv( 'ACCOM_TEST_ROOT' ) . '/wordpress/',
	'DB_NAME'         => getenv( 'ACCOM_TEST_DB_NAME' ),
	'DB_USER'         => getenv( 'ACCOM_TEST_DB_USER' ),
	'DB_PASSWORD'     => getenv( 'ACCOM_TEST_DB_PASSWORD' ),
	'DB_HOST'         => getenv( 'ACCOM_TEST_DB_HOST' ),
	'DB_CHARSET'      => 'utf8mb4',
	'DB_COLLATE'      => '',
	'WP_TESTS_DOMAIN' => 'example.test',
	'WP_TESTS_EMAIL'  => 'admin@example.test',
	'WP_TESTS_TITLE'  => 'Accommodation tests',
);

$config = "<?php\n";
foreach ( $constants as $key => $value ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Quote values for generated PHP, including database credentials.
	$config .= 'define( ' . var_export( $key, true ) . ', ' . var_export( $value, true ) . " );\n";
}
$config .= "define( 'WP_PHP_BINARY', PHP_BINARY );\n";
$config .= "\$table_prefix = 'accommodation_test_';\n";

$config_path = getenv( 'ACCOM_TEST_ROOT' ) . '/wordpress-tests-lib/wp-tests-config.php';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem is unavailable before WordPress boots.
file_put_contents( $config_path, $config );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Keep the generated credentials readable only by their owner.
chmod( $config_path, 0600 );
