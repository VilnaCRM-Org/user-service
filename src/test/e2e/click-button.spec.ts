import { Locator, Page, test } from '@playwright/test';

const aboutVilnaCRM: RegExp = /The first Ukrainian/;
const forWho: RegExp = /For who/;
const whyWe: RegExp = /Why we/;

const openDrawerLabel: string = 'Button to open the drawer';

const nameOption: { name: RegExp } = { name: /Try it out/ };

const clickTryItNowButtonByFilteredSection: (
  page: Page,
  uniqueSectionText: string | RegExp
) => Promise<void> = async (page, uniqueSectionText) => {
  await page.locator('section').filter({ hasText: uniqueSectionText }).getByRole('button').click();
};

test.describe('Buttons navigation tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('Desktop buttons navigation', async ({ page }) => {
    await page.locator('header').getByRole('button', nameOption).click();
    await clickTryItNowButtonByFilteredSection(page, aboutVilnaCRM);
    await clickTryItNowButtonByFilteredSection(page, forWho);
  });

  test('Mobile button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    await clickTryItNowButtonByFilteredSection(page, aboutVilnaCRM);
    await clickTryItNowButtonByFilteredSection(page, whyWe);
    await clickTryItNowButtonByFilteredSection(page, forWho);
  });

  test('Drawer button navigation', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });

    await page.getByLabel(openDrawerLabel).click();

    const drawerTryItNowButton: Locator = page.getByRole('button', nameOption);
    await drawerTryItNowButton.click();
  });
});
