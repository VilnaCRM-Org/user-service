import { expect, Locator, Page } from '@playwright/test';

import { NullableImgSize } from '../types/device-image';

export async function setImageAndViewport(
  page: Page,
  width: number,
  height: number
): Promise<NullableImgSize> {
  await page.setViewportSize({ width, height });
  const image: Locator = page.getByRole('img', { name: 'Main image' });
  await expect(image).toBeVisible();
  return image.boundingBox();
}
