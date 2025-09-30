const path = require('path');
const { spawn } = require('child_process');
const http = require('http');
const https = require('https');

const envCwd = path.resolve(__dirname, '../../..');
const defaultBaseUrl = process.env.WP_BASE_URL || 'http://localhost:8889';

function getNpxCommand() {
  return process.platform === 'win32' ? 'npx.cmd' : 'npx';
}

function runCommand(command, args, options = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      ...options,
      stdio: 'inherit',
      shell: process.platform === 'win32',
    });

    child.on('error', reject);
    child.on('exit', (code) => {
      if (code === 0) {
        resolve();
      } else {
        reject(new Error(`${command} ${args.join(' ')} exited with code ${code}`));
      }
    });
  });
}

function runWpEnv(args) {
  return runCommand(getNpxCommand(), ['wp-env', ...args], { cwd: envCwd });
}

function ping(url) {
  const target = new URL(url);
  const client = target.protocol === 'https:' ? https : http;

  return new Promise((resolve, reject) => {
    const request = client.request(
      {
        method: 'GET',
        hostname: target.hostname,
        port: target.port,
        path: `${target.pathname}${target.search}`,
      },
      (response) => {
        response.resume();
        if (response.statusCode && response.statusCode >= 200 && response.statusCode < 500) {
          resolve();
        } else {
          reject(new Error(`Unexpected status code ${response.statusCode}`));
        }
      }
    );

    request.on('error', reject);
    request.end();
  });
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitForWordPressReady(baseUrl = defaultBaseUrl, timeoutMs = 120000, intervalMs = 2000) {
  const deadline = Date.now() + timeoutMs;
  const healthUrl = new URL('/wp-json/', baseUrl).toString();

  while (Date.now() < deadline) {
    try {
      await ping(healthUrl);
      return;
    } catch (error) {
      if (Date.now() >= deadline) {
        throw error;
      }
      await sleep(intervalMs);
    }
  }

  throw new Error(`Timed out waiting for WordPress at ${healthUrl}`);
}

module.exports = {
  defaultBaseUrl,
  runWpEnv,
  waitForWordPressReady,
};
