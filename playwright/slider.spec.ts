import { test, Locator, expect } from '@playwright/test';

test.describe('Slider tests', () => {
  test('Move slider to the right', async ({ page }) => {
    await page.goto('/');
    await page.setViewportSize({ width: 375, height: 812 });

    const sliderTrack: Locator = await page.getByText('Open source').nth(3);

    const sliderOffsetWidth: number = await sliderTrack.evaluate(
      el => el.getBoundingClientRect().width + 205
    );
    await sliderTrack.hover({ force: true, position: { x: 0, y: 0 } });
    await page.mouse.down();
    await sliderTrack.hover({
      force: true,
      position: { x: sliderOffsetWidth, y: 0 },
    });
    await page.mouse.up();

    const secondSlide: Locator = await page.getByText('Ease of setup').nth(1);
    await expect(secondSlide).toBeVisible();
  });
});
