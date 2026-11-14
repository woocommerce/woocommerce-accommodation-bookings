#!/usr/bin/env bash

if [[ ${RUN_UNIT_TESTS} == 0 ]]; then
	exit 0
fi

if [ $# -lt 3 ]; then
	echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [wc-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
WC_VERSION=${6-latest}

TESTS_LIB_DIR="${WP_TESTS_DIR-/tmp/wordpress-tests-lib}"
TESTS_WP_DIR="${WP_CORE_DIR-/tmp/wordpress}"
TESTS_WC_DIR="${WC_CORE_DIR-/tmp/woocommerce}"

TESTS_DIR="$TESTS_WP_DIR/.."

echo $TESTS_LIB_DIR
echo $TESTS_WP_DIR
echo $TESTS_WC_DIR
echo $TESTS_DIR

download() {
	if [ `which curl` ]; then
		curl --fail -s "$1" > "$2";
	elif [ `which wget` ]; then
		wget -nv -O "$2" "$1"
	fi
}

if [ -z $WC_VERSION ] || [ $WC_VERSION == 'latest' ]; then
	echo "WooCommerce version not provided. Fetching latest tag from org..."
	download https://api.wordpress.org/plugins/info/1.0/woocommerce.json ~/woocommerce.json
	WC_VERSION=$(grep -o '"version":"[^"]*' ~/woocommerce.json | sed 's/"version":"//')
	echo "WooCommerce version fetched: $WC_VERSION"
fi

# Install exactly the requested ref: a tag (x.y.z, x.y.z-rc.N, nightly) or the trunk branch.
# Never fall back to another version, so a matrix row always tests what its name says.
if [[ $WC_VERSION == 'trunk' ]]; then
	latestTag=trunk
elif [[ -n $WC_VERSION ]] && git ls-remote --exit-code --refs --tags https://github.com/woocommerce/woocommerce "refs/tags/$WC_VERSION" > /dev/null; then
	latestTag=$WC_VERSION
else
	echo "Error: WooCommerce version '$WC_VERSION' has no matching tag in woocommerce/woocommerce." >&2
	exit 1
fi

# Only need to check normal versions. Betas and rcs are not supported.
if [[ $WP_VERSION =~ ^[0-9]+(\.[0-9]+)*$ ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
elif [[ $WP_VERSION == 'nightly' ]]; then
	WP_TESTS_TAG="trunk"
else
	if command -v curl >/dev/null 2>&1; then
		WP_VERSION_RESPONSE=$(curl --fail --silent --show-error --proto '=https' https://api.wordpress.org/core/version-check/1.7/) || exit 1
	elif command -v wget >/dev/null 2>&1; then
		WP_VERSION_RESPONSE=$(wget -nv --max-redirect=0 -O - https://api.wordpress.org/core/version-check/1.7/) || exit 1
	else
		echo "WordPress version lookup requires curl or wget"
		exit 1
	fi

	# PHP variables must reach the parser without shell expansion.
	# shellcheck disable=SC2016
	LATEST_VERSION=$(printf '%s' "$WP_VERSION_RESPONSE" | php -r '
		$response = json_decode(stream_get_contents(STDIN), true);
		$version = $response["offers"][0]["version"] ?? null;
		if (!is_string($version)) { exit(1); }
		echo $version;
	')
	if [[ ! "$LATEST_VERSION" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set -ex

install_wp() {

	if [ -d $TESTS_WP_DIR ]; then
		rm -rf $TESTS_WP_DIR
	fi

	mkdir -p $TESTS_WP_DIR

	if [[ $WP_VERSION == 'nightly' ]]; then
		download https://wordpress.org/nightly-builds/wordpress-latest.zip ~/wordpress-nightly.zip
		unzip -q ~/wordpress-nightly.zip -d ~/wordpress-nightly
		cp -a ~/wordpress-nightly/wordpress/. $TESTS_WP_DIR
		rm -rf ~/wordpress-nightly ~/wordpress-nightly.zip
	else
		if [ $WP_VERSION == 'latest' ]; then
			local TAR_FILE='https://wordpress.org/latest.tar.gz'
		else
			local TAR_FILE="https://wordpress.org/wordpress-$WP_VERSION.tar.gz"
		fi

		# --fail makes a missing version exit here instead of untarring an error page.
		download $TAR_FILE ~/wordpress.tar.gz
		tar --strip-components=1 -zxmf ~/wordpress.tar.gz -C $TESTS_WP_DIR
	fi

	if [ ! -d $TESTS_WP_DIR/wp-content/uploads ]; then
		mkdir -p $TESTS_WP_DIR/wp-content/uploads
	fi
}

# Escapes a value for a PHP single-quoted string (backslash, quote), then for a sed `s|...|...|` replacement (backslash, &, |).
sed_php_value() {
	printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e "s/'/\\\\'/g" -e 's/[\\&|]/\\&/g'
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	if [ -d $TESTS_LIB_DIR ]; then
		rm -rf $TESTS_LIB_DIR
	fi

	# set up testing suite if it doesn't yet exist
	if [ ! -d $TESTS_LIB_DIR ]; then
		# set up testing suite
		mkdir -p $TESTS_LIB_DIR
		svn co --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $TESTS_LIB_DIR/includes
		svn co --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $TESTS_LIB_DIR/data
	fi

	cd $TESTS_LIB_DIR

	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$TESTS_LIB_DIR"/wp-tests-config.php
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$TESTS_WP_DIR/':" "$TESTS_LIB_DIR"/wp-tests-config.php
		sed $ioption "s|youremptytestdbnamehere|$(sed_php_value "$DB_NAME")|" "$TESTS_LIB_DIR"/wp-tests-config.php
		sed $ioption "s|yourusernamehere|$(sed_php_value "$DB_USER")|" "$TESTS_LIB_DIR"/wp-tests-config.php
		sed $ioption "s|yourpasswordhere|$(sed_php_value "$DB_PASS")|" "$TESTS_LIB_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|$(sed_php_value "$DB_HOST")|" "$TESTS_LIB_DIR"/wp-tests-config.php
	fi

}

install_db() {
	# parse DB_HOST for port or socket references
	if [[ "$DB_HOST" == :* ]]; then
		# It's a socket, e.g. ':/path/to/socket'.
		local DB_HOSTNAME=""
		local DB_SOCK_OR_PORT=$(echo "$DB_HOST" | cut -d':' -f2-)
	elif [[ "$DB_HOST" == *:* ]]; then
		# It's a URL, e.g. 'localhost:3306' or 'http://localhost:3306'.
		local DB_HOSTNAME=$(echo "$DB_HOST" | rev | cut -d':' -f2- | rev) # everything before the last colon
		local DB_SOCK_OR_PORT=$(echo "$DB_HOST" | rev | cut -d':' -f1 | rev) # everything after the last colon
	else
		# It's a hostname, e.g. 'localhost'.
		local DB_HOSTNAME=$DB_HOST
		local DB_SOCK_OR_PORT=""
	fi

	local EXTRA=""

	if [[ -n $DB_SOCK_OR_PORT && ! $DB_SOCK_OR_PORT =~ ^[0-9]+$ ]]; then
		EXTRA=" --socket=$DB_SOCK_OR_PORT"
	elif [[ -n $DB_HOSTNAME && -n $DB_SOCK_OR_PORT ]]; then
		EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
	elif [[ -n $DB_HOSTNAME ]]; then
		EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
	fi

	# Drop target database if already present.
	echo "drop database if exists ${DB_NAME}" | mysql --user="${DB_USER}" --password="${DB_PASS}"${EXTRA}

	# Create database.
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA || true

	# Get the current PHP version
	php_version=$(php -r 'echo PHP_VERSION;')

	# For PHP version 7.0, change the default mysql authentication plugin to "mysql_native_password"
	# as "caching_sha2_password" is not supported
	# This is not needed if using MySQL server from LocalWP (and it will fail)
	if [[ "$php_version" == *"7.0"* ]]; then

		# Update mysql configuration file.
		echo "[mysqld]" | sudo tee -a /etc/mysql/my.cnf
		echo "default_authentication_plugin=mysql_native_password" | sudo tee -a /etc/mysql/my.cnf

		echo "Restarting MYSQL server..."
		sudo systemctl restart mysql.service

		# Update root user.
		echo "ALTER USER '${DB_USER}'@'$DB_HOSTNAME' IDENTIFIED WITH mysql_native_password BY '${DB_PASS}'" | mysql --user="${DB_USER}" --password="${DB_PASS}"

		# Debug statement
		# echo "SELECT @@GLOBAL.default_authentication_plugin" | mysql --user=${DB_USER} --password=${DB_PASS}
	fi
}

install_woocommerce() {

	if [ -d $TESTS_WC_DIR ]; then
		rm -rf $TESTS_WC_DIR
	fi

	cd $TESTS_DIR

	if [ -e wc-tmp ]; then
		rm -rf wc-tmp
	fi

	if [ -d wc-tmp-monorepo ]; then
		rm -rf wc-tmp-monorepo
	fi

	git clone --depth=1 --branch=$latestTag https://github.com/woocommerce/woocommerce wc-tmp-monorepo
	ln -s "$TESTS_DIR/wc-tmp-monorepo/plugins/woocommerce" "$TESTS_DIR/wc-tmp"
	cd wc-tmp-monorepo/plugins/woocommerce

	composer install --no-dev --no-scripts --optimize-autoloader

	# Load WooCommerce test helpers without installing its development dependencies.
	composer dump-autoload --dev --no-scripts --optimize

	php bin/generate-feature-config.php

	cd $TESTS_DIR
	mv wc-tmp $TESTS_WC_DIR
}

print_versions() {
	set +x
	echo "Installed WordPress: $(php -r 'require $argv[1]; echo $wp_version;' "$TESTS_WP_DIR/wp-includes/version.php") (requested: $WP_VERSION)"
	echo "Installed WooCommerce: $(awk '/^[[:space:]]*\*[[:space:]]*Version:/{print $NF; exit}' "$TESTS_WC_DIR/woocommerce.php") (requested: $WC_VERSION, ref: $latestTag)"
}

install_wp
install_test_suite
install_db
install_woocommerce
print_versions
