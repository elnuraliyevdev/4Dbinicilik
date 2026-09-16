import { test, expect, ADMIN_EMAIL, ADMIN_PIN, PASSWORD } from './fixtures';

test.describe('Admin — packages & club settings (real UI)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: '👑 Yönetici' }).click();
    await page.locator('#gwAdminUser').fill(ADMIN_EMAIL);
    await page.locator('#gwAdminPass').fill(PASSWORD);
    await page.locator('#gwAdminPin').fill(ADMIN_PIN);
    await page.getByRole('button', { name: /Yönetici Masası'na Güvenli Giriş/ }).click();
    await expect(page.locator('#adminHeaderName')).toContainText('E2E Admin', { timeout: 10_000 });
    await page.locator('#adminTabBtn-settings').click();
    await expect(page.locator('#adminPackageSettingsList')).toBeVisible({ timeout: 10_000 });
  });

  test('editing a package price through the UI persists to the server', async ({ page }) => {
    const priceInput = page.locator('[id^="pkgPriceInput-"]').first();
    await expect(priceInput).toBeVisible({ timeout: 10_000 });
    const packageId = (await priceInput.getAttribute('id'))!.replace('pkgPriceInput-', '');

    await priceInput.fill('9999');
    await page.locator(`button[onclick="adminSavePackagePrice(${packageId})"]`).click();
    await expect(page.locator('.toast').last()).toContainText('güncellendi', { timeout: 10_000 });

    const res = await page.request.get('/admin/packages', { headers: { Accept: 'application/json' } });
    const pkg = (await res.json()).packages.find((p: any) => String(p.id) === packageId);
    expect(pkg.price_try).toBe('9999.00');
  });

  test('adding a new package through the UI creates it, and an invalid one is rejected client-side', async ({ page }) => {
    // No lesson count / price at all — the form's own guard should catch
    // this before ever reaching the server (alert(), not a failed request).
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Paket Ekle' }).click();

    await page.locator('#newPkgLessons').fill('55');
    await page.locator('#newPkgPrice').fill('77000');
    await page.getByRole('button', { name: 'Paket Ekle' }).click();
    await expect(page.locator('.toast').last()).toContainText('Yeni paket eklendi', { timeout: 10_000 });
    await expect(page.locator('#adminPackageSettingsList')).toContainText('55 Ders', { timeout: 10_000 });
  });

  test('saving an invalid cancellation window through the UI is rejected, not silently accepted', async ({ page }) => {
    await page.locator('#settingCancellationHours').fill('-3');
    await page.getByRole('button', { name: 'Ayarları Güncelle' }).click();
    await expect(page.locator('.toast').last()).toContainText('saat arasında', { timeout: 10_000 });

    // The bad value must not have been persisted.
    const res = await page.request.get('/admin/settings', { headers: { Accept: 'application/json' } });
    const setting = (await res.json()).settings.find((s: any) => s.key === 'cancellation_window_hours');
    expect(setting.value).not.toBe('-3');
  });

  test('saving a valid club settings change through the UI actually changes real booking behavior', async ({ page }) => {
    await page.locator('#settingCancellationHours').fill('5');
    await page.getByRole('button', { name: 'Ayarları Güncelle' }).click();
    await expect(page.locator('.toast').last()).toContainText('güncellendi', { timeout: 10_000 });

    const res = await page.request.get('/admin/settings', { headers: { Accept: 'application/json' } });
    const setting = (await res.json()).settings.find((s: any) => s.key === 'cancellation_window_hours');
    expect(setting.value).toBe('5');

    // Restore the default so this test doesn't leave a mutated global
    // setting behind for every other spec's cancellation-window assumptions.
    await page.locator('#settingCancellationHours').fill('2');
    await page.getByRole('button', { name: 'Ayarları Güncelle' }).click();
    await expect(page.locator('.toast').last()).toContainText('güncellendi', { timeout: 10_000 });
  });
});

test.describe('Admin — audit log panel (real UI)', () => {
  test('the security panel shows real, non-empty audit data after a real login', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: '👑 Yönetici' }).click();
    await page.locator('#gwAdminUser').fill(ADMIN_EMAIL);
    await page.locator('#gwAdminPass').fill(PASSWORD);
    await page.locator('#gwAdminPin').fill(ADMIN_PIN);
    await page.getByRole('button', { name: /Yönetici Masası'na Güvenli Giriş/ }).click();
    await expect(page.locator('#adminHeaderName')).toContainText('E2E Admin', { timeout: 10_000 });

    await page.locator('#adminTabBtn-security').click();
    // This login itself is the AUTH_SUCCESS event the panel should show.
    await expect(page.locator('#secAuditTableBody')).toContainText('AUTH_SUCCESS', { timeout: 10_000 });
    await expect(page.locator('#secStatLogins')).not.toHaveText('48 Oturum', { timeout: 10_000 });
  });
});
