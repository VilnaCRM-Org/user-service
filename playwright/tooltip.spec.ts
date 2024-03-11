import { test, expect, Locator } from '@playwright/test';

async function handleTooltip(page, { role, name, element }): Promise<void> {
  const tooltipLocator: Locator = page.getByRole(role, { name });
  const elementLocator: Locator = element;

  await page.goto('/');
  await elementLocator.click();
  await expect(tooltipLocator).toBeVisible();

  await elementLocator.click();
  await expect(tooltipLocator).toBeHidden();
}

test.describe('Checking if the tooltips are working', () => {
  test('Tooltip services test', async ({ page }) => {
    await handleTooltip(page, {
      role: 'tooltip',
      name: 'Services Integrate in a few',
      element: page.getByText('services').nth(2),
    });
  });

  test('Tooltip password test', async ({ page }) => {
    await handleTooltip(page, {
      role: 'tooltip',
      name: 'We recommend using: lowercase',
      element: page.getByRole('img', { name: 'Password tip mark' }),
    });
  });
});
