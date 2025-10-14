const { test, expect } = require('@playwright/test');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-device-lab';
const DEFAULT_USERNAME = process.env.WP_USERNAME || 'admin';
const DEFAULT_PASSWORD = process.env.WP_PASSWORD || 'password';

function getAdminDeviceLabUrl(testInfo) {
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

async function waitForDeviceLabReady(page) {
  await page.waitForSelector('#ssc-device-lab-device');
  await page.waitForFunction(() => {
    return Boolean(
      window.SSC &&
      window.SSC.rest &&
      window.SSC.rest.root &&
      window.SSC.rest.nonce &&
      window.SSC_DEVICE_LAB
    );
  });

  return page.evaluate(() => ({
    restRoot: window.SSC.rest.root,
    nonce: window.SSC.rest.nonce,
  }));
}

test.describe('Device Lab preview', () => {
  test('switches devices, applies zoom, loads URLs and injects Supersede CSS', async ({ page }, testInfo) => {
    const adminUrl = getAdminDeviceLabUrl(testInfo);

    await authenticate(page, adminUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    let { restRoot, nonce } = await waitForDeviceLabReady(page);
    const saveCssEndpoint = new URL('save-css', restRoot).toString();

    const cssPayload = [
      ':root { --device-lab-test: #007acc; }',
      'body { background-color: rgb(0, 122, 204) !important; }',
      '.device-lab-css-test { color: rgb(136, 64, 64); }',
    ].join('\n');

    const saveResponse = await page.request.post(saveCssEndpoint, {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        css: cssPayload,
        option_name: 'ssc_tokens_css',
      },
    });

    expect(saveResponse.ok()).toBeTruthy();

    await page.reload({ waitUntil: 'networkidle' });
    ({ restRoot, nonce } = await waitForDeviceLabReady(page));

    const viewport = page.locator('#ssc-device-lab-viewport');
    const wrapper = page.locator('#ssc-device-lab-viewport-wrapper');

    await page.waitForFunction(() => {
      const frame = document.getElementById('ssc-device-lab-frame');
      if (!frame) {
        return false;
      }

      try {
        const doc = frame.contentDocument;
        if (!doc || !doc.body || !doc.defaultView) {
          return false;
        }

        const background = doc.defaultView.getComputedStyle(doc.body).backgroundColor;
        return background === 'rgb(0, 122, 204)';
      } catch (error) {
        return false;
      }
    });

    await page.locator('#ssc-device-lab-device').selectOption('ipad-air');
    await expect(viewport).toHaveAttribute('data-device', 'ipad-air');
    await expect(viewport).toHaveAttribute('data-width', '820');
    await expect(viewport).toHaveAttribute('data-height', '1180');

    const landscapeButton = page.locator('[data-orientation="landscape"]');
    await landscapeButton.click();
    await expect(viewport).toHaveAttribute('data-orientation', 'landscape');
    await expect(viewport).toHaveAttribute('data-width', '1180');
    await expect(viewport).toHaveAttribute('data-height', '820');

    const zoomInput = page.locator('#ssc-device-lab-zoom');
    await zoomInput.evaluate((input) => {
      input.value = '80';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    await expect(wrapper).toHaveAttribute('data-zoom', '80');
    await expect(page.locator('#ssc-device-lab-zoom-display')).toHaveText('80%');

    await page.locator('#ssc-device-lab-url').fill('https://example.com');
    await page.locator('#ssc-device-lab-load').click();
    await expect(page.locator('#ssc-device-lab-frame')).toHaveAttribute('src', 'https://example.com/');
  });
});
