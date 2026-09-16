import { test, expect, MEMBER_PHONE, PACKAGE_TEST_MEMBER_PHONE, ADMIN_EMAIL, ADMIN_PIN, PASSWORD } from './fixtures';

test.describe('Admin flow', () => {
  test('2FA login → dashboard stats → approve a package request → member credit increases', async ({ page, request }) => {
    const csrfMeta = async () => {
      const html = await (await request.get('/')).text();
      return html.match(/name="csrf-token" content="([^"]+)"/)?.[1] ?? '';
    };

    // Its own dedicated fixture identity, not the shared MEMBER_PHONE account
    // — this test grants a real +4 lesson credit, which must never leak into
    // member.spec.ts's exact-starting-balance assertions.
    let token = await csrfMeta();
    const loginRes = await request.post('/login/member', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { identifier: PACKAGE_TEST_MEMBER_PHONE, password: PASSWORD },
    });
    // Login regenerates the session (and thus the CSRF token) — switch to the
    // fresh one the login response hands back before making further POSTs.
    token = (await loginRes.json()).csrf_token;
    const packages = await (await request.get('/member/packages')).json();
    const fourLesson = packages.packages.find((p: any) => p.lesson_count === 4);
    expect(fourLesson, 'E2ETestSeeder must have created the 4-lesson package').toBeTruthy();

    const before = await (await request.get('/me')).json();
    const balanceBefore = before.user.remaining_lessons;

    await request.post('/member/packages/request', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { package_id: fourLesson.id },
    });
    await request.post('/logout', { headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' } });

    // --- Real UI admin login (email + password + PIN) ---
    await page.goto('/');
    await page.getByRole('button', { name: '👑 Yönetici' }).click();
    await page.locator('#gwAdminUser').fill(ADMIN_EMAIL);
    await page.locator('#gwAdminPass').fill(PASSWORD);
    await page.locator('#gwAdminPin').fill(ADMIN_PIN);
    await page.getByRole('button', { name: /Yönetici Masası'na Güvenli Giriş/ }).click();

    await expect(page.locator('#adminHeaderName')).toContainText('E2E Admin', { timeout: 10_000 });
    // Dashboard KPIs populated from real data, not the old prototype's hardcoded values.
    await expect(page.locator('#adminStatMembers')).not.toHaveText('—', { timeout: 10_000 });

    // --- Approve the pending package request ---
    await page.locator('#adminTabBtn-notifications').click();
    await expect(page.locator('#adminPurchaseRequestsList')).toContainText('E2E Package Test Member', { timeout: 10_000 });
    await page.getByRole('button', { name: '✅ Onayla' }).first().click();
    await expect(page.locator('#adminPurchaseRequestsList')).not.toContainText('E2E Package Test Member', { timeout: 10_000 });

    // --- Confirm the member's balance actually increased by the package size ---
    // Uses page.request (shares the admin-authenticated browser context's
    // cookies) — the standalone `request` fixture logged itself out earlier.
    const after = await (await page.request.get('/admin/members?search=E2E+Package+Test+Member')).json();
    const member = after.members.data.find((m: any) => m.name === 'E2E Package Test Member');
    expect(member.remaining_lessons).toBe(balanceBefore + fourLesson.lesson_count);
  });

  test('a member cannot reach the admin panel', async ({ page }) => {
    await page.goto('/');
    await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(MEMBER_PHONE);
    await page.locator('#gwMemberPass').fill(PASSWORD);
    await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
    await expect(page.locator('#statRemaining')).toBeVisible({ timeout: 10_000 });

    const resp = await page.request.get('/admin/dashboard', { headers: { Accept: 'application/json' } });
    expect(resp.status()).toBe(403);
  });
});
