import { test, expect } from '@playwright/test';

test('DeskTop full Page', async ({ page }) => {
  await page.goto('/');
  await page.setViewportSize({ width: 1440, height: 1200 });
  await expect(page).toHaveScreenshot('deskTop-full.png', { fullPage: true });
});
