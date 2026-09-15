import { test, expect, MEMBER_PHONE, TRAINER_PIN, PASSWORD } from './fixtures';

test.describe('Trainer flow', () => {
  test('PIN login → see today\'s roster → mark attendance → add feedback note', async ({ page, request, baseURL }) => {
    // --- Precondition via API: book today's first slot with the E2E trainer as the E2E member ---
    const csrfMeta = async () => {
      const html = await (await request.get('/')).text();
      return html.match(/name="csrf-token" content="([^"]+)"/)?.[1] ?? '';
    };

    const trainers = await (await request.get('/trainer-options')).json();
    const trainer = trainers.trainers.find((t: any) => t.name === 'E2E Trainer');
    expect(trainer, 'E2ETestSeeder must have created the E2E trainer').toBeTruthy();

    let token = await csrfMeta();
    const loginRes = await request.post('/login/member', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { identifier: MEMBER_PHONE, password: PASSWORD },
    });
    expect(loginRes.ok()).toBeTruthy();
    // Login regenerates the session (and thus the CSRF token) — the pre-login
    // token is now stale, so switch to the one the login response hands back.
    token = (await loginRes.json()).csrf_token;

    const today = new Date().toISOString().slice(0, 10);
    const bookRes = await request.post('/member/reservations', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { trainer_id: trainer.id, date: today, time: '11:00' },
    });
    // Tolerate "slot already taken" from a prior run — the schedule check below
    // only needs *some* confirmed reservation with this trainer today.
    expect([201, 409]).toContain(bookRes.status());

    await request.post('/logout', { headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' } });

    // --- Real UI trainer login ---
    await page.goto('/');
    await page.getByRole('button', { name: '🎯Antrenör' }).click();
    await page.locator('#gwTrainerSelect').selectOption(String(trainer.id));
    await page.locator('#gwTrainerPass').fill(TRAINER_PIN);
    await page.getByRole('button', { name: /Antrenör Seans Masasına Gir/ }).click();

    await expect(page.locator('#trainerPortalTitle')).toContainText('E2E TRAINER', { timeout: 10_000 });
    await expect(page.locator('#trainerRosterList')).toContainText('E2E Member', { timeout: 10_000 });

    // --- Mark attendance: completed ---
    await page.getByRole('button', { name: '✅ Geldi' }).first().click();
    await expect(page.locator('#trainerRosterList')).toContainText('Tamamlandı', { timeout: 10_000 });

    // --- Feedback note ---
    await page.locator('#trainerFeedbackNote').fill('E2E test notu: denge iyi, tırısta çalışmalı.');
    await page.getByRole('button', { name: /Gelişim Raporunu Kaydet/ }).click();
    await expect(page.locator('.toast').last()).toContainText('kaydedildi', { timeout: 10_000 });
  });
});
