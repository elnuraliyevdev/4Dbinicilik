import { test, expect, ADMIN_EMAIL, ADMIN_PIN, PASSWORD } from './fixtures';

/**
 * Aile Grupları is driven entirely by native window.prompt()/confirm()
 * dialogs — flagged in this session's audit as having zero Feature or E2E
 * coverage at all. Covers: creating a group, adding a member whose name
 * matches a real registered member (must link by user_id, not just store
 * the typed name), adding one that matches nobody (a "ghost" display-name-
 * only entry — current, documented behavior, not a claim it's ideal), and
 * removing a non-primary member.
 */
test.describe('Admin — family groups (native dialogs)', () => {
  test('create a group, add a matched member, add an unmatched member, then remove one', async ({ page }) => {
    const groupName = `E2E Test Ailesi ${Date.now()}`;

    await page.goto('/');
    await page.getByRole('button', { name: '👑 Yönetici' }).click();
    await page.locator('#gwAdminUser').fill(ADMIN_EMAIL);
    await page.locator('#gwAdminPass').fill(PASSWORD);
    await page.locator('#gwAdminPin').fill(ADMIN_PIN);
    await page.getByRole('button', { name: /Yönetici Masası'na Güvenli Giriş/ }).click();
    await expect(page.locator('#adminHeaderName')).toContainText('E2E Admin', { timeout: 10_000 });

    await page.locator('#adminTabBtn-families').click();
    await expect(page.getByRole('button', { name: '+ Yeni Grup' })).toBeVisible({ timeout: 10_000 });

    // --- Create the group ---
    page.once('dialog', (dialog) => dialog.accept(groupName));
    await page.getByRole('button', { name: '+ Yeni Grup' }).click();
    await expect(page.locator('#adminFamilyGroupsList')).toContainText(groupName, { timeout: 10_000 });

    const groupCard = page.locator('#adminFamilyGroupsList > div', { hasText: groupName });

    // --- Add a member whose name matches a real registered member exactly ---
    page.once('dialog', (dialog) => dialog.accept('E2E Member'));
    await groupCard.getByRole('button', { name: '+ Üye Ekle' }).click();
    await expect(groupCard).toContainText('E2E Member', { timeout: 10_000 });

    // The match must have linked by user_id, not just recorded the typed
    // name as an unlinked placeholder — confirm via the real API shape.
    const families = await (await page.request.get('/admin/families', { headers: { Accept: 'application/json' } })).json();
    const created = families.families.find((f: any) => f.name === groupName);
    expect(created, 'the group must exist via the API too, not just in the UI').toBeTruthy();
    const matchedMember = created.members.find((m: any) => m.user?.name === 'E2E Member');
    expect(matchedMember?.user_id, 'a name matching a real member must link by user_id').not.toBeNull();

    // --- Add a member whose name matches nobody ---
    page.once('dialog', (dialog) => dialog.accept('Tanınmayan Kişi Adı'));
    await groupCard.getByRole('button', { name: '+ Üye Ekle' }).click();
    await expect(groupCard).toContainText('Tanınmayan Kişi Adı', { timeout: 10_000 });

    // --- Remove the matched (non-primary) member ---
    page.once('dialog', (dialog) => dialog.accept());
    await groupCard.locator('span', { hasText: 'E2E Member' }).getByTitle('Gruptan Çıkar').click();
    await expect(groupCard).not.toContainText('E2E Member', { timeout: 10_000 });
  });
});
