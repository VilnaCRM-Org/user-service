// TODO: correct the url to our privacy policy

import { test, expect, Page } from '@playwright/test';

const vilnaCRMPrivacyPolicyURL: string =
  'https://github.com/VilnaCRM-Org/website/blob/main/README.md';

async function navigateToPrivacyPolicy(
  page: Page,
  linkName: string | RegExp,
  expectedURL: string | RegExp
): Promise<void> {
  await page.goto('/');
  await page.getByRole('link', { name: linkName, exact: true }).click();
  await page.goto(vilnaCRMPrivacyPolicyURL);
  await page.waitForURL(expectedURL);
  await expect(page).toHaveURL(expectedURL);
}

test.describe('Checking if the links to privacy policy are working', () => {
  test('Links to privacy policy', async ({ page }) => {
    await navigateToPrivacyPolicy(page, 'Privacy policy', /VilnaCRM/);
    await navigateToPrivacyPolicy(page, 'Usage policy', /VilnaCRM/);
  });
  test('Links to privacy policy in form', async ({ page }) => {
    await navigateToPrivacyPolicy(page, /Privacy Policy/, /VilnaCRM/);
    await navigateToPrivacyPolicy(page, /Use Policy/, /VilnaCRM/);
  });
});
