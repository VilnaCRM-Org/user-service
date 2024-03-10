import { test, expect } from '@playwright/test';

const links: Record<string, string> = {
  advantages: 'http://localhost:3000/#Advantages',
  forWho: 'http://localhost:3000/#forWhoSection',
  integration: 'http://localhost:3000/#Integration',
  contacts: 'http://localhost:3000/#Contacts',
};

test.describe('Checking if the navigation links are working', () => {
  test('Header links', async ({ page }) => {
    await page.goto('/');

    async function navigateAndExpect(linkName, expectedURL): Promise<void> {
      await page.getByRole('link', { name: linkName }).click();
      await expect(page).toHaveURL(expectedURL);
    }

    await navigateAndExpect('Advantages', links.advantages);
    await navigateAndExpect('For who', links.forWho);
    await navigateAndExpect('Integration', links.integration);
    await navigateAndExpect('Contacts', links.contacts);
  });

  test('Navigate drawer links', async ({ page }) => {
    await page.goto('/');
    await page.setViewportSize({ width: 375, height: 812 });

    async function openDrawerAndNavigate(linkName, expectedURL): Promise<void> {
      await page.getByLabel('Button to open the drawer').click();
      await page.getByRole('link', { name: linkName }).click();
      await expect(page.getByTestId('drawer')).toBeHidden();
      await expect(page).toHaveURL(expectedURL);
    }

    await openDrawerAndNavigate('Advantages', links.advantages);
    await openDrawerAndNavigate('For who', links.forWho);
    await openDrawerAndNavigate('Integration', links.integration);
    await openDrawerAndNavigate('Contacts', links.contacts);
  });
});
