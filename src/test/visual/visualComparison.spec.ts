import { test, expect } from '@playwright/test';

import { screenSizes } from './constants';

test.describe('Visual Tests', () => {
  for (const screen of screenSizes) {
    test(`${screen.name} test`, async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(3000);

      await page.waitForLoadState('networkidle');
      await page.evaluateHandle('document.fonts.ready');

      const scrollHeight: number = await page.evaluate(() => document.documentElement.scrollHeight);
      await page.setViewportSize({ width: screen.width, height: scrollHeight });

      await page.waitForTimeout(3000);

      await page.setViewportSize({ width: screen.width, height: screen.height });

      await page.waitForTimeout(3000);

      await expect(page).toHaveScreenshot(`${screen.name}.png`, {
        fullPage: true,
      });
    });
  }
});
