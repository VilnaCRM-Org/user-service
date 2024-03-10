import { test, expect } from '@playwright/test';

test.describe('Verify email', () => {
  test('Footer email', async ({ page }) => {
    await page.goto('/');
    await expect(
      page.getByRole('link', { name: 'info@vilnacrm.com' })
    ).toHaveAttribute('href', /mailto:*/);
    await expect(
      page.getByRole('link', { name: 'info@vilnacrm.com' })
    ).toHaveAttribute('href', 'mailto:info@vilnacrm.com');
  });

  test('Drawer email', async ({ page }) => {
    await page.goto('/');
    await page.setViewportSize({ width: 375, height: 812 });

    await page.getByLabel('Button to open the drawer').click();

    await expect(
      page.getByRole('link', { name: '@ info@vilnacrm.com' })
    ).toHaveAttribute('href', /mailto:*/);
    await expect(
      page.getByRole('link', { name: '@ info@vilnacrm.com' })
    ).toHaveAttribute('href', 'mailto:info@vilnacrm.com');
  });
});
