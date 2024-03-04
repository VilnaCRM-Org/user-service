import { test, expect } from '@playwright/test';

test('test', async ({ page }) => {
  await page.goto('http://localhost:3000/');
  await page.getByRole('button', { name: 'Log in' }).click();
  await page.getByTestId('header-sign-up').click();
  await page.getByTestId('about-sign-up').click();
  await page.getByTestId('for-who-sign-up').click();
  await expect(
    page
      .locator('div')
      .filter({ hasText: 'Sign up now and free up your' })
      .nth(2)
  ).toBeVisible();
});
