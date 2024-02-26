require('dotenv').config();

function url() {
  return process.env.WEBSITE_URL || 'http://localhost:3000';
}

module.exports = { url };