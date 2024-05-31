import { Locator, test } from '@playwright/test';

const nameOption: { name: RegExp } = { name: /Try it out/ };
const openDrawerLabel: string = 'Button to open the drawer';

test.describe('Buttons navigation tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('Desktop buttons navigation', async ({ page }) => {
    const headerTryItNowButton: Locator = page.getByRole('button', nameOption).nth(0);
    await headerTryItNowButton.click();

    const aboutUsTryItNowButton: Locator = page.getByRole('button', nameOption).nth(1);
    await aboutUsTryItNowButton.click();

    const forWhoTryItNowButton: Locator = page.getByRole('button', nameOption).nth(2);
    await forWhoTryItNowButton.click();
  });

  test('Mobile button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    const headerTryItNowButton: Locator = page.getByRole('button', nameOption).nth(0);
    await headerTryItNowButton.click();

    const aboutUsTryItNowButton: Locator = page.getByRole('button', nameOption).nth(1);
    await aboutUsTryItNowButton.click();
  });

  test('Drawer button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    await page.getByLabel(openDrawerLabel).click();

    const drawerTryItNowButton: Locator = page.getByRole('button', nameOption);
    await drawerTryItNowButton.click();
  });
});
