import { test, expect } from '@playwright/test';

test('Checking whether the drawer opens and closes', async ({ page }) => {
  await page.goto('http://localhost:3000/');
  await page.setViewportSize({ width: 450, height: 812 });

  await page.getByLabel('Button to open the drawer').click();
  await expect(page.getByTestId('drawer')).toBeVisible();

  await page.getByLabel('Button to exit the drawer').click();
  await expect(page.getByTestId('drawer')).toBeHidden();

  await page.getByLabel('Button to open the drawer').click();
  await page.getByRole('button', { name: 'Log in' }).click();

  await page.getByLabel('Button to open the drawer').click();
  await page.getByRole('button', { name: 'Try it out' }).click();
});
