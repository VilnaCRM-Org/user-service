// TODO: correct the url to our social networks

import { test, expect, Page } from '@playwright/test';

const socialLinks: { url: string }[] = [
  { url: 'https://www.instagram.com/' },
  { url: 'https://github.com/' },
  { url: 'https://www.facebook.com/' },
  { url: 'https://www.linkedin.com/' },
];

test.describe('Navigation tests', () => {
  async function navigateAndVerifyURL(
    page: Page,
    linkName: string,
    expectedURL: string | RegExp,
    url: string
  ): Promise<void> {
    await page.goto('/');
    await page.getByRole('link', { name: linkName }).click();
    await page.goto(url);
    await page.waitForURL(expectedURL);
    await expect(page).toHaveURL(expectedURL);
  }

  async function openDrawerAndNavigate(
    page: Page,
    linkName: string,
    expectedURL: string | RegExp,
    url: string
  ): Promise<void> {
    await page.goto('/');
    await page.setViewportSize({ width: 375, height: 812 });
    await page.getByLabel('Button to open the drawer').click();
    await page.getByTestId('drawer').getByLabel(`Link to ${linkName}`).click();
    await page.goto(url);
    await page.waitForURL(expectedURL);
    await expect(page).toHaveURL(expectedURL);
  }

  test('Navigate to social media links from footer', async ({ page }) => {
    await page.goto('/');
    await navigateAndVerifyURL(
      page,
      'Link to Instagram',
      /instagram/,
      socialLinks[0].url
    );
    await page.goto('/');
    await navigateAndVerifyURL(
      page,
      'Link to GitHub',
      /github/,
      socialLinks[1].url
    );
    await page.goto('/');
    await navigateAndVerifyURL(
      page,
      'Link to Facebook',
      /facebook/,
      socialLinks[2].url
    );
    await page.goto('/');
    await navigateAndVerifyURL(
      page,
      'Link to Linkedin',
      /link/,
      socialLinks[3].url
    );
  });

  test('Navigate to social media links from drawer', async ({ page }) => {
    await openDrawerAndNavigate(
      page,
      'Instagram',
      /instagram/,
      socialLinks[0].url
    );
    await openDrawerAndNavigate(page, 'GitHub', /github/, socialLinks[1].url);
    await openDrawerAndNavigate(
      page,
      'Facebook',
      /facebook/,
      socialLinks[2].url
    );
    await openDrawerAndNavigate(page, 'Linkedin', /link/, socialLinks[3].url);
  });
});
