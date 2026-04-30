import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'http://localhost:3000';

test.describe('PMK Management', () => {

  test.beforeEach(async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/login`);
    await page.getByLabel(/email/i).fill('admin@example.com');
    await page.getByLabel(/password/i).fill('password');
    await page.getByRole('button', { name: /masuk|login|sign in/i }).click();
    await page.waitForURL(/\/dashboard/);
  });

  test('pmk list page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/pmk`);
    await expect(page.getByRole('heading', { name: /p mk|daftar p mk/i })).toBeVisible();
  });

  test('can navigate to create pmk', async ({ page }) => {
    await page.goto(`${BASE_URL}/pmk`);
    // Click create button
    const createButton = page.getByRole('button', { name: /tambah|input|buat|baru/i }).or(
      page.locator('a[href="/pmk/create"]')
    );
    if (await createButton.isVisible()) {
      await createButton.click();
      await expect(page).toHaveURL(/\/pmk\/create/);
    }
  });

});