<?php
/**
 * Include the shared PHPStan defaults from the installed package or verified archive.
 *
 * @package WooCommerce Extension Tooling
 */

$tooling_root = getenv( 'EXTENSION_TOOLING_ROOT' );
if ( false === $tooling_root || '' === $tooling_root ) {
	$tooling_root = __DIR__ . '/node_modules/@woocommerce/extension-tooling';
}

return array(
	'includes' => array(
		$tooling_root . '/configs/phpstan.neon',
	),
);
