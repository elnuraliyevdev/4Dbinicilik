import { test, expect, ADMIN_EMAIL, ADMIN_PIN, PASSWORD } from './fixtures';

test.describe('Admin — member roster (real UI)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: '👑 Yönetici' }).click();
    await page.locator('#gwAdminUser').fill(ADMIN_EMAIL);
    await page.locator('#gwAdminPass').fill(PASSWORD);
    await page.locator('#gwAdminPin').fill(ADMIN_PIN);
    await page.getByRole('button', { name: /Yönetici Masası'na Güvenli Giriş/ }).click();
    await expect(page.locator('#adminHeaderName')).toContainText('E2E Admin', { timeout: 10_000 });
    await page.locator('#adminTabBtn-members').click();
    await expect(page.locator('#adminMemberSearch')).toBeVisible({ timeout: 10_000 });
  });

  // "E2E Package Test Member" — not the shared MEMBER_PHONE fixture, which
  // member.spec.ts asserts an exact starting balance against. This session
  // already hit this exact class of cross-test leakage twice (auth.spec.ts's
  // lockout test, admin.spec.ts's package approval); granting credits here
  // is the same category of mutation, so it gets the same treatment.
  const TARGET_NAME = 'E2E Package Test Member';

  test('searching the roster narrows the table to a real, matching result', async ({ page }) => {
    await page.locator('#adminMemberSearch').fill(TARGET_NAME);
    await expect(page.locator('#adminMembersTableBody')).toContainText(TARGET_NAME, { timeout: 10_000 });

    const rowCount = await page.locator('#adminMembersTableBody tr').count();
    expect(rowCount, 'a specific-enough search should return one row, not the whole roster').toBeLessThanOrEqual(2);
  });

  test('granting credits through the real UI button persists and is immediately reflected in the table', async ({
    page,
  }) => {
    await page.locator('#adminMemberSearch').fill(TARGET_NAME);
    const row = page.locator('#adminMembersTableBody tr', { hasText: TARGET_NAME }).first();
    await expect(row).toBeVisible({ timeout: 10_000 });

    const searchParam = encodeURIComponent(TARGET_NAME);
    const before = await page.request.get(`/admin/members?search=${searchParam}`, { headers: { Accept: 'application/json' } });
    const memberBefore = (await before.json()).members.data.find((m: any) => m.name === TARGET_NAME);

    await row.getByRole('button', { name: '+4 Ders' }).click();
    await expect(page.locator('.toast').last()).toContainText('ders kredisi eklendi', { timeout: 10_000 });

    const after = await page.request.get(`/admin/members?search=${searchParam}`, { headers: { Accept: 'application/json' } });
    const memberAfter = (await after.json()).members.data.find((m: any) => m.name === TARGET_NAME);
    expect(memberAfter.remaining_lessons).toBe(memberBefore.remaining_lessons + 4);

    // Reflected in the table without a manual page reload.
    await expect(row).toContainText(String(memberAfter.remaining_lessons), { timeout: 10_000 });
  });

  test('the club vs. program tab counts match what the filtered table actually shows', async ({ page }) => {
    await expect(page.locator('#memberTabBtn-club')).toContainText(/Kulüp Üyeleri \(\d+\)/, { timeout: 10_000 });
    const clubCountText = await page.locator('#memberTabBtn-club').innerText();
    const clubCount = parseInt(clubCountText.match(/\((\d+)\)/)?.[1] ?? '0', 10);
    expect(clubCount).toBeGreaterThan(0);

    await page.locator('#memberTabBtn-prog').click();
    await expect(page.locator('#memberTabBtn-prog')).toHaveClass(/active/, { timeout: 10_000 });

    // Every row shown under "Program Kayıtlıları" must actually have an
    // active package — this is exactly the query the fake-vs-real-data audit
    // flagged as worth pinning down with a real assertion.
    const res = await page.request.get('/admin/members?scope=program', { headers: { Accept: 'application/json' } });
    const programMembers = (await res.json()).members.data;
    for (const m of programMembers) {
      expect(m.active_package_id, `${m.name} is listed under Program Kayıtlıları without an active package`).not.toBeNull();
    }
  });
});
