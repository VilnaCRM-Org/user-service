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

test('Submit the registration form', async ({ page }) => {
  const initialsInput: Locator = page.getByPlaceholder('Mykhailo Svitskyi');
  const emailInput: Locator = page.getByPlaceholder('vilnaCRM@gmail.com');
  const passwordInput: Locator = page.getByPlaceholder('Create a password');
  const signupButton: Locator = page.getByRole('button', { name: 'Sign-Up' });
  const termsCheckbox: Locator = page.getByLabel('I have read and accept the');
  await page.goto('http://localhost:3000/');

  await initialsInput.click();
  await initialsInput.fill(user.name);
  await emailInput.click();
  await emailInput.fill(user.email);
  await passwordInput.click();
  await passwordInput.fill(user.password);
  await termsCheckbox.check();
  expect(termsCheckbox).toBeChecked();

  await Promise.all([
    page.waitForResponse(
      resp => resp.url().includes('/api/graphql') && resp.status() === 200
    ),
    signupButton.click(),
  ]);
});
