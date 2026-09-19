# PHP checks

Install dependencies with `composer install` and
`composer --working-dir=tools/php-quality install` using PHP 8.4.
Run `composer check:php` for the PHP syntax and PHPCS checks used on every PR.
Owned production and authored test PHP are included; vendor code, generated files
and build tools are excluded. PHP compatibility remains a separate check.

`composer lint:phpcs` shows the full inventory. The baseline compares findings by
file, line, sniff, message and severity; new errors and warnings fail the gate.
Run `composer lint:phpcs:baseline:update` only for a reviewed baseline change.
Do not regenerate it to hide new findings.

### Plugin Check

CI uses the pinned `wordpress/plugin-check-action` with warnings enabled and
`strict: true`, so every reported error or warning fails. It checks owned
production source; dependency, development, test and generated files are excluded
in `.github/workflows/plugin-check.yml`.

The workflow lists specific ignored codes for existing findings and established
WooCommerce names and behavior. These exclusions also hide future occurrences of
the same codes; review them when changing affected code. No whole check category
is disabled. The action's result artifact contains all remaining findings.
