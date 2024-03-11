import { test } from '@playwright/test';

test.describe('Buttons navigation tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  async function clickAndVerifySection(page, elementLocator): Promise<void> {
    await page.click(elementLocator);
    await page.locator('[data-testid="auth-section"]').boundingBox();
  }

  async function clickDrawerButton(page, buttonName): Promise<void> {
    await page.getByLabel('Button to open the drawer').click();
    await page.getByRole('button', { name: buttonName }).click();
    await page.locator('[data-testid="auth-section"]').boundingBox();
  }

  test('Desktop buttons navigation', async ({ page }) => {
    await clickAndVerifySection(page, 'button:has-text("Log in")');
    await clickAndVerifySection(page, '[data-testid="header-sign-up"]');
    await clickAndVerifySection(page, '[data-testid="about-sign-up"]');
    await clickAndVerifySection(page, '[data-testid="for-who-sign-up"]');
  });

  test('Mobile button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await clickAndVerifySection(page, '[data-testid="why-us-sign-up"]');
  });

  test('Drawer button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await clickDrawerButton(page, 'Log in');
    await clickDrawerButton(page, 'Try it out');
  });
});
