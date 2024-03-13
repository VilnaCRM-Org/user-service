import { Locator, Page, Response, expect } from '@playwright/test';

import { expectationsEmail, expectationsPassword } from './constants';
import { User } from './types';

export async function fillInitialsInput(page: Page, user: User): Promise<void> {
  const initialsInput: Locator = page.getByPlaceholder('Mykhailo Svitskyi');
  await initialsInput.fill('Hello');
  await expect(page.getByText('Invalid full name format')).toBeVisible();
  await initialsInput.fill(user.fullName);
}

export async function fillEmailInput(page: Page, user: User): Promise<void> {
  const emailInput: Locator = page.getByPlaceholder('vilnaCRM@gmail.com');
  for (const expectation of expectationsEmail) {
    await emailInput.fill(expectation.email);
    await expect(page.getByText(expectation.errorText)).toBeVisible();
  }

  await emailInput.fill(user.email);
}

export async function fillPasswordInput(page: Page, user: User): Promise<void> {
  const passwordInput: Locator = page.getByPlaceholder('Create a password');
  for (const expectation of expectationsPassword) {
    await passwordInput.fill(expectation.password);
    await expect(page.getByText(expectation.errorText)).toBeVisible();
  }

  await passwordInput.fill(user.password);
}
export function responseFilter(resp: Response): boolean {
  return resp.url().includes('/api/graphql') && resp.status() === 200;
}