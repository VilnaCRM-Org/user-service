import { test, expect, Locator } from '@playwright/test';

import { authSection, signUpButton, policyText, userData } from './constants';
import { fillEmailInput, fillInitialsInput, fillPasswordInput } from './utils';

test('Should display error messages for invalid inputs', async ({ page }) => {
  await page.goto('/');

  await fillInitialsInput(page, userData);
  await fillEmailInput(page, userData);
  await fillPasswordInput(page, userData);

  await page.getByLabel(policyText).check();
  await page.getByRole('button', { name: signUpButton }).click();

  const loading: Locator = page.getByTestId(authSection).locator('svg');
  await expect(loading).toBeVisible();
});
