import woocommerce from '@woocommerce/eslint-plugin';
import globals from 'globals';
import playwright from 'eslint-plugin-playwright';

export default [
	{
		ignores: [
			'**/build/**',
			'assets/images/**',
			'includes/**',
			'languages/**',
			'**/vendor/**',
			'coverage/**',
			'tests/e2e/test-results/**',
			'test-results/**',
			'playwright-report/**',
			'dist/**',
		],
	},
	// The browser suite uses Playwright, not the preset's Jest/testing-library scopes.
	...woocommerce.configs.recommended.map( ( config ) =>
		config.files?.some( ( pattern ) => pattern.includes( 'tests' ) )
			? {
					...config,
					ignores: [ ...( config.ignores || [] ), 'tests/e2e/**' ],
				}
			: config
	),
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
	{
		files: [ '*.js', '*.mjs', 'tests/e2e/**/*.js' ],
		languageOptions: {
			sourceType: 'commonjs',
			globals: {
				...Object.fromEntries(
					Object.keys( globals.browser ).map( ( name ) => [
						name,
						'off',
					] )
				),
				...globals.node,
				wp: 'off',
				wcSettings: 'off',
				SCRIPT_DEBUG: 'off',
			},
		},
		rules: {
			// Node configurations and Playwright helpers use CommonJS loaders.
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
	{
		// Playwright transpiles this helper's existing mixed module syntax.
		files: [ 'eslint.config.mjs', 'tests/e2e/utils/index.js' ],
		languageOptions: { sourceType: 'module' },
	},
	{
		files: [ 'tests/e2e/**/*.js' ],
		plugins: { playwright },
		rules: {
			'playwright/no-focused-test': 'error',
			'playwright/valid-expect': 'error',
			'playwright/missing-playwright-await': 'error',
		},
	},
	{
		files: [ 'tests/e2e/utils/index.js' ],
		rules: {
			// These exported helpers call other hoisted function declarations.
			'@typescript-eslint/no-use-before-define': [
				'error',
				{ functions: false },
			],
		},
	},
];
