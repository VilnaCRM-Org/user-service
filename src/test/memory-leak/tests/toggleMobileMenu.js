const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

function url() {
  return process.env.MEMLAB_WEBSITE_URL;
}

async function action(page) {
  await page.setViewport({ width: 375, height: 812 });

  await page.click('img[alt="Bars Icon"]');
}

async function back(page) {
  await page.click('img[alt="Exit Icon"]');
}

module.exports = { url, action, back };
