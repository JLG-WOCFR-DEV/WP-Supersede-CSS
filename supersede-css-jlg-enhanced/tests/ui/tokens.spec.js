const { test, expect } = require('@playwright/test');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-tokens';
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

async function waitForPluginReady(page) {
  await page.waitForSelector('#ssc-token-builder');
  await page.waitForSelector('#ssc-tokens-preview-style');
  await page.waitForFunction(() => {
    return Boolean(
      window.SSC &&
        window.SSC.rest &&
        window.SSC.rest.root &&
        window.SSC.rest.nonce &&
        window.SSC_TOKENS_DATA
    );
  });

  return page.evaluate(() => ({
    restRoot: window.SSC.rest.root,
    nonce: window.SSC.rest.nonce,
  }));
}

async function seedTokens(page, tokensEndpoint, nonce, tokens) {
  const response = await page.request.post(tokensEndpoint, {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
    },
    data: { tokens },
  });

  expect(response.ok()).toBeTruthy();
}

test.describe('Token manager admin UI', () => {
  test('adds, edits and deletes tokens with live CSS preview updates', async ({ page }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
    const adminTokensUrl = new URL(ADMIN_PATH, baseURL).toString();

    await authenticate(page, adminTokensUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    let { restRoot, nonce } = await waitForPluginReady(page);
    let tokensEndpoint = new URL('tokens', restRoot).toString();

    const initialTokens = [
      {
        name: '--primary-color',
        value: '#123456',
        type: 'color',
        description: 'Primary brand color',
        group: 'Brand',
      },
    ];

    await seedTokens(page, tokensEndpoint, nonce, initialTokens);

    await page.reload({ waitUntil: 'networkidle' });
    ({ restRoot, nonce } = await waitForPluginReady(page));
    tokensEndpoint = new URL('tokens', restRoot).toString();

    const builder = page.locator('#ssc-token-builder');
    const rows = builder.locator('.ssc-token-row');
    const previewStyle = page.locator('#ssc-tokens-preview-style');
    const cssTextarea = page.locator('#ssc-tokens');

    await expect(rows).toHaveCount(initialTokens.length);
    await expect(previewStyle).toContainText('--primary-color: #123456;');
    let cssValue = await cssTextarea.inputValue();
    expect(cssValue).toContain('--primary-color: #123456;');

    await page.locator('#ssc-token-add').click();
    await expect(rows).toHaveCount(initialTokens.length + 1);

    const lastRow = rows.last();
    await lastRow.locator('.token-name').fill('spacing_small');
    await lastRow.locator('.token-name').blur();
    await expect(lastRow.locator('.token-name')).toHaveValue('--spacing_small');

    await lastRow.locator('.token-type').selectOption('text');
    await expect(lastRow.locator('.token-value')).toBeVisible();
    await lastRow.locator('.token-value').fill('1rem');
    await lastRow.locator('.token-value').blur();

    await expect(previewStyle).toContainText('--spacing_small: 1rem;');
    cssValue = await cssTextarea.inputValue();
    expect(cssValue).toContain('--spacing_small: 1rem;');

    const saveResponse = page.waitForResponse(
      (response) =>
        response.url().startsWith(tokensEndpoint) &&
        response.request().method() === 'POST'
    );
    await page.locator('#ssc-tokens-save').click();
    const saved = await saveResponse;
    expect(saved.ok()).toBeTruthy();

    await expect(rows).toHaveCount(initialTokens.length + 1);

    const updatedRow = rows.last();
    await updatedRow.locator('.token-value').fill('2rem');
    await updatedRow.locator('.token-value').blur();

    await expect(previewStyle).toContainText('--spacing_small: 2rem;');
    cssValue = await cssTextarea.inputValue();
    expect(cssValue).toContain('--spacing_small: 2rem;');

    await updatedRow.locator('.token-delete').click();
    await expect(rows).toHaveCount(initialTokens.length);

    await expect(previewStyle).not.toContainText('--spacing_small');
    cssValue = await cssTextarea.inputValue();
    expect(cssValue).not.toContain('--spacing_small');

    const deleteSave = page.waitForResponse(
      (response) =>
        response.url().startsWith(tokensEndpoint) &&
        response.request().method() === 'POST'
    );
    await page.locator('#ssc-tokens-save').click();
    const deleteResult = await deleteSave;
    expect(deleteResult.ok()).toBeTruthy();

    const finalResponse = await page.request.get(tokensEndpoint, {
      headers: {
        'X-WP-Nonce': nonce,
      },
    });
    expect(finalResponse.ok()).toBeTruthy();
    const finalJson = await finalResponse.json();
    expect(finalJson.tokens).toHaveLength(1);
    expect(finalJson.tokens[0].name).toBe('--primary-color');
    expect(finalJson.tokens[0].value).toBe('#123456');
    expect(finalJson.tokens[0].type).toBe('color');
    expect(finalJson.css).toContain('--primary-color: #123456;');
    expect(finalJson.css).not.toContain('--spacing_small');
  });
});
