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
			// Preserve existing hook payloads, jQuery bindings and helper names.
			camelcase: [
				'error',
				{
					allow: [
						'get_booking_form',
						'get_jquery_element',
						'get_selected_date_type',
						'is_product_type_accommodation_booking',
						'booking_data',
						'custom_data',
						'date_picker',
						'resource_id',
						'$date_picker',
						'$booking_form',
						'data_content',
						'date_type',
						'next_date_type',
						'wc_accommodation_bookings_trigger_change_events',
					],
				},
			],
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
