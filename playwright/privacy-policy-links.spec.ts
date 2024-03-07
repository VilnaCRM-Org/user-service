// TODO: correct the url to our privacy policy

import { test, expect } from '@playwright/test';

async function navigateToPrivacyPolicy(
  page,
  linkName,
  expectedURL
): Promise<void> {
  page.goto('http://localhost:3000/');
  await page.getByRole('link', { name: linkName, exact: true }).click();
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
