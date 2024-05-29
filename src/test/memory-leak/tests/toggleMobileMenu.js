const Utils = require('../utils/utils');

const menuIconSelector = 'img[alt="Bars Icon"]';
const closeIconSelector = 'img[alt="Exit Icon"]';

async function setup(page) {
  await page.setViewport(Utils.mobileViewport);
}

async function action(page) {
  await page.setViewport(Utils.mobileViewport);

  await page.click(menuIconSelector);

  await page.waitForSelector(closeIconSelector, { visible: true });
}

async function back(page) {
  await page.click(closeIconSelector);

  await page.waitForSelector(closeIconSelector, { hidden: true });
}

module.exports = Utils.createScenario({ setup, action, back });
