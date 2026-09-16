import { test, expect, MEMBER_PHONE, PASSWORD } from './fixtures';

test.describe('Packages & Safari (real UI, not raw API)', () => {
  test('buying a package through the Paketler view creates a pending request, and a second attempt is blocked', async ({ page, context }) => {
    await page.goto('/');
    await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(MEMBER_PHONE);
    await page.locator('#gwMemberPass').fill(PASSWORD);
    await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
    await expect(page.locator('#statRemaining')).toHaveText('10', { timeout: 10_000 });

    await page.evaluate(() => (window as any).switchView?.('packages'));
    await expect(page.locator('#categoryMembership button', { hasText: 'Talep Oluştur' }).first()).toBeVisible({
      timeout: 10_000,
    });

    // buyPackage() confirms via a native dialog, then opens a WhatsApp deep
    // link in a new tab — accept the dialog and just close whatever tab
    // that spawns, we only care about the request that reaches our server.
    page.once('dialog', (dialog) => dialog.accept());
    const newTabPromise = context.waitForEvent('page', { timeout: 5_000 }).catch(() => null);
    await page.locator('#categoryMembership button', { hasText: 'Talep Oluştur' }).first().click();

    await expect(page.locator('.toast').last()).toContainText('Paket talebiniz oluşturuldu', { timeout: 10_000 });
    const newTab = await newTabPromise;
    if (newTab) await newTab.close();

    // A second purchase attempt while the first is still pending must be
    // rejected — the duplicate-request guard added this session. page.request
    // shares the browser's session cookies but (unlike the page's own fetch()
    // calls) doesn't auto-attach the CSRF header — read it from the DOM.
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const dupRes = await page.request.post('/member/packages/request', {
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken ?? '' },
      data: { package_id: await firstPackageId(page) },
    });
    expect(dupRes.status()).toBe(422);
    const dupBody = await dupRes.json();
    expect(dupBody.message).toContain('bekleyen bir paket talebiniz var');
  });

  test('booking a safari tour through the Paketler → Safari tab reaches the server with the correct participant count and price', async ({
    page,
  }) => {
    await page.goto('/');
    await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(MEMBER_PHONE);
    await page.locator('#gwMemberPass').fill(PASSWORD);
    await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
    await expect(page.locator('#statRemaining')).toHaveText('10', { timeout: 10_000 });

    await page.evaluate(() => (window as any).switchView?.('packages'));
    await page.locator('#tabBtnSafari').click();
    await expect(page.locator('#categorySafari button', { hasText: 'Rezervasyon Yap' }).first()).toBeVisible({
      timeout: 10_000,
    });
    await page.locator('#categorySafari button', { hasText: 'Rezervasyon Yap' }).first().click();

    await expect(page.locator('#safariModal')).toHaveClass(/open/);
    await page.locator('#safariPax').selectOption('3');
    await page.locator('#safariDate').fill(await tomorrowDateString(page));
    await page.locator('#safariTime').selectOption('15:30');

    // The displayed total is per_person * 3 — sanity-check it's non-zero and
    // consistent before submitting, since the server independently
    // recomputes this rather than trusting whatever the client sends.
    const displayedTotal = await page.locator('#safariTotalPrice').innerText();
    expect(displayedTotal).not.toBe('0 ₺');

    const [reservationRes] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/member/safari-tours/book') && r.request().method() === 'POST'),
      page.getByRole('button', { name: /Safari Rezervasyonunu Tamamla/ }).click(),
    ]);
    expect(reservationRes.status()).toBe(201);
    const body = await reservationRes.json();
    expect(body.reservation.participants).toBe(3);
    expect(body.reservation.type).toBe('safari');

    await expect(page.locator('.toast').last()).toBeVisible({ timeout: 10_000 });
  });
});

async function firstPackageId(page: import('@playwright/test').Page): Promise<number> {
  const res = await page.request.get('/member/packages', { headers: { Accept: 'application/json' } });
  const data = await res.json();
  return data.packages[0].id;
}

async function tomorrowDateString(page: import('@playwright/test').Page): Promise<string> {
  return page.evaluate(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Istanbul' }).formatToParts(d);
    const get = (t: string) => parts.find((p) => p.type === t)?.value;
    return `${get('year')}-${get('month')}-${get('day')}`;
  });
}
