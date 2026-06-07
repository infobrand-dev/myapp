const { test, expect } = require('@playwright/test');

test('crm onboarding loads the CRM-specific catalog and form', async ({ page }) => {
  await page.goto('/onboarding?product_line=crm', { waitUntil: 'domcontentloaded' });

  await expect(page.getByRole('heading', { name: /mulai workspace crm anda/i })).toBeVisible();
  await expect(page.getByText(/pilih paket, buat workspace/i)).toBeVisible();
  await expect(page.getByText(/pilih paket crm/i)).toBeVisible();
  await expect(page.locator('input[name="subscription_plan_id"]')).toHaveCount(1);
  await expect(page.getByText(/customer 360/i).first()).toBeVisible();
  await expect(page.getByText(/pipeline/i).first()).toBeVisible();
  await expect(page.getByText(/follow-up/i).first()).toBeVisible();
});

test.describe('crm mobile onboarding', () => {
  test.use({ viewport: { width: 390, height: 844 } });

  test('crm onboarding remains usable on mobile', async ({ page }) => {
    await page.goto('/onboarding?product_line=crm', { waitUntil: 'domcontentloaded' });

    await expect(page.getByRole('heading', { name: /mulai workspace crm anda/i })).toBeVisible();
    await expect(page.getByText(/pilih paket crm/i)).toBeVisible();
    await expect(page.locator('input[name="company_name"]')).toBeVisible();
    await expect(page.locator('input[name="slug"]')).toBeVisible();
    await expect(page.getByRole('button', { name: /lanjut ke pembayaran/i })).toBeVisible();
  });
});
