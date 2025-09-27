const path = require('path');
const { test, expect } = require('@playwright/test');

const pluginRoot = path.resolve(__dirname, '..', '..');
const restRoot = 'https://example.test/wp-json/ssc/v1/';

function generateCss(tokens) {
  if (!tokens.length) {
    return ':root {\n}\n';
  }
  const lines = tokens.map((token) => `    ${token.name}: ${token.value};`);
  return `:root {\n${lines.join('\n')}\n}`;
}

test.describe('Token manager admin UI', () => {
  test('adds, edits and deletes tokens with live CSS preview updates', async ({ page }) => {
    const jqueryPath = require.resolve('jquery/dist/jquery.min.js');
    const tokensScriptPath = path.resolve(pluginRoot, 'assets/js/tokens.js');

    let serverTokens = [
      {
        name: '--primary-color',
        value: '#123456',
        type: 'color',
        description: 'Primary brand color',
        group: 'Brand',
      },
    ];
    const initialTokens = serverTokens.slice();

    await page.route('**/wp-json/ssc/v1/tokens**', async (route) => {
      const request = route.request();
      if (request.method() === 'GET') {
        await route.fulfill({
          contentType: 'application/json',
          headers: {
            'access-control-allow-origin': '*',
          },
          body: JSON.stringify({
            tokens: serverTokens,
            css: generateCss(serverTokens),
          }),
        });
        return;
      }

      if (request.method() === 'POST') {
        const payload = request.postDataJSON() || {};
        if (Array.isArray(payload.tokens)) {
          serverTokens = payload.tokens;
        }
        await route.fulfill({
          contentType: 'application/json',
          headers: {
            'access-control-allow-origin': '*',
          },
          body: JSON.stringify({
            tokens: serverTokens,
            css: generateCss(serverTokens),
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.setContent(`
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Tokens Admin Test</title>
        </head>
        <body>
          <div class="ssc-app ssc-fullwidth">
            <div class="ssc-token-toolbar">
              <button id="ssc-token-add" class="button">Add token</button>
            </div>
            <div id="ssc-token-builder" class="ssc-token-builder" aria-live="polite"></div>
            <textarea id="ssc-tokens" rows="10" class="large-text" readonly></textarea>
            <div class="ssc-actions">
              <button id="ssc-tokens-save" class="button button-primary">Save tokens</button>
              <button id="ssc-tokens-copy" class="button">Copy CSS</button>
            </div>
            <style id="ssc-tokens-preview-style"></style>
            <div id="ssc-tokens-preview"></div>
          </div>
        </body>
      </html>
    `, { waitUntil: 'domcontentloaded' });

    await page.evaluate(({ restEndpoint, tokens, css }) => {
      window.SSC = {
        rest: {
          root: restEndpoint,
          nonce: 'ui-test-nonce',
        },
      };
      window.SSC_TOKENS_DATA = {
        tokens,
        css,
        types: {
          color: { label: 'Colour', input: 'color' },
          text: { label: 'Text', input: 'text' },
        },
        i18n: {
          addToken: 'Add token',
          emptyState: 'No tokens yet',
          groupLabel: 'Group',
          nameLabel: 'Name',
          valueLabel: 'Value',
          typeLabel: 'Type',
          descriptionLabel: 'Description',
          deleteLabel: 'Delete',
          saveSuccess: 'Tokens saved',
          saveError: 'Unable to save tokens',
        },
      };
      window.sscToast = () => {};
    }, { restEndpoint: restRoot, tokens: initialTokens, css: generateCss(initialTokens) });

    await page.addScriptTag({ path: jqueryPath });
    await page.addScriptTag({ path: tokensScriptPath });
    const builder = page.locator('#ssc-token-builder');
    const rows = builder.locator('.ssc-token-row');
    const previewStyle = page.locator('#ssc-tokens-preview-style');
    const getPreviewText = () => page.evaluate(() => document.getElementById('ssc-tokens-preview-style').textContent || '');
    const cssTextarea = page.locator('#ssc-tokens');

    await expect(rows).toHaveCount(1);
    await expect.poll(getPreviewText).toContain('--primary-color: #123456;');

    await page.locator('#ssc-token-add').click();
    await expect(rows).toHaveCount(2);

    const newRow = builder.locator('.ssc-token-row').last();
    await newRow.locator('.token-name').fill('spacing_small');
    await newRow.locator('.token-type').selectOption('text');
    await newRow.locator('.token-value').click();
    await expect(newRow.locator('.token-name')).toHaveValue('--spacing_small');
    await newRow.locator('.token-value').fill('8px');

    await expect.poll(getPreviewText).toContain('--spacing_small: 8px;');

    const saveResponse = page.waitForResponse((response) =>
      response.url() === restRoot + 'tokens' && response.request().method() === 'POST'
    );
    await page.locator('#ssc-tokens-save').click();
    await saveResponse;
    expect(serverTokens.length).toBe(2);
    expect(serverTokens[1].name).toBe('--spacing_small');

    await newRow.locator('.token-value').fill('12px');
    await expect.poll(getPreviewText).toContain('--spacing_small: 12px;');

    await builder.locator('.ssc-token-row').last().locator('.token-delete').click();
    await expect(rows).toHaveCount(1);
    await expect.poll(getPreviewText).not.toContain('--spacing_small');

    const deleteSave = page.waitForResponse((response) =>
      response.url() === restRoot + 'tokens' && response.request().method() === 'POST'
    );
    await page.locator('#ssc-tokens-save').click();
    await deleteSave;

    expect(serverTokens.length).toBe(1);
    await expect.poll(getPreviewText).toContain('--primary-color: #123456;');
    await expect(cssTextarea).toHaveValue(/--primary-color: #123456;/);
  });
});
