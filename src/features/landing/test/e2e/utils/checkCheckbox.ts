import { Locator, expect } from '@playwright/test';

export async function checkCheckbox(checkbox: Locator): Promise<void> {
  await checkbox.check();
  expect(checkbox).toBeChecked();
}
