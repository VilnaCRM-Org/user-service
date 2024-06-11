import { test, expect } from '@playwright/test';

import { screenSizes } from './constants';

test('Visual Test', async ({ page }) => {
  await page.goto('/');
  for (const screen of screenSizes) {
    await page.setViewportSize({ width: screen.width, height: screen.height });
    await page.waitForTimeout(3000);
    await expect(page).toHaveScreenshot(`${screen.name}.png`, {
      fullPage: true,
    });
  }
});
