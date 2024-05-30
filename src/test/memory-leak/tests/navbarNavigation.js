const ScenarioBuilder = require('../utils/ScenarioBuilder');

const scenarioBuilder = new ScenarioBuilder();

const advantagesLinkSelector = 'a[href="#Advantages"]';
const forWhoSectionLinkSelector = 'a[href="#forWhoSection"]';
const integrationLinkSelector = 'a[href="#Integration"]';
const contactsLinkSelector = 'a[href="#Contacts"]';

const coordinateX = 0;
const coordinateY = 0;

async function action(page) {
  await page.click(advantagesLinkSelector);
  await page.waitForTimeout(1500);

  await page.click(forWhoSectionLinkSelector);
  await page.waitForTimeout(1500);

  await page.click(integrationLinkSelector);
  await page.waitForTimeout(1500);

  await page.click(contactsLinkSelector);
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

module.exports = scenarioBuilder.createScenario({ action, back });
