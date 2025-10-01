const { test, expect } = require('@playwright/test');

const ADMIN_SHELL_PATH = '/wp-admin/admin.php?page=supersede-css-jlg';
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
});
