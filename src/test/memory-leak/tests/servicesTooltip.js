const ScenarioBuilder = require('../utils/ScenarioBuilder');

const scenarioBuilder = new ScenarioBuilder();

const servicesButtonSelector = 'span.css-1rp615p-MuiTypography-root';
const tooltipSelector = '.MuiTooltip-popper';

const coordinateX = 100;
const coordinateY = 100;

async function action(page) {
  await page.click(servicesButtonSelector);

  await page.waitForSelector(tooltipSelector, { visible: true });
}

async function back(page) {
  await page.mouse.click(coordinateX, coordinateY);

  await page.waitForSelector(tooltipSelector, { hidden: true });
}

module.exports = scenarioBuilder.createScenario({ action, back });
