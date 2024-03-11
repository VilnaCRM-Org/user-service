import { test, expect } from '@playwright/test';

async function openDrawer(page): Promise<void> {
  await page.getByLabel('Button to open the drawer').click();
  await expect(page.getByTestId('drawer')).toBeVisible();
}

async function closeDrawer(page): Promise<void> {
  await page.getByLabel('Button to exit the drawer').click();
  await expect(page.getByTestId('drawer')).toBeHidden();
}

test('Checking whether the drawer opens and closes', async ({ page }) => {
  await page.goto('/');
  await page.setViewportSize({ width: 450, height: 812 });

  await openDrawer(page);
  await closeDrawer(page);

  await openDrawer(page);
  await page.setViewportSize({ width: 1024, height: 812 });

  await expect(page.getByTestId('drawer')).toBeHidden();
});
