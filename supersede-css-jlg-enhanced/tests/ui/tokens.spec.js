const { test, expect } = require('@playwright/test');

const ADMIN_PATH = '/wp-admin/admin.php?page=supersede-css-jlg-tokens';
const DEFAULT_USERNAME = process.env.WP_USERNAME || 'admin';
const DEFAULT_PASSWORD = process.env.WP_PASSWORD || 'password';

function getAdminTokensUrl(testInfo) {
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

async function waitForPluginReady(page) {
  await page.waitForSelector('[data-component="tokens"]');
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
    const adminTokensUrl = getAdminTokensUrl(testInfo);

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

    const rows = page.locator('.ssc-token-row');
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

  test('shows localized toast message when copying tokens', async ({ page }, testInfo) => {
    const adminTokensUrl = getAdminTokensUrl(testInfo);

    await authenticate(page, adminTokensUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    await waitForPluginReady(page);

    const expectedMessage = await page.evaluate(() => {
      const data = window.SSC_TOKENS_DATA || {};
      const messages = data.i18n || {};
      return messages.copySuccess || 'Tokens copiés';
    });

    await page.locator('#ssc-tokens-copy').click();

    const toast = page.locator('.ssc-toast').last();
    await expect(toast).toHaveText(expectedMessage);
  });

  test('prevents saving tokens with duplicate names', async ({ page }, testInfo) => {
    const adminTokensUrl = getAdminTokensUrl(testInfo);

    await authenticate(page, adminTokensUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    let { restRoot, nonce } = await waitForPluginReady(page);
    let tokensEndpoint = new URL('tokens', restRoot).toString();

    const baseToken = {
      name: '--primary-color',
      value: '#123456',
      type: 'color',
      description: 'Primary color',
      group: 'Brand',
    };

    await seedTokens(page, tokensEndpoint, nonce, [baseToken]);

    await page.reload({ waitUntil: 'networkidle' });
    ({ restRoot, nonce } = await waitForPluginReady(page));
    tokensEndpoint = new URL('tokens', restRoot).toString();

    const rows = page.locator('.ssc-token-row');

    await expect(rows).toHaveCount(1);

    await page.locator('#ssc-token-add').click();
    await expect(rows).toHaveCount(2);

    const duplicateRow = rows.nth(1);
    await duplicateRow.locator('.token-name').fill('--primary-color');
    await duplicateRow.locator('.token-name').blur();

    const duplicateMessages = await page.evaluate(() => {
      const data = window.SSC_TOKENS_DATA || {};
      const messages = data.i18n || {};
      return {
        duplicateError: messages.duplicateError || 'Certains tokens utilisent le même nom. Corrigez les doublons avant d’enregistrer.',
        duplicateListPrefix: messages.duplicateListPrefix || 'Doublons :',
      };
    });

    await page.evaluate(() => {
      const spoken = [];
      window.__sscTestSpokenMessages = spoken;
      if (!window.wp) {
        window.wp = {};
      }
      if (!window.wp.a11y) {
        window.wp.a11y = {};
      }
      const originalSpeak = typeof window.wp.a11y.speak === 'function' ? window.wp.a11y.speak : null;
      window.wp.a11y.speak = function speakOverride(message, politeness) {
        spoken.push(String(message));
        if (originalSpeak) {
          return originalSpeak.call(this, message, politeness);
        }
        return undefined;
      };
    });

    const observedRequests = [];
    const requestListener = (request) => {
      if (request.url().startsWith(tokensEndpoint) && request.method() === 'POST') {
        observedRequests.push(request);
      }
    };
    page.on('request', requestListener);

    await page.locator('#ssc-tokens-save').click();
    await page.waitForTimeout(750);

    page.off('request', requestListener);
    expect(observedRequests).toHaveLength(0);

    const expectedDuplicateToast = `${duplicateMessages.duplicateError} ${duplicateMessages.duplicateListPrefix} --primary-color`;
    const duplicateToast = page.locator('.ssc-toast').last();
    await expect(duplicateToast).toHaveText(expectedDuplicateToast);

    const spokenMessages = await page.evaluate(() => window.__sscTestSpokenMessages || []);
    expect(spokenMessages).toContain(expectedDuplicateToast);

    const firstRowName = rows.first().locator('.token-name');
    await expect(firstRowName).toHaveAttribute('aria-invalid', 'true');
    await expect(duplicateRow.locator('.token-name')).toHaveAttribute('aria-invalid', 'true');
    await expect(duplicateRow).toHaveClass(/ssc-token-row--duplicate/);

    const fetchResponse = await page.request.get(tokensEndpoint, {
      headers: {
        'X-WP-Nonce': nonce,
      },
    });
    expect(fetchResponse.ok()).toBeTruthy();
    const fetchedJson = await fetchResponse.json();
    expect(Array.isArray(fetchedJson.tokens)).toBeTruthy();
    expect(fetchedJson.tokens).toHaveLength(1);
    expect(fetchedJson.tokens[0].name).toBe('--primary-color');

    await duplicateRow.locator('.token-name').fill('--secondary-color');
    await duplicateRow.locator('.token-name').blur();
    await expect(firstRowName).not.toHaveAttribute('aria-invalid', 'true');
    await expect(duplicateRow.locator('.token-name')).not.toHaveAttribute('aria-invalid', 'true');

    const saveResponse = page.waitForResponse(
      (response) =>
        response.url().startsWith(tokensEndpoint) &&
        response.request().method() === 'POST'
    );
    await page.locator('#ssc-tokens-save').click();
    const saved = await saveResponse;
    expect(saved.ok()).toBeTruthy();

    const finalState = await page.request.get(tokensEndpoint, {
      headers: {
        'X-WP-Nonce': nonce,
      },
    });
    expect(finalState.ok()).toBeTruthy();
    const finalJson = await finalState.json();
    expect(finalJson.tokens).toHaveLength(2);
    const names = finalJson.tokens.map((token) => token.name).sort();
    expect(names).toEqual(['--primary-color', '--secondary-color']);
  });

  test('API surfaces duplicate conflicts when normalization detects collisions', async ({ page }, testInfo) => {
    const adminTokensUrl = getAdminTokensUrl(testInfo);

    await authenticate(page, adminTokensUrl, {
      username: DEFAULT_USERNAME,
      password: DEFAULT_PASSWORD,
    });

    let { restRoot, nonce } = await waitForPluginReady(page);
    let tokensEndpoint = new URL('tokens', restRoot).toString();

    const baseTokens = [
      {
        name: '--primary-color',
        value: '#123456',
        type: 'color',
        description: 'Primary brand color',
        group: 'Brand',
      },
      {
        name: '--secondary-color',
        value: '#abcdef',
        type: 'color',
        description: 'Secondary brand color',
        group: 'Brand',
      },
    ];

    await seedTokens(page, tokensEndpoint, nonce, baseTokens);

    await page.reload({ waitUntil: 'networkidle' });
    ({ restRoot, nonce } = await waitForPluginReady(page));
    tokensEndpoint = new URL('tokens', restRoot).toString();

    const beforeResponse = await page.request.get(tokensEndpoint, {
      headers: {
        'X-WP-Nonce': nonce,
      },
    });
    expect(beforeResponse.ok()).toBeTruthy();
    const beforeJson = await beforeResponse.json();
    const beforeSnapshot = JSON.stringify(beforeJson.tokens);

    const duplicatePayload = [
      {
        name: '--SpacingLarge',
        value: '4rem',
        type: 'text',
        description: 'Large spacing token',
        group: 'Spacing',
      },
      {
        name: 'spacing-large',
        value: '6rem',
        type: 'text',
        description: 'Duplicate spacing token',
        group: 'Spacing',
      },
    ];

    const duplicateResponse = await page.request.post(tokensEndpoint, {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: { tokens: duplicatePayload },
    });

    expect(duplicateResponse.status()).toBe(422);
    const duplicateJson = await duplicateResponse.json();
    expect(duplicateJson.ok).toBeFalsy();
    expect(Array.isArray(duplicateJson.duplicates)).toBeTruthy();
    expect(duplicateJson.duplicates.length).toBeGreaterThan(0);
    const firstDuplicate = duplicateJson.duplicates[0];
    expect(firstDuplicate.canonical).toBe('--SpacingLarge');
    expect(Array.isArray(firstDuplicate.variants)).toBeTruthy();
    expect(firstDuplicate.variants).toEqual(
      expect.arrayContaining(['--SpacingLarge', '--spacing-large'])
    );
    expect(Array.isArray(firstDuplicate.conflicts)).toBeTruthy();
    const conflictNames = firstDuplicate.conflicts.map((conflict) => conflict.name);
    expect(conflictNames).toEqual(
      expect.arrayContaining(['--SpacingLarge', 'spacing-large'])
    );

    const afterResponse = await page.request.get(tokensEndpoint, {
      headers: {
        'X-WP-Nonce': nonce,
      },
    });
    expect(afterResponse.ok()).toBeTruthy();
    const afterJson = await afterResponse.json();
    expect(JSON.stringify(afterJson.tokens)).toBe(beforeSnapshot);
  });
});
