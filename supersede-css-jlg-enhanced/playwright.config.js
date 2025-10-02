const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.WP_BASE_URL || 'http://localhost:8889';

module.exports = defineConfig({
  testDir: './tests/ui',
  timeout: 120000,
  expect: {
    timeout: 10000,
  },
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  globalSetup: require.resolve('./tests/ui/global-setup.js'),
  globalTeardown: require.resolve('./tests/ui/global-teardown.js'),
  use: {
    headless: true,
    locale: 'en-US',
    baseURL,
  },
  // Suite names double as CLI filters. Run a specific suite with:
  // `npx playwright test --project=<suite-name>`
  projects: [
    {
      name: 'chromium-accessibility',
      testMatch: /.*accessibility\.spec\.js$/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'chromium-tokens',
      testMatch: /tokens\.spec\.js$/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'chromium-visual-effects',
      testMatch: /visual-effects\.spec\.js$/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'chromium-import-export',
      testMatch: /import-export\.spec\.js$/,
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'chromium-css-utilities',
      testMatch: /css-utilities\.spec\.js$/,
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  reporter: [['list']],
});
