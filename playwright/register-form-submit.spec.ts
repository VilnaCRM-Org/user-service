import { test } from '@playwright/test';

test('test', async ({ page }) => {
  await page.goto('http://localhost:3000/');
  await page.getByPlaceholder('Mykhailo Svitskyi').click();
  await page.getByPlaceholder('Mykhailo Svitskyi').fill('Vlas Dv');
  await page.getByPlaceholder('vilnaCRM@gmail.com').click();
  await page.getByPlaceholder('vilnaCRM@gmail.com').fill('vlas@gmail.com');
  await page.getByPlaceholder('Create a password').click();
  await page.getByPlaceholder('Create a password').fill('asadadas2Wda');
  await page.locator('label').click();
  await page.getByRole('button', { name: 'Sign-Up' }).click();
});
