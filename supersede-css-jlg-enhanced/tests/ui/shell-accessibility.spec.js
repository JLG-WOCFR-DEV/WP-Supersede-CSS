const { test, expect } = require('@playwright/test');

const ADMIN_SHELL_PATH = '/wp-admin/admin.php?page=supersede-css-jlg';
const DEBUG_CENTER_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-debug-center';
const DEFAULT_USERNAME = process.env.WP_USERNAME || 'admin';
const DEFAULT_PASSWORD = process.env.WP_PASSWORD || 'password';

async function authenticate(page, adminUrl, credentials) {
  const loginUrl = `/wp-login.php?redirect_to=${encodeURIComponent(adminUrl)}`;
  await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });

  const usernameInput = page.locator('#user_login');
  if (await usernameInput.isVisible()) {
    await usernameInput.fill(credentials.username);
    await page.locator('#user_pass').fill(credentials.password);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.locator('#wp-submit').click(),
    ]);
  }

  await page.goto(adminUrl, { waitUntil: 'networkidle' });
}

test.describe('Supersede CSS shell accessibility', () => {
  test('mobile sidebar toggle updates ARIA attributes in narrow viewport', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const adminShellUrl = new URL(ADMIN_SHELL_PATH, baseURL).toString();

    await page.setViewportSize({ width: 480, height: 900 });

    await authenticate(page, adminShellUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await page.waitForSelector('#ssc-mobile-menu');
    await page.waitForSelector('#ssc-sidebar');

    const mobileMenuButton = page.locator('#ssc-mobile-menu');
    const sidebar = page.locator('#ssc-sidebar');

    await expect(mobileMenuButton).toBeVisible();
    await expect.poll(async () => mobileMenuButton.getAttribute('aria-expanded')).toBe('false');
    await expect.poll(async () => sidebar.getAttribute('aria-hidden')).toBe('true');

    await mobileMenuButton.click();

    await expect.poll(async () => mobileMenuButton.getAttribute('aria-expanded')).toBe('true');
    await expect.poll(async () => sidebar.getAttribute('aria-hidden')).toBeNull();

    await mobileMenuButton.click();

    await expect.poll(async () => mobileMenuButton.getAttribute('aria-expanded')).toBe('false');
    await expect.poll(async () => sidebar.getAttribute('aria-hidden')).toBe('true');
  });

  test('command palette hides background content and closes on Escape', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const adminShellUrl = new URL(ADMIN_SHELL_PATH, baseURL).toString();

    await page.setViewportSize({ width: 1280, height: 900 });

    await authenticate(page, adminShellUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await page.waitForSelector('#ssc-cmdk');
    await page.waitForSelector('#ssc-cmdp');
    await page.waitForSelector('body > #wpwrap');

    const paletteTrigger = page.locator('#ssc-cmdk');
    const palette = page.locator('#ssc-cmdp');
    const background = page.locator('body > #wpwrap');

    await expect(palette).toBeVisible();
    await expect.poll(async () => palette.getAttribute('aria-hidden')).toBe('true');

    await paletteTrigger.click();

    await expect.poll(async () => palette.getAttribute('aria-hidden')).toBe('false');
    await expect.poll(async () => background.getAttribute('aria-hidden')).toBe('true');
    await expect.poll(async () => background.getAttribute('inert')).toBe('');

    await page.keyboard.press('Escape');

    await expect.poll(async () => palette.getAttribute('aria-hidden')).toBe('true');
    await expect.poll(async () => background.getAttribute('aria-hidden')).toBeNull();
    await expect.poll(async () => background.getAttribute('inert')).toBeNull();
  });

  test('debug center shows localized health check messaging', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const debugCenterUrl = new URL(DEBUG_CENTER_PATH, baseURL).toString();

    await page.setViewportSize({ width: 1280, height: 900 });

    await authenticate(page, debugCenterUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await page.route('**/wp-json/ssc/v1/health**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok' }),
      });
    });

    await page.waitForSelector('#ssc-health-run');
    await page.waitForSelector('#ssc-health-json');

    const runningMessage = await page.evaluate(() => window.sscDebugCenterL10n?.strings?.healthCheckRunningMessage || '');
    expect(runningMessage).not.toEqual('');

    const checkingLabel = await page.evaluate(() => window.sscDebugCenterL10n?.strings?.healthCheckCheckingLabel || '');
    expect(checkingLabel).not.toEqual('');

    const button = page.locator('#ssc-health-run');
    await button.click();

    await expect(button).toHaveText(checkingLabel);

    const resultPane = page.locator('#ssc-health-json');
    await expect(resultPane).toContainText(runningMessage);
  });
});
