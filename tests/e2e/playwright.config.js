const { defineConfig, devices } = require( '@playwright/test' );
const playwrightDefaults = require( '@woocommerce/extension-tooling/playwright-defaults' );

const { baseUrl } = require( './config' );

const defaults = playwrightDefaults( {
	htmlReport: './test-results/playwright-report',
} );

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig( defaults, {
	testDir: '../../tests',
	testMatch: process.env.ACCOM_E2E_REGRESSION
		? '**/{persistence,admin-save-preservation}.spec.js'
		: '**/*.spec.js',

	/* Maximum time one test can run for. */
	timeout: 90 * 1000,

	/* Folder for test artifacts such as screenshots, videos, traces, etc. */
	outputDir: './test-results/report',

	/* Run tests in files in parallel */
	fullyParallel: false,

	workers: 1,

	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		/* Base URL to use in actions like `await page.goto('/')`. */
		baseURL: baseUrl,

		stateDir: './tests/e2e/test-results/storage/',

		// Record video only when retrying a test for the first time.
		video: 'on-first-retry',

		// Viewport used for all pages in the context.
		viewport: { width: 1280, height: 720 },
	},

	/* Configure projects for major browsers */
	projects: [
		{
			name: 'chromium',
			/* warnings.spec.js runs only in the `warnings` project, after every other spec. */
			testIgnore: '**/warnings.spec.js',
			teardown: 'warnings',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
		{ name: 'warnings', testMatch: '**/warnings.spec.js' },
	],

	// path to the global setup files.
	globalSetup: process.env.ACCOM_E2E_REGRESSION
		? undefined
		: require.resolve( './global-setup' ),
} );
