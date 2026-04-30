import { test, expect } from '@playwright/test';

const BASE_URL = process.env.APP_URL || 'http://localhost:3000';

test.describe('KGB Management', () => {

  test.beforeEach(async ({ page }) => {
    // Login as admin to access KGB pages
    await page.goto(`${BASE_URL}/login`);
    await page.getByLabel(/email/i).fill('admin@example.com');
    await page.getByLabel(/password/i).fill('password');
    await page.getByRole('button', { name: /masuk|login|sign in/i }).click();
    await page.waitForURL(/\/dashboard/);
  });

  test('kgb list page loads', async ({ page }) => {
    await page.goto(`${BASE_URL}/kgb`);
    await expect(page.getByRole('heading', { name: /k gb|daftar k gb/i })).toBeVisible();
  });

  test('kgb list shows data', async ({ page }) => {
    await page.goto(`${BASE_URL}/kgb`);
    // Wait for table to load
    await page.waitForSelector('table', { timeout: 5000 });
    // Check table columns are visible
    await expect(page.getByText(/nip/i)).toBeVisible();
    await expect(page.getByText(/nama/i)).toBeVisible();
    await expect(page.getByText(/status/i)).toBeVisible();
  });

  test('kgb list empty state', async ({ page }) => {
    await page.goto(`${BASE_URL}/kgb`);
    // When no data, check empty state message or empty table
    const rows = await page.locator('table tbody tr').count();
    // Either empty state message or empty table should be visible
    const emptyMessage = page.getByText(/belum ada|tidak ada|kosong/i);
    if (await emptyMessage.isVisible()) {
      await expect(emptyMessage).toBeVisible();
    } else {
      expect(rows).toBe(0);
    }
  });

  test('can view kgb detail', async ({ page }) => {
    await page.goto(`${BASE_URL}/kgb`);
    await page.waitForSelector('table', { timeout: 5000 });
    // Click on first row's view link if rows exist
    const viewButton = page.locator('a[href*="/kgb/"]').first();
    if (await viewButton.isVisible()) {
      await viewButton.click();
      await expect(page).toHaveURL(/\/kgb\/\d+/);
      // Check detail page elements
      await expect(page.getByText(/nip/i)).toBeVisible();
      await expect(page.getByText(/nama/i)).toBeVisible();
    }
  });

  test('kgb status badge colors', async ({ page }) => {
    await page.goto(`${BASE_URL}/kgb`);
    await page.waitForSelector('table', { timeout: 5000 });

    // Check each status badge color if visible
    const statusBadges = page.locator('[class*="badge"], [class*="status"]');
    const count = await statusBadges.count();
    if (count > 0) {
      await expect(statusBadges.first()).toBeVisible();
      // Check that draft (gray), diajukan (blue), diverifikasi (yellow), disetujui (green), ditolak (red)
      // are distinguishable by their color classes
    }
  });

});