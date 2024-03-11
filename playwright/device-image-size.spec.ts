import { test, expect, Locator, Page } from '@playwright/test';

interface ImageSize {
  width: number;
  height: number;
}
type NullableImgSize = ImageSize | null;

async function setImageAndViewport(
  page: Page,
  width: number,
  height: number
): Promise<NullableImgSize> {
  await page.setViewportSize({ width, height });
  const image: Locator = page.getByRole('img', { name: 'Main image' });
  await expect(image).toBeVisible();
  return image.boundingBox();
}

test('Should display the correct image size based on viewport', async ({
  page,
}: {
  page: Page;
}) => {
  await page.goto('/');

  const mobile: NullableImgSize = await setImageAndViewport(page, 375, 667);
  const desktop: NullableImgSize = await setImageAndViewport(page, 1200, 800);

  expect(desktop?.height).toBeGreaterThan(mobile?.height ?? 0);
  expect(desktop?.width).toBeGreaterThan(mobile?.width ?? 0);
});
