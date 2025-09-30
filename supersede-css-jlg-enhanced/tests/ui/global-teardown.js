const { runWpEnv } = require('./utils/wp-env');

module.exports = async () => {
  try {
    await runWpEnv(['stop']);
  } catch (error) {
    console.warn('Failed to stop wp-env:', error.message);
  }

  try {
    await runWpEnv(['destroy', '--yes']);
  } catch (error) {
    console.warn('Failed to destroy wp-env:', error.message);
  }
};
