import { test, expect, Locator } from '@playwright/test';

test('Checking the current year', async ({ page }) => {
  await page.goto('/');

  const currentYear: number = new Date().getFullYear();
  const yearElements: Locator = await page.getByText(currentYear.toString());

  const displayedYear: string | null = await yearElements.first().textContent();
  expect(displayedYear).toBe(currentYear.toString());
});
