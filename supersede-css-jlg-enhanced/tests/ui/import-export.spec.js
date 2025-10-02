const path = require('path');
const { test, expect } = require('@playwright/test');
const { authenticate, getAdminUrl } = require('./utils/auth');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-import';
const SAMPLE_CONFIG_PATH = path.resolve(__dirname, 'fixtures/sample-config.json');

async function waitForImportExportReady(page) {
  await page.waitForSelector('#ssc-export-config');
  await page.waitForFunction(() => {
    return (
      typeof window !== 'undefined' &&
      window.SSC &&
      window.SSC.rest &&
      typeof window.SSC.rest.root === 'string' &&
      typeof window.SSC.rest.nonce === 'string'
    );
  });
}

test.describe('Import / Export workflow', () => {
  test('exports a config, reimports it and surfaces notifications', async ({ page }, testInfo) => {
    const adminUrl = getAdminUrl(testInfo, ADMIN_PATH);
    await authenticate(page, adminUrl);
    await waitForImportExportReady(page);

    await page.route('**/wp-json/ssc/v1/export-config**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ tokens: {}, utilities: {} }),
      });
    });

    const downloadPromise = page.waitForEvent('download');
    const exportToast = page.waitForSelector('.ssc-toast', { state: 'attached' });
    await page.locator('#ssc-export-config').click();
    await downloadPromise;
    await exportToast;
    await expect(page.locator('.ssc-toast').last()).toHaveText('Configuration exportée !');
    await page.unroute('**/wp-json/ssc/v1/export-config**');
    await page.waitForSelector('.ssc-toast', { state: 'detached' });

    await page.route('**/wp-json/ssc/v1/import-config', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ applied: ['tokens', 'utilities'], skipped: [] }),
      });
    });

    await page.setInputFiles('#ssc-import-file', SAMPLE_CONFIG_PATH);
    const importToast = page.waitForSelector('.ssc-toast', { state: 'attached' });
    await page.locator('#ssc-import-btn').click();
    await importToast;

    await expect(page.locator('#ssc-import-msg')).toHaveText('Import terminé : 2 option(s) appliquée(s).');
    await expect(page.locator('.ssc-toast').last()).toHaveText('Configuration importée !');
    await page.unroute('**/wp-json/ssc/v1/import-config');
  });
});
