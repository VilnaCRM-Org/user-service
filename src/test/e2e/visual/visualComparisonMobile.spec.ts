import { test, expect } from '@playwright/test';

test('Mobile full Page', async ({ page }) => {
  await page.goto('/');
  await page.setViewportSize({ width: 375, height: 812 });
  await expect(page).toHaveScreenshot('mobile-full.png', { fullPage: true });
});
