const Utils = require('../utils/utils');

const signUpButtonSelector = 'a[href="#signUp"]';

const coordinateX = 0;
const coordinateY = 0;

async function action(page) {
  await page.click(signUpButtonSelector);

  await page.waitForTimeout(2000);
}

async function back(page) {
  await page.evaluate(
    (x, y) => {
      window.scrollTo(x, y);
    },
    coordinateX,
    coordinateY
  );

  await page.waitForTimeout(2000);
}

module.exports = Utils.createScenario({ action, back });
