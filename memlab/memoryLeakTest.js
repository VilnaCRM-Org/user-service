require('dotenv').config();

function url() {
  return process.env.WEBSITE_URL;
}

module.exports = { url };
