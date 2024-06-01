import { test, expect } from '@playwright/test';

import { screenSizes } from './constants';

test.describe('Visual Test', () => {
  for (const screen of screenSizes) {
    test(`${screen.name} test`, async ({ page }) => {
      await page.goto('/');

      await page.waitForLoadState('networkidle');
      await page.evaluateHandle('document.fonts.ready');

      await page.setViewportSize({ width: screen.width, height: screen.height });

      await page.waitForTimeout(500);

      await expect(page).toHaveScreenshot(`${screen.name}.png`, {
        fullPage: true,
      });
    });
  }
});
