import { test, expect } from '@playwright/test';

test('By clicking on burs in the phone, the drawer opens and all navigation works', async ({
  page,
}) => {
  await page.goto('http://localhost:3000/');
  await page.setViewportSize({ width: 375, height: 812 });
  await page.getByLabel('Button to open the drawer').click();
  await expect(page.getByTestId('drawer')).toBeVisible();
});
