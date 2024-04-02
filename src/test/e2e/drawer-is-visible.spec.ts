import { test, expect, Page } from '@playwright/test';

const drawerTestId: string = 'drawer';
const labelButtonToOpenDrawer: string = 'Button to open the drawer';
const labelButtonToExitDrawer: string = 'Button to exit the drawer';

async function openDrawer(page: Page): Promise<void> {
  await page.getByLabel(labelButtonToOpenDrawer).click();
  await expect(page.getByTestId(drawerTestId)).toBeVisible();
}

async function closeDrawer(page: Page): Promise<void> {
  await page.getByLabel(labelButtonToExitDrawer).click();
  await expect(page.getByTestId(drawerTestId)).toBeHidden();
}

test('Checking whether the drawer opens and closes', async ({ page }) => {
  await page.goto('/');
  await page.setViewportSize({ width: 450, height: 812 });

  await openDrawer(page);
  await closeDrawer(page);

  await openDrawer(page);
  await page.setViewportSize({ width: 1024, height: 812 });

  await expect(page.getByTestId(drawerTestId)).toBeHidden();
});
