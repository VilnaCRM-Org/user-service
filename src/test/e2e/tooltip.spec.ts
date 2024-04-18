import { test, expect, Locator, Page } from '@playwright/test';

async function handleTooltip(
  page: Page,
  {
    name,
    element,
    tooltip,
  }: { name: string; element: Locator; tooltip: Locator }
): Promise<void> {
  const closeLocator: Locator = page.getByRole('heading', { name });
  const elementLocator: Locator = element;

  await page.goto('/');
  await elementLocator.click();
  await expect(tooltip).toBeVisible();

  await closeLocator.click();
  await expect(tooltip).toBeHidden();
}

test.describe('Checking if the tooltips are working', () => {
  test('Tooltip services test', async ({ page }) => {
    await handleTooltip(page, {
      name: 'Ready plugins for CMS',
      element: page.getByRole('tooltip', { name: 'services' }),
      tooltip: page
        .getByRole('tooltip', { name: 'Services Integrate in a few' })
        .nth(1),
    });
  });

  test('Tooltip password test', async ({ page }) => {
    await handleTooltip(page, {
      name: 'Or register on the website:',
      element: page.getByRole('img', { name: 'Password tip mark' }),
      tooltip: page
        .locator('div')
        .filter({ hasText: 'We recommend using:lowercase' })
        .nth(1),
    });
  });
});
