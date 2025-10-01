const { test, expect } = require('@playwright/test');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg';
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

test.describe('Command palette accessibility', () => {
  test('search input exposes translated accessible name', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const adminUrl = new URL(ADMIN_PATH, baseURL).toString();

    await authenticate(page, adminUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await page.waitForFunction(
      () => Boolean(window.SSC && window.SSC.i18n && window.SSC.i18n.commandPaletteSearchLabel)
    );
    const expectedLabel = await page.evaluate(() => window.SSC.i18n.commandPaletteSearchLabel);

    const commandPaletteButton = page.locator('#ssc-cmdk');
    await expect(commandPaletteButton).toBeVisible();
    await commandPaletteButton.click();

    const searchInput = page.locator('#ssc-cmdp-search');
    await expect(searchInput).toBeVisible();
    await expect(searchInput).toHaveAccessibleName(expectedLabel);
  });
});
