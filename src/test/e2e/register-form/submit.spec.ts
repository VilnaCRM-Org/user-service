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
  graphqlEndpoint,
} from './constants';
import { responseFilter } from './utils';

test('Submit the registration form', async ({ page }) => {
  const initialsInput: Locator = page.getByPlaceholder(placeholderInitials);
  const emailInput: Locator = page.getByPlaceholder(placeholderEmail);
  const passwordInput: Locator = page.getByPlaceholder(placeholderPassword);
  const policyTextCheckbox: Locator = page.getByLabel(policyText);

  const signupButton: Locator = page.getByRole('button', {
    name: signUpButton,
  });

  await page.goto('/');

  await fillInput(initialsInput, userData.fullName);
  await fillInput(emailInput, userData.email);
  await fillInput(passwordInput, userData.password);
  await checkCheckbox(policyTextCheckbox);

  await page.route(graphqlEndpoint, route => {
    route.fulfill();
  });

  page.waitForResponse(responseFilter);

  await signupButton.click();
});
