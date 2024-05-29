const Utils = require('../utils/utils');

const sliderSelector = '.swiper-wrapper';

const iterations = 6;

async function setup(page) {
  await page.setViewport(Utils.mobileViewport);
}

async function action(page) {
  await Utils.swipeSlider(page, sliderSelector, iterations, 'left');
}

async function back(page) {
  await Utils.swipeSlider(page, sliderSelector, iterations, 'right');
}

module.exports = Utils.createScenario({ setup, action, back });
