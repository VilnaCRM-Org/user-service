import { test } from '@playwright/test';

test('First', async ({ page }) => {
  await page.goto('https://demo.playwright.dev/todomvc/#/');
  await page.locator('input').fill('Learn Playwright');
  await page.locator('input').press('Enter');
});
