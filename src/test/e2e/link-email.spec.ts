import { test, expect, Locator, Page } from '@playwright/test';

const vilnaCRMEmail: string = 'info@vilnacrm.com';

async function verifyEmailLink(page: Page, linkName: string): Promise<void> {
  const linkSelector: Locator = page.getByRole('link', { name: linkName });
  await expect(linkSelector).toHaveAttribute('href', `mailto:${linkName}`);
}

test.describe('Verify email', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('Footer email', async ({ page }) => {
    await verifyEmailLink(page, vilnaCRMEmail);
  });

  test('Drawer email', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    await page.getByLabel('Button to open the drawer').click();
    await verifyEmailLink(page, vilnaCRMEmail);
  });
});
