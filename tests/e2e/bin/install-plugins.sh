#!/bin/bash

rm -rf ./test-plugins

echo "Downloading WooCommerce Bookings"
gh release download --repo woocommerce/woocommerce-bookings --pattern  woocommerce-bookings.zip --dir ./test-plugins
cd ./test-plugins
unzip -o woocommerce-bookings.zip
cd ..
