import { test, expect, Locator } from '@playwright/test';

test('Checking the current year', async ({ page }) => {
  await page.goto('/');
  const yearElement: Locator = await page.getByText('2024');
  const displayedYear: string | null = await yearElement.textContent();

  const currentYear: number = new Date().getFullYear();
  expect(displayedYear).toBe(currentYear.toString());
});
