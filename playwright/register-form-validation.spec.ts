import { faker } from '@faker-js/faker';
import { test, expect, Locator } from '@playwright/test';

interface User {
  name: string;
  email: string;
  password: string;
}

const user: User = {
  name: faker.person.fullName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
};

test('Should display error messages for invalid inputs', async ({ page }) => {
  const initialsInput: Locator = page.getByPlaceholder('Mykhailo Svitskyi');
  const emailInput: Locator = page.getByPlaceholder('vilnaCRM@gmail.com');
  const passwordInput: Locator = page.getByPlaceholder('Create a password');

  await page.goto('http://localhost:3000/');

  await page.getByRole('button', { name: 'Sign-Up' }).click();

  await expect(page.getByText('Your first and last name are')).toBeVisible();
  await expect(page.getByText('Email address is a required')).toBeVisible();
  await expect(page.getByText('Password is a required field')).toBeVisible();

  await initialsInput.fill('Hello');
  await expect(page.getByText('Invalid full name format')).toBeVisible();
  await initialsInput.fill(user.name);

  await emailInput.fill('hello@sdf');
  await expect(
    page.getByText("Email must contain '@' and '.' symbols")
  ).toBeVisible();

  await emailInput.fill('hello@sdf.');
  await expect(page.getByText('Invalid email address')).toBeVisible();
  await emailInput.fill(user.email);

  await passwordInput.fill('tirion');
  await expect(page.getByText('Password must be between 8')).toBeVisible();

  await passwordInput.fill('lanister');
  await expect(
    page.getByText('Password must contain at least one number')
  ).toBeVisible();

  await passwordInput.fill('lanister1');
  await expect(
    page.getByText('Password must contain at least one uppercase letter')
  ).toBeVisible();
  await passwordInput.fill(user.password);

  await page.getByLabel('I have read and accept the').check();
  await page.getByRole('button', { name: 'Sign-Up' }).click();
});
