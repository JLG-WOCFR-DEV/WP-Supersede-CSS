const { runWpEnv, waitForWordPressReady, defaultBaseUrl } = require('./utils/wp-env');

module.exports = async () => {
  await runWpEnv(['start']);
  await waitForWordPressReady(defaultBaseUrl);
};
