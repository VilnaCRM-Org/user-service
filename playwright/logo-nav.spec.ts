import { test, expect } from '@playwright/test';

async function performLogoNavigation(page, locator, logoName): Promise<void> {
  await page.goto('/');
  await page.locator(locator).getByRole('link', { name: logoName }).click();
  await expect(page).toHaveURL(/\/#/);
}

test.describe('Navigation tests', () => {
  test('Header logo navigation', async ({ page }) => {
    await performLogoNavigation(page, 'header', 'Vilna logo');
  });

  test('Footer logo navigation', async ({ page }) => {
    await performLogoNavigation(page, '#Contacts', 'Vilna logo');
  });
});
