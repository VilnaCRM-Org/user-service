import { test, expect, Locator } from '@playwright/test';

import {
  authSection,
  expectationsRequired,
  signUpButton,
  policyText,
  userData,
} from './constants';
import { fillEmailInput, fillInitialsInput, fillPasswordInput } from './utils';

test('Should display error messages for invalid inputs', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: signUpButton }).click();

  for (const expectation of expectationsRequired) {
    await expect(page.getByText(expectation.text)).toBeVisible();
  }

  await fillInitialsInput(page, userData);
  await fillEmailInput(page, userData);
  await fillPasswordInput(page, userData);

  await page.getByLabel(policyText).check();
  await page.getByRole('button', { name: signUpButton }).click();

  const loading: Locator = await page.getByTestId(authSection).locator('svg');
  await expect(loading).toBeVisible();
});
