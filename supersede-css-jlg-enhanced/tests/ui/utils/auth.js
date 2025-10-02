const DEFAULT_USERNAME = process.env.WP_USERNAME || 'admin';
const DEFAULT_PASSWORD = process.env.WP_PASSWORD || 'password';

function getAdminUrl(testInfo, adminPath) {
  const baseURL = testInfo.project.use.baseURL || 'http://localhost:8889';
  return new URL(adminPath, baseURL).toString();
}

function getDefaultCredentials() {
  return {
    username: DEFAULT_USERNAME,
    password: DEFAULT_PASSWORD,
  };
}

async function authenticate(page, adminUrl, credentials = getDefaultCredentials()) {
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

module.exports = {
  authenticate,
  getAdminUrl,
  getDefaultCredentials,
};
