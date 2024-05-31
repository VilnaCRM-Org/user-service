import { test, expect, Page } from '@playwright/test';

const socialLinks: { url: string }[] = [
  // TODO: correct the url to our social networks
  { url: 'https://www.instagram.com/' },
  { url: 'https://github.com/VilnaCRM-Org/' },
  { url: 'https://www.facebook.com/' },
  { url: 'https://www.linkedin.com/' },
];

test.describe('Navigation tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  async function navigateAndVerifyURL(
    page: Page,
    linkName: string,
    expectedURL: string | RegExp,
    url: string
  ): Promise<void> {
    await page.route(url, route => {
      route.fulfill({
        status: 200,
        body: 'Instagram Page',
        headers: {
          'Content-Type': 'text/html',
        },
      });
    });
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
    await page.setViewportSize({ width: 375, height: 812 });
    await page.getByLabel('Button to open the drawer').click();
    await page.getByRole('presentation').getByLabel(`Link to ${linkName}`).click();
    await page.goto(url);
    await page.waitForURL(expectedURL);
    await expect(page).toHaveURL(expectedURL);
  }

  test('Desktop Instagram link', async ({ page }) => {
    await navigateAndVerifyURL(page, 'Link to Instagram', /instagram/, socialLinks[0].url);
  });
  test('Mobile Instagram link', async ({ page }) => {
    await openDrawerAndNavigate(page, 'Instagram', /instagram/, socialLinks[0].url);
  });

  test('Desktop GitHub link', async ({ page }) => {
    await navigateAndVerifyURL(page, 'Link to GitHub', /github/, socialLinks[1].url);
  });
  test('Mobile GitHub link', async ({ page }) => {
    await openDrawerAndNavigate(page, 'GitHub', /github/, socialLinks[1].url);
  });

  test('Desktop Facebook link', async ({ page }) => {
    await navigateAndVerifyURL(page, 'Link to Facebook', /facebook/, socialLinks[2].url);
  });
  test('Mobile Facebook link', async ({ page }) => {
    await openDrawerAndNavigate(page, 'Facebook', /facebook/, socialLinks[2].url);
  });

  test('Desktop Linkedin link', async ({ page }) => {
    await navigateAndVerifyURL(page, 'Link to Linkedin', /link/, socialLinks[3].url);
  });
  test('Mobile Linkedin link', async ({ page }) => {
    await openDrawerAndNavigate(page, 'Linkedin', /link/, socialLinks[3].url);
  });
});
