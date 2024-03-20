import { Locator } from '@playwright/test';

export async function fillInput(input: Locator, value: string): Promise<void> {
  await input.click();
  await input.fill(value);
}
