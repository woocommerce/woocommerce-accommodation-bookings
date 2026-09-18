import woocommerce from '@woocommerce/eslint-plugin';
import globals from 'globals';

export default [
	{
		ignores: [
			'**/build/**',
			'assets/images/**',
			'includes/**',
			'languages/**',
			'vendor/**',
			'tests/**',
			'dist/**',
		],
	},
	...woocommerce.configs.recommended,
	{
		files: [ 'src/js/**/*.js' ],
		languageOptions: {
			globals: { ...globals.browser, wp: 'readonly' },
		},
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'woocommerce-accommodation-bookings' },
			],
			'@wordpress/no-unused-vars-before-return': 'error',
			'@wordpress/dependency-group': 'error',
			'import/no-extraneous-dependencies': 'error',
			'import/no-unresolved': 'error',
			'import/named': 'error',
		},
		settings: {
			jsdoc: { mode: 'typescript' },
			// List of modules that are externals in our webpack config.
			// This helps the `import/no-extraneous-dependencies` and
			//`import/no-unresolved` rules account for them.
			'import/core-modules': [
				'jquery',
				'@woocommerce/blocks-registry',
				'@woocommerce/settings',
				'@wordpress/i18n',
				'@wordpress/element',
				'@wordpress/html-entities',
			],
		},
	},
];
