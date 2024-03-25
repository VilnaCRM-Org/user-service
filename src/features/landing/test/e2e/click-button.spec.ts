import { Locator, Page, test } from '@playwright/test';

const authSection: string = 'auth-section';
const headerLogInButton: string = 'header-log-in';
const headerSignUnButton: string = 'header-sign-up';
const aboutSignUnButton: string = 'about-sign-up';
const forWhoSigUpButton: string = 'for-who-sign-up';
const whyUsSigUpButton: string = 'why-us-sign-up';

test.describe('Buttons navigation tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  async function clickAndVerifySection(
    page: Page,
    elementLocator: string
  ): Promise<void> {
    const signUp: Locator = page.getByTestId(elementLocator);
    await signUp.click();
    await page.getByTestId(authSection).boundingBox();
  }

  async function clickDrawerButton(
    page: Page,
    buttonName: string
  ): Promise<void> {
    await page.getByLabel('Button to open the drawer').click();
    await page.getByRole('button', { name: buttonName }).click();
    await page.getByTestId(authSection).boundingBox();
  }

  test('Desktop buttons navigation', async ({ page }) => {
    await clickAndVerifySection(page, headerLogInButton);
    await clickAndVerifySection(page, headerSignUnButton);
    await clickAndVerifySection(page, aboutSignUnButton);
    await clickAndVerifySection(page, forWhoSigUpButton);
  });

  test('Mobile button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await clickAndVerifySection(page, whyUsSigUpButton);
    await clickAndVerifySection(page, aboutSignUnButton);
  });

  test('Drawer button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await clickDrawerButton(page, 'Log in');
    await clickDrawerButton(page, 'Try it out');
  });
});
