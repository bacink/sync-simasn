import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'http://localhost:3000';

test.describe('Error Handling', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.getByLabel(/email/i).fill('admin@example.com');
    await page.getByLabel(/password/i).fill('password');
    await page.getByRole('button', { name: /masuk|login|sign in/i }).click();
    await page.waitForURL(/\/dashboard/);
  });

  test('api error shows toast', async ({ page }) => {
    // Intercept API calls and simulate error
    await page.route('**/api/kgb', (route) => {
      route.fulfill({
        status: 500,
        body: JSON.stringify({ message: 'Internal server error' }),
      });
    });

    await page.goto(`${BASE_URL}/kgb`);

    // Check for toast/alert showing error message
    await expect(page.getByText(/gagal|error|kesalahan|500/i).or(
      page.locator('[role="alert"], .toast, .alert')
    )).toBeVisible({ timeout: 5000 });
  });

  test('network error handled gracefully', async ({ page }) => {
    // Simulate network failure by aborting API requests
    await page.route('**/api/**', (route) => {
      route.abort('failed');
    });

    await page.goto(`${BASE_URL}/kgb`);

    // Should show user-friendly error, not blank page
    await expect(page.getByText(/gagal memuat|network| koneksi /i).or(
      page.getByRole('heading', { name: /error/i })
    )).toBeVisible({ timeout: 5000 });
  });

});