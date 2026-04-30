import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'http://localhost:3000';

test.describe('Navigation', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.getByLabel(/email/i).fill('admin@example.com');
    await page.getByLabel(/password/i).fill('password');
    await page.getByRole('button', { name: /masuk|login|sign in/i }).click();
    await page.waitForURL(/\/dashboard/);
  });

  test('sidebar navigation works', async ({ page }) => {
    // Navigate to KGB via sidebar
    await page.locator('nav').getByText(/k gb/i).first().click();
    await expect(page).toHaveURL(/\/kgb/);

    // Navigate to PMK via sidebar
    await page.locator('nav').getByText(/p mk/i).first().click();
    await expect(page).toHaveURL(/\/pmk/);

    // Navigate to Dashboard via sidebar
    await page.locator('nav').getByText(/dashboard/i).first().click();
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('protected routes redirect to login', async ({ page }) => {
    // Try to access dashboard without auth (clear cookies)
    await page.context().clearCookies();
    await page.goto(`${BASE_URL}/dashboard`);
    await expect(page).toHaveURL(/\/login/);

    // Try KGB page
    await page.goto(`${BASE_URL}/kgb`);
    await expect(page).toHaveURL(/\/login/);

    // Try PMK page
    await page.goto(`${BASE_URL}/pmk`);
    await expect(page).toHaveURL(/\/login/);
  });

});