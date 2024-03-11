import { test, expect } from '@playwright/test';

const links: Record<string, string> = {
  advantages: 'http://localhost:3000/#Advantages',
  forWho: 'http://localhost:3000/#forWhoSection',
  integration: 'http://localhost:3000/#Integration',
  contacts: 'http://localhost:3000/#Contacts',
};

async function navigateAndExpect(page, linkName, expectedURL): Promise<void> {
  await page.getByRole('link', { name: linkName }).click();
  await expect(page).toHaveURL(expectedURL);
}

async function openDrawerAndNavigate(
  page,
  linkName,
  expectedURL
): Promise<void> {
  await page.getByLabel('Button to open the drawer').click();
  await page.getByRole('link', { name: linkName }).click();
  await expect(page.getByTestId('drawer')).toBeHidden();
  await expect(page).toHaveURL(expectedURL);
}

test.describe('Checking if the navigation links are working', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('Header links', async ({ page }) => {
    await navigateAndExpect(page, 'Advantages', links.advantages);
    await navigateAndExpect(page, 'For who', links.forWho);
    await navigateAndExpect(page, 'Integration', links.integration);
    await navigateAndExpect(page, 'Contacts', links.contacts);
  });

  test('Navigate drawer links', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    await openDrawerAndNavigate(page, 'Advantages', links.advantages);
    await openDrawerAndNavigate(page, 'For who', links.forWho);
    await openDrawerAndNavigate(page, 'Integration', links.integration);
    await openDrawerAndNavigate(page, 'Contacts', links.contacts);
  });
});
