// Todo: remove eslint rules
/* eslint-disable no-empty-function */
// eslint-disable-next-line import/no-extraneous-dependencies
require('dotenv').config();

function url() {
  return process.env.WEBSITE_URL;
}

async function action() {}

async function back() {}

module.exports = { url, action, back };
