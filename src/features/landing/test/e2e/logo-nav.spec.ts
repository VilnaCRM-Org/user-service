import { test, expect, Page } from '@playwright/test';

const urlWithHashFragmentRegex: RegExp = /\/#/;

async function performLogoNavigation(
  page: Page,
  locator: string,
  logoName: string
): Promise<void> {
  await page.goto('/');
  await page.locator(locator).getByRole('link', { name: logoName }).click();
  await expect(page).toHaveURL(urlWithHashFragmentRegex);
}

test.describe('Navigation tests', () => {
  test('Header logo navigation', async ({ page }) => {
    await performLogoNavigation(page, 'header', 'Vilna logo');
  });

  test('Footer logo navigation', async ({ page }) => {
    await performLogoNavigation(page, '#Contacts', 'Vilna logo');
  });
});
