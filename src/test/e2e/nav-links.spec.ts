import { test, expect, Page } from '@playwright/test';

const links: Record<string, string> = {
  advantages: `${process.env.NEXT_PUBLIC_WEBSITE_URL}/#Advantages`,
  forWho: `${process.env.NEXT_PUBLIC_WEBSITE_URL}/#forWhoSection`,
  integration: `${process.env.NEXT_PUBLIC_WEBSITE_URL}/#Integration`,
  contacts: `${process.env.NEXT_PUBLIC_WEBSITE_URL}/#Contacts`,
};

const drawerTestId: string = 'drawer';
const advantagesNavLink: string = 'Advantages';
const forWhoNavLink: string = 'For who';
const integrationNavLink: string = 'Integration';
const contactsNavLink: string = 'Contacts';

async function navigateAndExpect(
  page: Page,
  linkName: string,
  expectedURL: string | RegExp
): Promise<void> {
  await page.getByRole('link', { name: linkName }).click();
  await expect(page).toHaveURL(expectedURL);
}

async function openDrawerAndNavigate(
  page: Page,
  linkName: string,
  expectedURL: string | RegExp
): Promise<void> {
  await page.getByLabel('Button to open the drawer').click();
  await page.getByRole('link', { name: linkName }).click();
  await expect(page.getByTestId(drawerTestId)).toBeHidden();
  await expect(page).toHaveURL(expectedURL);
}

test.describe('Checking if the navigation links are working', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('Header links', async ({ page }) => {
    await navigateAndExpect(page, advantagesNavLink, links.advantages);
    await navigateAndExpect(page, forWhoNavLink, links.forWho);
    await navigateAndExpect(page, integrationNavLink, links.integration);
    await navigateAndExpect(page, contactsNavLink, links.contacts);
  });

  test('Navigate drawer links', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    await openDrawerAndNavigate(page, advantagesNavLink, links.advantages);
    await openDrawerAndNavigate(page, forWhoNavLink, links.forWho);
    await openDrawerAndNavigate(page, integrationNavLink, links.integration);
    await openDrawerAndNavigate(page, contactsNavLink, links.contacts);
  });
});
