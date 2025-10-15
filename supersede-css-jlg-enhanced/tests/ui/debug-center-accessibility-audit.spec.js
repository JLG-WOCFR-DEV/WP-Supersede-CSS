const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const ADMIN_SURFACES = [
  {
    name: 'Debug Center',
    path: '/wp-admin/admin.php?page=supersede-css-jlg-debug-center',
    include: '.ssc-debug-center',
  },
  {
    name: 'CSS Utilities editor',
    path: '/wp-admin/admin.php?page=supersede-css-jlg-utilities',
    include: '.ssc-utilities-wrap',
  },
  {
    name: 'Command palette overlay',
    path: '/wp-admin/admin.php?page=supersede-css-jlg-utilities',
    include: '.ssc-command-palette',
    beforeAnalyze: async (page) => {
      await page.waitForFunction(() => typeof window !== 'undefined' && window.sscCommandPalette && typeof window.sscCommandPalette.open === 'function');
      await page.evaluate(() => {
        window.sscCommandPalette.open();
      });
      await page.waitForSelector('.ssc-command-palette', { state: 'visible' });
      await page.waitForTimeout(100);
    },
  },
];
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

test.describe('Supersede CSS admin accessibility audits', () => {
  for (const surface of ADMIN_SURFACES) {
    test(`${surface.name} surface has no serious Axe violations`, async ({ page }, testInfo) => {
      const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
      const surfaceUrl = new URL(surface.path, baseURL).toString();

      await page.setViewportSize({ width: 1280, height: 900 });

      await authenticate(page, surfaceUrl, {
        username: DEFAULT_USERNAME,
        password: DEFAULT_PASSWORD,
      });

      await page.waitForSelector(surface.include);

      if (typeof surface.beforeAnalyze === 'function') {
        await surface.beforeAnalyze(page);
      }

      const axe = new AxeBuilder({ page })
        .include(surface.include)
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
  }
});
