const { defineConfig, devices } = require("@playwright/test");

const { baseUrl } = require("./config");

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: "../../tests",

  /* Maximum time one test can run for. */
  timeout: 90 * 1000,

  expect: {
    /**
     * Maximum time expect() should wait for the condition to be met.
     * For example in `await expect(locator).toHaveText();`
     */
    timeout: 20 * 1000,
  },

  /* Folder for test artifacts such as screenshots, videos, traces, etc. */
  outputDir: "./test-results/report",

  /* Run tests in files in parallel */
  fullyParallel: false,

  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,

  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,

  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 1,

  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ["list"],
    [
      "html",
      {
        outputFolder: "./test-results/playwright-report",
        open: "never",
      },
    ],
  ],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: baseUrl,

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: "retain-on-failure",

    // Capture screenshot after each test failure.
    screenshot: "only-on-failure",

    stateDir: "./tests/e2e/test-results/storage/",

    // Record video only when retrying a test for the first time.
    video: "on-first-retry",

    // Viewport used for all pages in the context.
    viewport: { width: 1280, height: 720 },
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],

  // path to the global setup files.
  globalSetup: require.resolve("./global-setup"),
  globalTeardown: require.resolve("./global-teardown"),
});
