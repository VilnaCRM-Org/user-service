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

async function fillInput(input: Locator, value: string): Promise<void> {
  await input.click();
  await input.fill(value);
}

async function checkCheckbox(checkbox: Locator): Promise<void> {
  await checkbox.check();
  expect(checkbox).toBeChecked();
}

function responseFilter(resp: any): boolean {
  return resp.url().includes('/api/graphql') && resp.status() === 200;
}

test('Submit the registration form', async ({ page }) => {
  const initialsInput: Locator = page.getByPlaceholder('Mykhailo Svitskyi');
  const emailInput: Locator = page.getByPlaceholder('vilnaCRM@gmail.com');
  const passwordInput: Locator = page.getByPlaceholder('Create a password');
  const signupButton: Locator = page.getByRole('button', { name: 'Sign-Up' });
  const termsCheckbox: Locator = page.getByLabel('I have read and accept the');

  await page.goto('/');

  await fillInput(initialsInput, user.name);
  await fillInput(emailInput, user.email);
  await fillInput(passwordInput, user.password);

  await checkCheckbox(termsCheckbox);

  await Promise.all([
    page.waitForResponse(responseFilter),
    signupButton.click(),
  ]);
});
