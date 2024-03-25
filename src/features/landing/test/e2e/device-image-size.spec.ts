import { test, expect, Page } from '@playwright/test';

import { NullableImgSize } from './types/device-image';
import { setImageAndViewport } from './utils/setImageAndViewport';

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
