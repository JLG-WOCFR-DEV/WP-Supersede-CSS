const { test, expect } = require('@playwright/test');
const { authenticate, getAdminUrl } = require('./utils/auth');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-effects';

async function waitForVisualEffectsReady(page) {
  await page.waitForSelector('.ssc-ve-tabs');
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

test.describe('Visual effects studio', () => {
  test('supports tab navigation, live previews and ECG preset application', async ({ page }, testInfo) => {
    const adminUrl = getAdminUrl(testInfo, ADMIN_PATH);
    await authenticate(page, adminUrl);
    await waitForVisualEffectsReady(page);

    const backgroundsTab = page.locator('#ssc-ve-tab-backgrounds');
    const backgroundsPanel = page.locator('#ssc-ve-panel-backgrounds');
    await expect(backgroundsTab).toHaveAttribute('aria-selected', 'true');
    await expect(backgroundsPanel).toHaveClass(/active/);

    const crtTab = page.locator('#ssc-ve-tab-crt');
    await crtTab.click();
    const crtPanel = page.locator('#ssc-ve-panel-crt');
    await expect(crtTab).toHaveAttribute('aria-selected', 'true');
    await expect(crtPanel).toBeVisible();
    await page.waitForFunction(() => {
      const canvas = document.getElementById('ssc-crt-canvas');
      if (!canvas) {
        return false;
      }
      const rect = canvas.getBoundingClientRect();
      return rect.width > 0 && rect.height > 0;
    });

    const ecgTab = page.locator('#ssc-ve-tab-ecg');
    await ecgTab.click();
    const ecgPanel = page.locator('#ssc-ve-panel-ecg');
    await expect(ecgTab).toHaveAttribute('aria-selected', 'true');
    await expect(ecgPanel).toBeVisible();

    const presetSelect = page.locator('#ssc-ecg-preset');
    await presetSelect.selectOption('fast');
    const cssOutput = page.locator('#ssc-ecg-css');
    await expect(cssOutput).toContainText('animation:ssc-ecg-line 1.2s');

    const previewPath = page.locator('#ssc-ecg-preview-path');
    await expect(previewPath).toHaveAttribute('d', /L60,30/);

    await page.route('**/wp-json/ssc/v1/save-css', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true }),
      });
    });

    const applyButton = page.locator('#ssc-ecg-apply');
    const toastPromise = page.waitForSelector('.ssc-toast', { state: 'attached' });
    await applyButton.click();
    await toastPromise;
    await expect(page.locator('.ssc-toast').last()).toHaveText('Effet ECG appliqué !');
    await page.unroute('**/wp-json/ssc/v1/save-css');
  });
});
