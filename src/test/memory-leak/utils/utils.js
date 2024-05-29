const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

class Utils {
  static mobileViewport = { width: 400, height: 812 };

  static createScenario(scenarioOptions) {
    const url = () => process.env.MEMLAB_WEBSITE_URL;

    const scenario = { url, ...scenarioOptions };

    return scenario;
  }

  static async swipeSlider(page, selector, iterationsNumber, direction = 'left') {
    const slider = await page.$(selector);
    const boundingBox = await slider.boundingBox();

    const coordinates = this.calculateCoordinates(boundingBox, direction);

    for (const _ of Array(iterationsNumber)) {
      await page.mouse.move(coordinates.startX, coordinates.startY);
      await page.mouse.down();

      await page.mouse.move(coordinates.endX, coordinates.endY, { steps: 20 });
      await page.mouse.up();

      await page.waitForTimeout(500);
    }
  }

  static calculateCoordinates(boundingBox, direction) {
    const startX =
      direction === 'left' ? boundingBox.x + boundingBox.width - 10 : boundingBox.x + 10;
    const endX = direction === 'left' ? boundingBox.x + 10 : boundingBox.x + boundingBox.width - 10;
    const startY = boundingBox.y + boundingBox.height / 2;
    const endY = boundingBox.y + boundingBox.height / 2;

    return { startX, endX, startY, endY };
  }
}

module.exports = Utils;
