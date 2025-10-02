const { test, expect } = require('@playwright/test');
const { authenticate, getAdminUrl } = require('./utils/auth');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-utilities';

async function waitForUtilitiesReady(page) {
  await page.waitForSelector('.ssc-editor-tabs');
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

test.describe('CSS utilities workspace', () => {
  test('supports CodeMirror editing and responsive preview controls', async ({ page }, testInfo) => {
    const adminUrl = getAdminUrl(testInfo, ADMIN_PATH);
    await authenticate(page, adminUrl);
    await waitForUtilitiesReady(page);

    const desktopEditor = page.locator('#ssc-editor-panel-desktop .CodeMirror');
    await desktopEditor.click({ position: { x: 10, y: 10 } });
    await page.keyboard.type('.hero { color: red; }');
    await page.waitForFunction(() => {
      const editor = document.querySelector('#ssc-editor-panel-desktop .CodeMirror');
      return editor && editor.CodeMirror && editor.CodeMirror.getValue().includes('color: red');
    });

    const tabletTab = page.locator('#ssc-editor-tab-tablet');
    await tabletTab.click();
    await expect(tabletTab).toHaveAttribute('aria-selected', 'true');
    const tabletEditor = page.locator('#ssc-editor-panel-tablet .CodeMirror');
    await tabletEditor.click({ position: { x: 10, y: 10 } });
    await page.keyboard.type('.hero { font-size: 24px; }');
    await page.waitForFunction(() => {
      const editor = document.querySelector('#ssc-editor-panel-tablet .CodeMirror');
      return editor && editor.CodeMirror && editor.CodeMirror.getValue().includes('font-size: 24px');
    });

    const previewFrame = page.locator('#ssc-preview-frame');
    await page.waitForFunction(() => {
      const frame = document.getElementById('ssc-preview-frame');
      return frame && typeof frame.getAttribute('src') === 'string' && frame.getAttribute('src') !== '';
    });
    const previewUrl = await previewFrame.getAttribute('src');
    await expect(page.locator('#ssc-preview-url')).toHaveValue(previewUrl || '');

    await page.locator('.ssc-responsive-toggles button[data-vp="mobile"]').click();
    await expect(previewFrame).toHaveCSS('max-width', '375px');

    await page.setViewportSize({ width: 900, height: 720 });
    await page.waitForFunction(() => {
      const toggle = document.getElementById('ssc-preview-toggle');
      return toggle && toggle.getAttribute('aria-expanded') === 'false';
    });

    const previewColumn = page.locator('#ssc-preview-column');
    await expect(previewColumn).toHaveClass(/is-hidden/);
    await page.locator('#ssc-preview-toggle').click();
    await expect(page.locator('#ssc-preview-toggle')).toHaveAttribute('aria-expanded', 'true');
    await expect(previewColumn).not.toHaveClass(/is-hidden/);
  });
});
