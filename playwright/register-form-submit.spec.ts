import { test } from '@playwright/test';

test('test', async ({ page }) => {
  await page.goto('http://localhost:3000/');
  await page.getByPlaceholder('Mykhailo Svitskyi').click();
  await page.getByPlaceholder('Mykhailo Svitskyi').fill('My name');
  await page.getByPlaceholder('vilnaCRM@gmail.com').click();
  await page.getByPlaceholder('vilnaCRM@gmail.com').fill('myname@hotline.com');
  await page.getByPlaceholder('Create a password').click();
  await page.getByPlaceholder('Create a password').fill('myNameHere12');
  await page.getByLabel('I have read and accept the').check();
  await page.getByRole('button', { name: 'Sign-Up' }).click();
});
