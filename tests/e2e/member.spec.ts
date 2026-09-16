import { test, expect, MEMBER_PHONE, PASSWORD } from './fixtures';

test.describe('Member flow', () => {
  test('login → book a lesson → credit deducted → cancel → credit refunded', async ({ page }) => {
    await page.goto('/');

    // --- Login ---
    await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(MEMBER_PHONE);
    await page.locator('#gwMemberPass').fill(PASSWORD);
    await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();

    await expect(page.locator('#statRemaining')).toHaveText('10', { timeout: 10_000 });

    // --- Go to availability, book the first open slot ---
    // Tomorrow, not today: today's remaining slots can land inside (or right
    // at) the 2h cancellation boundary depending on what time the suite
    // happens to run, which would make the free-cancel step below correctly
    // get rejected by the server rather than testing what it's meant to.
    await page.getByRole('button', { name: /Eğitmen Takvimine Git/ }).click();
    await page.getByRole('button', { name: /Yarın/ }).click();
    const firstAvailableSlot = page.locator('.slot-tag.available').first();
    await expect(firstAvailableSlot).toBeVisible({ timeout: 10_000 });
    await firstAvailableSlot.click();

    await expect(page.locator('#quickBookingModal')).toHaveClass(/open/);
    await page.getByRole('button', { name: /Rezervasyonu Onayla/ }).click();

    // Booking succeeded → dashboard shown, credit balance dropped by 1.
    await expect(page.locator('#statRemaining')).toHaveText('9', { timeout: 10_000 });
    await expect(page.locator('#activeLessonsList')).toContainText('Manej Biniş Dersi');

    // --- Cancel it (outside the 2h window since the slot is a future date) ---
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Ücretsiz İptal Dene' }).click();

    // Refund brings the balance back to 10.
    await expect(page.locator('#statRemaining')).toHaveText('10', { timeout: 10_000 });
    await expect(page.locator('#activeLessonsList')).toContainText('Aktif bir rezervasyonunuz bulunmuyor');
  });

  test('booking the same slot twice is rejected for the second member', async ({ browser }) => {
    const contextA = await browser.newContext();
    const pageA = await contextA.newPage();
    await pageA.goto('/');
    await pageA.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(MEMBER_PHONE);
    await pageA.locator('#gwMemberPass').fill(PASSWORD);
    await pageA.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
    await expect(pageA.locator('#statRemaining')).toHaveText('10', { timeout: 10_000 });

    await pageA.getByRole('button', { name: /Eğitmen Takvimine Git/ }).click();
    await pageA.getByRole('button', { name: /Yarın/ }).click();
    const slot = pageA.locator('.slot-tag.available').first();
    await expect(slot).toBeVisible({ timeout: 10_000 });
    await slot.click();
    await pageA.getByRole('button', { name: /Rezervasyonu Onayla/ }).click();
    await expect(pageA.locator('#statRemaining')).toHaveText('9', { timeout: 10_000 });

    // Same member, second tab — the slot they just took should now read busy.
    // Nav items are plain divs with an onclick handler, not <a> elements, so
    // there's no accessible "link" role to click here — go straight to the
    // same JS dispatcher the real nav button ends up calling.
    const pageB = await contextA.newPage();
    await pageB.goto('/');
    await expect(pageB.locator('#statRemaining')).toHaveText('9', { timeout: 10_000 });
    await pageB.evaluate(() => (window as any).switchView?.('availability'));
    await pageB.getByRole('button', { name: /Yarın/ }).click();
    await expect(pageB.locator('.slot-tag.busy').first()).toBeVisible({ timeout: 10_000 });

    // Clean up: cancel the booking we made so the fixture stays reusable.
    await pageA.evaluate(() => (window as any).switchView?.('dashboard'));
    pageA.once('dialog', (dialog) => dialog.accept());
    await pageA.getByRole('button', { name: 'Ücretsiz İptal Dene' }).click();
    await expect(pageA.locator('#statRemaining')).toHaveText('10', { timeout: 10_000 });

    await contextA.close();
  });
});
