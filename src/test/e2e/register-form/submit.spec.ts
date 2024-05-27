import { test, Locator } from '@playwright/test';

import { checkCheckbox } from '../utils/checkCheckbox';
import { fillInput } from '../utils/fillInput';

import {
  placeholderInitials,
  placeholderEmail,
  placeholderPassword,
  signUpButton,
  policyText,
  userData,
} from './constants';
import { responseFilter } from './utils';

test('Submit the registration form', async ({ page }) => {
  const initialsInput: Locator = page.getByPlaceholder(placeholderInitials);
  const emailInput: Locator = page.getByPlaceholder(placeholderEmail);
  const passwordInput: Locator = page.getByPlaceholder(placeholderPassword);
  const signupButton: Locator = page.getByRole('button', {
    name: signUpButton,
  });
  const policyTextCheckbox: Locator = page.getByLabel(policyText);

  await page.goto('/');

  await fillInput(initialsInput, userData.fullName);
  await fillInput(emailInput, userData.email);
  await fillInput(passwordInput, userData.password);

  await checkCheckbox(policyTextCheckbox);

  await Promise.all([signupButton.click(), page.waitForResponse(responseFilter)]);
});
