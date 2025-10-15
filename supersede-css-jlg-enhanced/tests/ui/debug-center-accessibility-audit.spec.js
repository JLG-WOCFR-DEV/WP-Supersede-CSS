const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

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

test.describe('Debug Center accessibility audit', () => {
  test('surface has no serious or critical Axe violations', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const debugCenterUrl = new URL(DEBUG_CENTER_PATH, baseURL).toString();

    await page.setViewportSize({ width: 1280, height: 900 });

    await authenticate(page, debugCenterUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await page.waitForSelector('.ssc-debug-center');

    const axe = new AxeBuilder({ page })
      .include('.ssc-debug-center')
      .withTags(['wcag2a', 'wcag2aa']);

    const results = await axe.analyze();

    const seriousViolations = results.violations.filter((violation) =>
      ['serious', 'critical'].includes(violation.impact)
    );

    const report = seriousViolations.map((violation) => ({
      id: violation.id,
      impact: violation.impact,
      description: violation.description,
      targets: violation.nodes.flatMap((node) => node.target),
    }));

    expect(report).toEqual([]);
  });
});
