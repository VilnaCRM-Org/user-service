const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

function url() {
  return process.env.MEMLAB_WEBSITE_URL;
}

async function action(page) {
  await page.click('a[href="#signUp"]');
}

module.exports = { url, action };
