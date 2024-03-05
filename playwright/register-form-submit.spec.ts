import { test, expect } from '@playwright/test';

test.describe('two tests', () => {
  test('test', async ({ page }) => {
    const initialsInput: ReturnType<typeof page.getByPlaceholder> =
      page.getByPlaceholder('Mykhailo Svitskyi');
    const emailInput: ReturnType<typeof page.getByPlaceholder> =
      page.getByPlaceholder('vilnaCRM@gmail.com');
    const passwordInput: ReturnType<typeof page.getByPlaceholder> =
      page.getByPlaceholder('Create a password');

    await page.goto('http://localhost:3000/');

    await initialsInput.click();
    await initialsInput.fill('Hello');
    await page.getByText('Or register on the website:').click();
    await expect(page.getByText('Invalid full name format')).toBeVisible();

    await initialsInput.click();
    await initialsInput.fill('Hello World');

    await emailInput.click();
    await emailInput.fill('hello');
    await page.getByText('Or register on the website:').click();
    await expect(page.getByText("Email must contain '@' and")).toBeVisible();

    await emailInput.click();
    await emailInput.fill('hello@sdf.');
    await expect(page.getByText('Invalid email address')).toBeVisible();

    await emailInput.click();
    await emailInput.fill('hello@sdf.fd');

    await passwordInput.click();
    await passwordInput.fill('dsaasdaasd');
    await page.getByText('Or register on the website:').click();
    await expect(page.getByText('Password must contain at')).toBeVisible();

    await passwordInput.click();
    await passwordInput.fill('dsaasd');
    await expect(page.getByText('Password must be between 8')).toBeVisible();

    await passwordInput.click();
    await passwordInput.fill('dsaasdxcsdf');
    await expect(page.getByText('Password must contain at')).toBeVisible();

    await passwordInput.click();
    await passwordInput.fill('dsaasdxcsdf2');
    await expect(page.getByText('Password must contain at')).toBeVisible();

    await passwordInput.click();
    await passwordInput.fill('dsaasdxcsdf2W');

    await page.getByLabel('I have read and accept the').check();
    await page.getByRole('button', { name: 'Sign-Up' }).click();
  });
});
