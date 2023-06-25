import { defineConfig } from 'cypress';
import 'dotenv/config';

export default defineConfig({
  e2e: {
    supportFile: './cypress/support/e2e.{ts, tsx}',
    specPattern: './cypress/spec/*.spec.{ts, tsx}',
  },
  env: {
    WEBSITE_URL: process.env.WEBSITE_URL,
  },
  downloadsFolder: './cypress/downloads',
  fileServerFolder: './cypress/fileServer',
  fixturesFolder: './cypress/fixtures',
  screenshotsFolder: './cypress/screenshots',
  videosFolder: './cypress/videos',
  supportFolder: './cypress/support',
  video: false,
});
