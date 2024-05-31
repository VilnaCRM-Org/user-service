import { Locator, Page, Response, expect } from '@playwright/test';

import {
  expectationsEmail,
  expectationsPassword,
  placeholderInitials,
  placeholderEmail,
  placeholderPassword,
  signUpButton,
  graphqlEndpoint,
} from './constants';
import { User } from './types';

export async function fillInitialsInput(page: Page, user: User): Promise<void> {
  const initialsInput: Locator = page.getByPlaceholder(placeholderInitials);
  await page.getByRole('button', { name: signUpButton }).click();
  await initialsInput.fill(' ');
  await expect(page.getByText('Invalid full name format')).toBeVisible();
  await initialsInput.fill(user.fullName);
}
export async function fillEmailInput(page: Page, user: User): Promise<void> {
  const emailInput: Locator = page.getByPlaceholder(placeholderEmail);
  await page.getByRole('button', { name: signUpButton }).click();
  for (const expectation of expectationsEmail) {
    await emailInput.fill(expectation.email);
    await expect(page.getByText(expectation.errorText)).toBeVisible();
  }

  await emailInput.fill(user.email);
}

export async function fillPasswordInput(page: Page, user: User): Promise<void> {
  const passwordInput: Locator = page.getByPlaceholder(placeholderPassword);
  await page.getByRole('button', { name: signUpButton }).click();
  for (const expectation of expectationsPassword) {
    await passwordInput.fill(expectation.password);
    await expect(page.getByText(expectation.errorText)).toBeVisible();
  }

  await passwordInput.fill(user.password);
}
export function responseFilter(resp: Response): boolean {
  return resp.url().includes(graphqlEndpoint) && resp.status() === 200;
}
