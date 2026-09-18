#!/usr/bin/env bash
# Provision a dedicated local integration environment; requires PHP/mysqli, curl, svn, unzip and authenticated gh.
set -euo pipefail
# Configuration contains disposable database credentials from the first write.
umask 077
if [ "$#" -ne 5 ]; then
  echo "Usage: $0 <empty-test-directory> <WP-version> <WC-version> <Bookings-version> <Add-ons-version>" >&2
  echo "Set ACCOM_TEST_DB_NAME (accommodation_test_*), ACCOM_TEST_DB_USER, ACCOM_TEST_DB_PASSWORD and ACCOM_TEST_DB_HOST (host[:port])." >&2
  exit 1
fi
: "${ACCOM_TEST_DB_NAME:?Set an isolated accommodation_test_* database name}"
: "${ACCOM_TEST_DB_USER:?Set the local test database user}"
: "${ACCOM_TEST_DB_PASSWORD?Set the local test database password (may be empty)}"
export ACCOM_TEST_DB_HOST="${ACCOM_TEST_DB_HOST:-127.0.0.1}"
[[ "$ACCOM_TEST_DB_NAME" =~ ^accommodation_test_[a-zA-Z0-9_]+$ ]] || { echo "Refusing a database name outside accommodation_test_*" >&2; exit 1; }
for command in php curl svn unzip gh; do
  command -v "$command" >/dev/null || { echo "Missing required command: $command" >&2; exit 1; }
done
for version in "${@:2}"; do [[ "$version" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]] || { echo "Use explicit release versions." >&2; exit 1; }; done
mkdir -p "$1"
export ACCOM_TEST_ROOT="$(cd "$1" && pwd)"
# Never silently reuse a different dependency combination or an unrelated directory.
if [ -n "$(ls -A "$ACCOM_TEST_ROOT")" ]; then echo "Choose an empty test directory; use the run script to reset fixtures." >&2; exit 1; fi
cd "$ACCOM_TEST_ROOT"
curl --fail --location --silent --show-error "https://wordpress.org/wordpress-$2.tar.gz" | tar -xz
mkdir wordpress-tests-lib
svn export --quiet "https://develop.svn.wordpress.org/tags/$2/tests/phpunit/includes" wordpress-tests-lib/includes
svn export --quiet "https://develop.svn.wordpress.org/tags/$2/tests/phpunit/data" wordpress-tests-lib/data
mkdir -p dependencies
curl --fail --location --silent --show-error "https://downloads.wordpress.org/plugin/woocommerce.$3.zip" -o dependencies/woocommerce.zip
gh release download "$4" --repo woocommerce/woocommerce-bookings --pattern woocommerce-bookings.zip --dir dependencies
gh release download "$5" --repo woocommerce/woocommerce-product-addons --pattern woocommerce-product-addons.zip --dir dependencies
for archive in dependencies/*.zip; do unzip -q "$archive" -d dependencies; done
php <<'PHP'
<?php
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$host = explode( ':', getenv( 'ACCOM_TEST_DB_HOST' ), 2 );
$db = new mysqli( $host[0], getenv( 'ACCOM_TEST_DB_USER' ), getenv( 'ACCOM_TEST_DB_PASSWORD' ), '', isset( $host[1] ) ? (int) $host[1] : 3306 );
$db->query( 'CREATE DATABASE IF NOT EXISTS `' . getenv( 'ACCOM_TEST_DB_NAME' ) . '`' );
$config = "<?php\n";
foreach ( array( 'ABSPATH' => getenv( 'ACCOM_TEST_ROOT' ) . '/wordpress/', 'DB_NAME' => getenv( 'ACCOM_TEST_DB_NAME' ), 'DB_USER' => getenv( 'ACCOM_TEST_DB_USER' ), 'DB_PASSWORD' => getenv( 'ACCOM_TEST_DB_PASSWORD' ), 'DB_HOST' => getenv( 'ACCOM_TEST_DB_HOST' ), 'DB_CHARSET' => 'utf8mb4', 'DB_COLLATE' => '', 'WP_TESTS_DOMAIN' => 'example.test', 'WP_TESTS_EMAIL' => 'admin@example.test', 'WP_TESTS_TITLE' => 'Accommodation tests' ) as $key => $value ) {
    $config .= 'define( ' . var_export( $key, true ) . ', ' . var_export( $value, true ) . " );\n";
}
$config .= "define( 'WP_PHP_BINARY', PHP_BINARY );\n";
$config .= "\$table_prefix = 'accommodation_test_';\n";
$path = getenv( 'ACCOM_TEST_ROOT' ) . '/wordpress-tests-lib/wp-tests-config.php';
file_put_contents( $path, $config );
chmod( $path, 0600 );
PHP
printf 'Prepared %s. Run tests/bin/run-integration.sh with this directory.\n' "$ACCOM_TEST_ROOT"
