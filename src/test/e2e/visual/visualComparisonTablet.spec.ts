import { test, expect } from '@playwright/test';

test('Tablet full Page', async ({ page }) => {
  await page.goto('/');
  await page.setViewportSize({ width: 768, height: 812 });
  await expect(page).toHaveScreenshot('Tablet-full.png', { fullPage: true });
});
