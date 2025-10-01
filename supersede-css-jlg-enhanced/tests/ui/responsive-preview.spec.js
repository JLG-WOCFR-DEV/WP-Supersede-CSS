const { test, expect } = require('@playwright/test');
const { runWpEnv } = require('./utils/wp-env');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-utilities';
const DEFAULT_USERNAME = process.env.WP_USERNAME || 'admin';
const DEFAULT_PASSWORD = process.env.WP_PASSWORD || 'password';

function getAdminUtilitiesUrl(testInfo) {
  const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
  return new URL(ADMIN_PATH, baseURL).toString();
}

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

test.describe('Responsive preview breakpoints', () => {
  test.beforeAll(async () => {
    await runWpEnv([
      'run',
      'cli',
      'wp',
      'eval',
      "update_option('ssc_breakpoints', ['desktop' => 900, 'tablet' => 640, 'mobile' => 360], false);",
    ]);
  });

  test.afterAll(async () => {
    await runWpEnv(['run', 'cli', 'wp', 'option', 'delete', 'ssc_breakpoints']);
  });

  test('applies configured breakpoints to preview toggles', async ({ page }, testInfo) => {
    const adminUrl = getAdminUtilitiesUrl(testInfo);

    await authenticate(page, adminUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await page.waitForSelector('.ssc-utilities-wrap');
    await page.waitForFunction(() => {
      return Boolean(
        window.SSC_UTILITIES_DATA &&
          window.SSC_UTILITIES_DATA.breakpoints &&
          typeof window.SSC_UTILITIES_DATA.breakpoints === 'object'
      );
    });

    const localizedBreakpoints = await page.evaluate(() => window.SSC_UTILITIES_DATA.breakpoints);
    expect(localizedBreakpoints.tablet).toBe(640);
    expect(localizedBreakpoints.mobile).toBe(360);

    const previewFrame = page.locator('#ssc-preview-frame');
    await expect(previewFrame).toHaveCSS('max-width', '900px');

    const tabletToggle = page.locator('.ssc-responsive-toggles button[data-vp="tablet"]');
    const mobileToggle = page.locator('.ssc-responsive-toggles button[data-vp="mobile"]');
    const desktopToggle = page.locator('.ssc-responsive-toggles button[data-vp="desktop"]');

    await tabletToggle.click();
    await expect(previewFrame).toHaveCSS('max-width', '640px');

    await mobileToggle.click();
    await expect(previewFrame).toHaveCSS('max-width', '360px');

    await desktopToggle.click();
    await expect(previewFrame).toHaveCSS('max-width', '900px');
  });
});
