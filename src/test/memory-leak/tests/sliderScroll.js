const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

async function swipeSlider(page, direction = 'left') {
  const slider = await page.$('.swiper-wrapper');
  const boundingBox = await slider.boundingBox();

  const startX = direction === 'left' ? boundingBox.x + boundingBox.width - 10 : boundingBox.x + 10;
  const endX = direction === 'left' ? boundingBox.x + 10 : boundingBox.x + boundingBox.width - 10;
  const startY = boundingBox.y + boundingBox.height / 2;
  const endY = boundingBox.y + boundingBox.height / 2;

  for (let i = 0; i < 6; i++) {
    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(endX, endY, { steps: 20 });
    await page.mouse.up();
    await page.waitForTimeout(500);
  }
}

function url() {
  return process.env.MEMLAB_WEBSITE_URL;
}

async function action(page) {
  await page.setViewport({ width: 375, height: 812 });

  await swipeSlider(page, 'left');
}

async function back(page) {
  await swipeSlider(page, 'right');
}

module.exports = { url, action, back };
