// TODO: correct the url to our social networks

import { test, expect, Page } from '@playwright/test';

test.describe('Navigation tests', () => {
  async function navigateAndVerifyURL(
    page: Page,
    linkName: string,
    expectedURL: string | RegExp
  ): Promise<void> {
    await page.goto('/');
    await page.getByRole('link', { name: linkName }).click();
    await page.waitForURL(expectedURL);
    await expect(page).toHaveURL(expectedURL);
  }

  async function openDrawerAndNavigate(
    page: Page,
    linkName: string,
    expectedURL: string | RegExp
  ): Promise<void> {
    await page.goto('/');
    await page.setViewportSize({ width: 375, height: 812 });
    await page.getByLabel('Button to open the drawer').click();
    await page.getByTestId('drawer').getByLabel(`Link to ${linkName}`).click();
    await page.waitForURL(expectedURL);
    await expect(page).toHaveURL(expectedURL);
  }

  test('Navigate to social media links from footer', async ({ page }) => {
    await navigateAndVerifyURL(page, 'Link to Instagram', /instagram\.com/);
    await navigateAndVerifyURL(page, 'Link to GitHub', /github\.com/);
    await navigateAndVerifyURL(page, 'Link to Facebook', /facebook\.com/);
    await navigateAndVerifyURL(page, 'Link to Linkedin', /linkedin\.com/);
  });

  test('Navigate to social media links from drawer', async ({ page }) => {
    await openDrawerAndNavigate(page, 'Instagram', /instagram\.com/);
    await openDrawerAndNavigate(page, 'GitHub', /github\.com/);
    await openDrawerAndNavigate(page, 'Facebook', /facebook\.com/);
    await openDrawerAndNavigate(page, 'Linkedin', /linkedin\.com/);
  });
});
