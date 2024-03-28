const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);
function url() {
  return process.env.MEMLAB_WEBSITE_URL;
}

module.exports = { url };
