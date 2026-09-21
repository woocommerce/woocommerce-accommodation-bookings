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

test_directory="$1"
wp_version="$2"
wc_version="$3"
bookings_version="$4"
addons_version="$5"
script_directory="$(cd "$(dirname "$0")" && pwd)"

: "${ACCOM_TEST_DB_NAME:?Set an isolated accommodation_test_* database name}"
: "${ACCOM_TEST_DB_USER:?Set the local test database user}"
: "${ACCOM_TEST_DB_PASSWORD?Set the local test database password (may be empty)}"
export ACCOM_TEST_DB_HOST="${ACCOM_TEST_DB_HOST:-127.0.0.1}"

if [[ ! "$ACCOM_TEST_DB_NAME" =~ ^accommodation_test_[a-zA-Z0-9_]+$ ]]; then
  echo "Refusing a database name outside accommodation_test_*" >&2
  exit 1
fi

for required_command in php curl svn unzip gh; do
  if ! command -v "$required_command" >/dev/null; then
    echo "Missing required command: $required_command" >&2
    exit 1
  fi
done

for version in "$wp_version" "$wc_version" "$bookings_version" "$addons_version"; do
  if [[ ! "$version" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
    echo "Use explicit release versions." >&2
    exit 1
  fi
done

mkdir -p "$test_directory"
export ACCOM_TEST_ROOT="$(cd "$test_directory" && pwd)"

# Never silently reuse a different dependency combination or an unrelated directory.
if [ -n "$(ls -A "$ACCOM_TEST_ROOT")" ]; then
  echo "Choose an empty test directory; use the run script to reset fixtures." >&2
  exit 1
fi
cd "$ACCOM_TEST_ROOT"

curl --fail --location --silent --show-error "https://wordpress.org/wordpress-$wp_version.tar.gz" | tar -xz
mkdir wordpress-tests-lib
svn export --quiet "https://develop.svn.wordpress.org/tags/$wp_version/tests/phpunit/includes" wordpress-tests-lib/includes
svn export --quiet "https://develop.svn.wordpress.org/tags/$wp_version/tests/phpunit/data" wordpress-tests-lib/data

mkdir -p dependencies
curl --fail --location --silent --show-error "https://downloads.wordpress.org/plugin/woocommerce.$wc_version.zip" -o dependencies/woocommerce.zip
gh release download "$bookings_version" --repo woocommerce/woocommerce-bookings --pattern woocommerce-bookings.zip --dir dependencies
gh release download "$addons_version" --repo woocommerce/woocommerce-product-addons --pattern woocommerce-product-addons.zip --dir dependencies

for archive in dependencies/*.zip; do
  unzip -q "$archive" -d dependencies
done

php "$script_directory/configure-integration.php"
printf 'Prepared %s. Run tests/bin/run-integration.sh with this directory.\n' "$ACCOM_TEST_ROOT"
