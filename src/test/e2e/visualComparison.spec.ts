import { test, expect } from '@playwright/test';

test('example test', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveScreenshot('website.png');
});
test('Full Page', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveScreenshot('website-full.png', { fullPage: true });
});
