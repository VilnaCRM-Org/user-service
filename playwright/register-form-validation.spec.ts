import { faker } from '@faker-js/faker';
import { test, expect, Locator, Page } from '@playwright/test';

interface User {
  name: string;
  email: string;
  password: string;
}

interface Expectation {
  errorText: string;
  email: string;
}

interface ExpectationsPassword {
  errorText: string;
  password: string;
}

const userData: User = {
  name: faker.person.fullName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
};

const expectations: Expectation[] = [
  { errorText: "Email must contain '@' and '.' symbols", email: 'hello@sdf' },
  { errorText: 'Invalid email address', email: 'hello@sdf.' },
];

const expectationsPassword: ExpectationsPassword[] = [
  { errorText: 'Password must be between 8', password: 'tirion' },
  {
    errorText: 'Password must contain at least one number',
    password: 'lanister',
  },
  {
    errorText: 'Password must contain at least one uppercase letter',
    password: 'lanister1',
  },
];

const expectationsRequired: { text: string }[] = [
  { text: 'Your first and last name are' },
  { text: 'Email address is a required' },
  { text: 'Password is a required field' },
];

async function fillInitialsInput(page: Page, user: User): Promise<void> {
  const initialsInput: Locator = page.getByPlaceholder('Mykhailo Svitskyi');
  await initialsInput.fill('Hello');
  await expect(page.getByText('Invalid full name format')).toBeVisible();
  await initialsInput.fill(user.name);
}

async function fillEmailInput(page: Page, user: User): Promise<void> {
  const emailInput: Locator = page.getByPlaceholder('vilnaCRM@gmail.com');
  for (const expectation of expectations) {
    await emailInput.fill(expectation.email);
    await expect(page.getByText(expectation.errorText)).toBeVisible();
  }

  await emailInput.fill(user.email);
}

async function fillPasswordInput(page: Page, user: User): Promise<void> {
  const passwordInput: Locator = page.getByPlaceholder('Create a password');
  for (const expectation of expectationsPassword) {
    await passwordInput.fill(expectation.password);
    await expect(page.getByText(expectation.errorText)).toBeVisible();
  }

  await passwordInput.fill(user.password);
}

test('Should display error messages for invalid inputs', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Sign-Up' }).click();

  for (const expectation of expectationsRequired) {
    await expect(page.getByText(expectation.text)).toBeVisible();
  }

  await fillInitialsInput(page, userData);
  await fillEmailInput(page, userData);
  await fillPasswordInput(page, userData);

  await page.getByLabel('I have read and accept the').check();
  await page.getByRole('button', { name: 'Sign-Up' }).click();

  const loading: Locator = await page
    .getByTestId('auth-section')
    .locator('svg');
  await expect(loading).toBeVisible();
});
