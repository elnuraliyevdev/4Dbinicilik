import { execSync } from 'node:child_process';
import { writeFileSync, unlinkSync } from 'node:fs';
import { join } from 'node:path';
import {
  test,
  expect,
  PASSWORD,
  UNCLAIMED_MEMBER_PHONE,
  LOCKOUT_TEST_MEMBER_PHONE,
  INACTIVE_TRAINER_PIN,
} from './fixtures';

/**
 * There's no admin-facing listing that includes an inactive trainer by
 * design (every real endpoint — /trainer-options, /member/availability —
 * correctly hides them), so there's no HTTP way to look this id up as a
 * real client would. Read it straight from the DB the same way fixtures.ts
 * already shells out for e2e:reset — via a temp script file rather than an
 * inline --execute string, which mangles nested quotes/closures under
 * execSync's shell escaping.
 */
function inactiveTrainerId(): number {
  const scriptPath = join(process.cwd(), 'storage', 'framework', 'e2e-lookup.php');
  writeFileSync(
    scriptPath,
    "echo App\\Models\\Trainer::whereHas('user', fn ($q) => $q->where('name', 'E2E Inactive Trainer'))->value('id');"
  );
  try {
    const output = execSync(`php artisan tinker < "${scriptPath}"`, { cwd: process.cwd() }).toString();
    const id = parseInt(output.trim(), 10);
    if (!id) throw new Error(`Could not resolve E2E Inactive Trainer id from tinker output: ${output}`);
    return id;
  } finally {
    unlinkSync(scriptPath);
  }
}

test.describe('Auth edge cases', () => {
  test('5 failed member logins lock the account, even with the correct password on the 6th try', async ({ page }) => {
    await page.goto('/');

    for (let i = 0; i < 5; i++) {
      await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(LOCKOUT_TEST_MEMBER_PHONE);
      await page.locator('#gwMemberPass').fill('WrongPassword' + i);
      await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
      await expect(page.locator('#gatewayFormError')).toContainText('hatalı', { timeout: 10_000 });
    }

    // 6th attempt, this time with the *correct* password — must still be
    // rejected, proving the lock is a real per-account gate and not just a
    // repeated-wrong-password counter.
    await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(LOCKOUT_TEST_MEMBER_PHONE);
    await page.locator('#gwMemberPass').fill(PASSWORD);
    await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
    await expect(page.locator('#gatewayFormError')).toContainText('Çok fazla hatalı deneme', { timeout: 10_000 });

    // Never actually logged in — the dashboard never loaded.
    await expect(page.locator('#statRemaining')).not.toBeVisible();
  });

  test('a deactivated trainer cannot log in even with the correct PIN', async ({ request }) => {
    const trainers = await (await request.get('/trainer-options')).json();
    // is_active=false trainers must not even appear in the public roster.
    const inactive = trainers.trainers.find((t: any) => t.name === 'E2E Inactive Trainer');
    expect(inactive, 'an inactive trainer should not be listed in /trainer-options at all').toBeUndefined();

    // The login endpoint itself must reject it too, not just hide it from the dropdown.
    const html = await (await request.get('/')).text();
    const token = html.match(/name="csrf-token" content="([^"]+)"/)?.[1] ?? '';

    const loginRes = await request.post('/login/trainer', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { trainer_id: inactiveTrainerId(), pin: INACTIVE_TRAINER_PIN },
    });
    expect(loginRes.status()).toBe(422);
  });

  test('claim-account: a member with no password yet can set one via the signed link and then log in', async ({ page, request }) => {
    // Log in as admin, generate the real claim link exactly as the admin UI does.
    const html = await (await request.get('/')).text();
    let token = html.match(/name="csrf-token" content="([^"]+)"/)?.[1] ?? '';
    const adminLogin = await request.post('/login/admin', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { email: 'e2e-admin@test.local', password: PASSWORD, pin: '4242' },
    });
    expect(adminLogin.ok()).toBeTruthy();
    token = (await adminLogin.json()).csrf_token;

    const membersRes = await request.get('/admin/members?search=E2E+Unclaimed', {
      headers: { Accept: 'application/json' },
    });
    const members = await membersRes.json();
    const unclaimed = members.members.data.find((m: any) => m.name === 'E2E Unclaimed Member');
    expect(unclaimed, 'E2ETestSeeder must have created the unclaimed member fixture').toBeTruthy();

    const linkRes = await request.post(`/admin/members/${unclaimed.id}/claim-link`, {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
    });
    expect(linkRes.ok()).toBeTruthy();
    const { claim_url } = await linkRes.json();

    await request.post('/logout', { headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' } });

    // Open the real link in a real browser tab — this is the page a member
    // actually receives and opens on their phone.
    await page.goto(claim_url);
    await expect(page.getByText('E2E Unclaimed Member')).toBeVisible({ timeout: 10_000 });

    await page.locator('#claimPassword').fill('BrandNewPass123');
    await page.locator('#claimPasswordConfirm').fill('BrandNewPass123');
    await page.getByRole('button', { name: 'Hesabımı Aktifleştir' }).click();
    await expect(page.getByText('Hesabınız aktifleşti')).toBeVisible({ timeout: 10_000 });

    // The new password actually works for a real login.
    await page.getByRole('link', { name: 'Giriş Sayfasına Git' }).click();
    await page.getByPlaceholder('Örn: 05551234567 veya referans kodu').fill(UNCLAIMED_MEMBER_PHONE);
    await page.locator('#gwMemberPass').fill('BrandNewPass123');
    await page.getByRole('button', { name: /Üye Portalı'na Giriş Yap/ }).click();
    await expect(page.locator('#statRemaining')).toBeVisible({ timeout: 10_000 });
  });
});
