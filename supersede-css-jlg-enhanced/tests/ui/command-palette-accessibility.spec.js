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

  test('supports keyboard navigation across the results', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const adminUrl = new URL(ADMIN_PATH, baseURL).toString();

    await authenticate(page, adminUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    const commandPaletteButton = page.locator('#ssc-cmdk');
    await expect(commandPaletteButton).toBeVisible();
    await commandPaletteButton.click();

    const searchInput = page.locator('#ssc-cmdp-search');
    await expect(searchInput).toBeVisible();
    await expect(searchInput).toBeFocused();

    const resultsList = page.locator('#ssc-cmdp-results');
    const options = resultsList.locator('[role="option"]');
    await expect(options.first()).toBeVisible();
    const optionCount = await options.count();
    expect(optionCount).toBeGreaterThanOrEqual(2);

    await searchInput.press('ArrowDown');
    const firstOption = options.first();
    const firstId = await firstOption.getAttribute('id');
    expect(firstId).not.toBeNull();
    await expect(firstOption).toHaveAttribute('aria-selected', 'true');
    await expect(resultsList).toHaveAttribute('aria-activedescendant', firstId);
    await expect(searchInput).toBeFocused();

    await searchInput.press('ArrowDown');
    const secondOption = options.nth(1);
    const secondId = await secondOption.getAttribute('id');
    expect(secondId).not.toBeNull();
    await expect(secondOption).toHaveAttribute('aria-selected', 'true');
    await expect(firstOption).toHaveAttribute('aria-selected', 'false');
    await expect(resultsList).toHaveAttribute('aria-activedescendant', secondId);

    await searchInput.press('End');
    const lastOption = options.nth(optionCount - 1);
    const lastId = await lastOption.getAttribute('id');
    expect(lastId).not.toBeNull();
    await expect(lastOption).toHaveAttribute('aria-selected', 'true');
    await expect(resultsList).toHaveAttribute('aria-activedescendant', lastId);

    await searchInput.press('Home');
    await expect(firstOption).toHaveAttribute('aria-selected', 'true');
    await expect(resultsList).toHaveAttribute('aria-activedescendant', firstId);

    await searchInput.press('ArrowUp');
    await expect(firstOption).toHaveAttribute('aria-selected', 'true');
    await expect(resultsList).toHaveAttribute('aria-activedescendant', firstId);
  });
});
