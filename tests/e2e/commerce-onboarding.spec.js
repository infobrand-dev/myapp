const { test, expect } = require('@playwright/test');

test('commerce public landing and onboarding click-through works', async ({ page }) => {
  await page.goto('/commerce', { waitUntil: 'domcontentloaded' });

  await expect(page.getByRole('heading', { name: /jalankan storefront dan operasional order/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /daftar commerce/i })).toBeVisible();

  await page.getByRole('link', { name: /daftar commerce/i }).click();

  await expect(page).toHaveURL(/\/onboarding\?product_line=commerce/);
  await expect(page.getByRole('heading', { name: /mulai workspace commerce anda/i })).toBeVisible();
  await expect(page.getByText(/pilih paket commerce/i)).toBeVisible();
  await expect(page.locator('.plan-card').filter({ hasText: /commerce starter/i }).first()).toBeVisible();
  await expect(page.locator('.plan-card').filter({ hasText: /commerce growth/i }).first()).toBeVisible();
  await expect(page.locator('.plan-card').filter({ hasText: /commerce scale/i }).first()).toBeVisible();

  await page.getByRole('radio', { name: /commerce growth/i }).check();
  await page.locator('input[name="company_name"]').fill('PT Commerce Test');
  await page.locator('input[name="slug"]').fill('commerce-e2e-check');
  await page.locator('input[name="name"]').fill('QA Commerce');
  await page.locator('input[name="email"]').fill('commerce-e2e@example.com');
  await page.locator('input[name="password"]').fill('Password123!');
  await page.locator('input[name="password_confirmation"]').fill('Password123!');
  await page.getByRole('checkbox', { name: /saya menyetujui/i }).check();

  const noPaymentWarning = page.getByText(/belum ada metode pembayaran yang tersedia saat ini/i);
  const payButton = page.getByRole('button', { name: /lanjut ke pembayaran/i });

  if (await noPaymentWarning.isVisible()) {
    await expect(noPaymentWarning).toBeVisible();
    await expect(payButton).toBeDisabled();
  } else {
    const paymentMethodCount = await page.locator('input[name="payment_method"]').count();
    expect(paymentMethodCount).toBeGreaterThan(0);
    await expect(payButton).toBeVisible();
  }
});

test('generic onboarding requires business suite selection first', async ({ page }) => {
  await page.goto('/onboarding', { waitUntil: 'domcontentloaded' });

  await expect(page.getByRole('heading', { name: /pilih business suite lalu lanjutkan ke plan/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /accounting/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /commerce/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /omnichannel/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /crm untuk pipeline penjualan/i })).toBeVisible();
});
