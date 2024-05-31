import { test, expect, Page } from '@playwright/test';

// TODO: correct the url to our privacy policy
const vilnaCRMPrivacyPolicyURL: string = 'https://github.com/VilnaCRM-Org';

async function navigateToPrivacyPolicy(
  page: Page,
  linkName: string | RegExp,
  expectedURL: string | RegExp
): Promise<void> {
  await page.route(vilnaCRMPrivacyPolicyURL, route => {
    route.fulfill({
      status: 200,
      body: 'Github Page',
      headers: {
        'Content-Type': 'text/html',
      },
    });
  });
  await page.getByRole('link', { name: linkName, exact: true }).click();
  await page.goto(vilnaCRMPrivacyPolicyURL);
  await page.waitForURL(expectedURL);
  await expect(page).toHaveURL(expectedURL);
}

test.describe('Checking if the links to privacy policy are working', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('Links to privacy policy', async ({ page }) => {
    await navigateToPrivacyPolicy(page, 'Privacy policy', /VilnaCRM/);
  });
  test('Links to usage policy', async ({ page }) => {
    await navigateToPrivacyPolicy(page, 'Usage policy', /VilnaCRM/);
  });

  test('Links to privacy policy in form', async ({ page }) => {
    await navigateToPrivacyPolicy(page, /Privacy Policy/, /VilnaCRM/);
  });
  test('Links to usage policy in form', async ({ page }) => {
    await navigateToPrivacyPolicy(page, /Use Policy/, /VilnaCRM/);
  });
});
