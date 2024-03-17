import { test, expect, Locator, Page } from '@playwright/test';

async function handleTooltip(
  page: Page,
  { name, element }: { name: string; element: Locator }
): Promise<void> {
  const tooltipLocator: Locator = page.getByRole('tooltip', { name });
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
      name: 'Services Integrate in a few',
      element: page.getByText('services').nth(2),
    });
  });

  test('Tooltip password test', async ({ page }) => {
    await handleTooltip(page, {
      name: 'We recommend using: lowercase',
      element: page.getByRole('img', { name: 'Password tip mark' }),
    });
  });
});
