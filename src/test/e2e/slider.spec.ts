import { test, Locator, expect, Page } from '@playwright/test';

const FIRST_SLIDE_TITLE_WHY_US: string = 'Open source';
const SECOND_SLIDE_TITLE_WHY_US: string = 'Ease of setup';
const FIRST_SLIDE_TITLE_POSSIBILITIES: string = 'Public API';
const SECOND_SLIDE_TITLE_POSSIBILITIES: string = 'Ready plugins for CMS';
const TOOLTIP_CONTENT_TEXT: string = 'Services Integrate in a few';

async function performSliderTest(
  page: Page,
  firstSlideLocator: Locator,
  secondSlideLocator: Locator
): Promise<void> {
  await page.goto('/');
  await page.setViewportSize({ width: 375, height: 812 });

  const sliderOffsetWidth: number = await firstSlideLocator.evaluate(
    el => el.getBoundingClientRect().width + 220
  );

  await firstSlideLocator.hover({ force: true, position: { x: 0, y: 0 } });
  await page.mouse.down();
  await firstSlideLocator.hover({
    force: true,
    position: { x: sliderOffsetWidth, y: 0 },
  });
  await page.mouse.up();

  await expect(secondSlideLocator).toBeVisible();
}

test.describe('Slider tests', () => {
  test('Slider test in the whyus section', async ({ page }) => {
    const firstSlideWhyUs: Locator = page
      .getByText(FIRST_SLIDE_TITLE_WHY_US)
      .nth(3);
    const secondSlideWhyUs: Locator = page
      .getByText(SECOND_SLIDE_TITLE_WHY_US)
      .nth(1);

    await performSliderTest(page, firstSlideWhyUs, secondSlideWhyUs);
  });

  test('Slider test in the possibilities section', async ({ page }) => {
    const firstSlidePossibilities: Locator = page
      .getByText(FIRST_SLIDE_TITLE_POSSIBILITIES)
      .nth(1);
    const secondSlidePossibilities: Locator = page
      .getByText(SECOND_SLIDE_TITLE_POSSIBILITIES)
      .nth(1);

    await performSliderTest(
      page,
      firstSlidePossibilities,
      secondSlidePossibilities
    );

    await page.getByText('services').nth(3).click();

    const tooltipContent: Locator = page
      .getByRole('tooltip', {
        name: TOOLTIP_CONTENT_TEXT,
      })
      .nth(1);

    await expect(tooltipContent).toBeVisible();

    tooltipContent.click();
    await expect(tooltipContent).toBeHidden();
  });
});
