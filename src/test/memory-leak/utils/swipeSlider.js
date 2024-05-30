async function swipeSlider(page, selector, iterationsNumber, direction = 'left') {
  const slider = await page.$(selector);
  const boundingBox = await slider.boundingBox();

  const coordinates = calculateCoordinates(boundingBox, direction);

  await horizontalDragAndDrop(page, coordinates, iterationsNumber);
}

function calculateCoordinates(boundingBox, direction) {
  const startX = direction === 'left' ? boundingBox.x + boundingBox.width - 10 : boundingBox.x + 10;
  const endX = direction === 'left' ? boundingBox.x + 10 : boundingBox.x + boundingBox.width - 10;
  const startY = boundingBox.y + boundingBox.height / 2;
  const endY = boundingBox.y + boundingBox.height / 2;

  return { startX, endX, startY, endY };
}

async function horizontalDragAndDrop(page, coordinates, iterationsNumber) {
  Array(iterationsNumber).forEach(async () => {
    await page.mouse.move(coordinates.startX, coordinates.startY);
    await page.mouse.down();

    await page.mouse.move(coordinates.endX, coordinates.endY, { steps: 20 });
    await page.mouse.up();

    await page.waitForTimeout(500);
  });
}

module.exports = swipeSlider;
