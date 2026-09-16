import { test, expect, ADMIN_EMAIL, ADMIN_PIN, PASSWORD, CONCURRENCY_TEST_MEMBER_PHONE } from './fixtures';

/**
 * Directly exercises the critical fix from this session's audit:
 * CreditLedgerService::deductForReservation() used to check-then-decrement
 * the user's balance with no row lock. The booking transaction's own
 * TrainerSlot lock only serializes two requests for the *same* slot — it
 * does nothing for the same member booking two *different* slots at once,
 * which could oversell a balance of 1 into two confirmed reservations.
 * Fixed by locking the user (and family) row before checking the balance.
 */
test.describe('Concurrency — credit oversell prevention', () => {
  test('a member with exactly 1 lesson credit can only win one of two simultaneous bookings for different slots', async ({
    request,
  }) => {
    const csrfMeta = async () => {
      const html = await (await request.get('/')).text();
      return html.match(/name="csrf-token" content="([^"]+)"/)?.[1] ?? '';
    };

    // --- Admin grants exactly 1 credit to the dedicated fixture member ---
    let token = await csrfMeta();
    const adminLogin = await request.post('/login/admin', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { email: ADMIN_EMAIL, password: PASSWORD, pin: ADMIN_PIN },
    });
    expect(adminLogin.ok()).toBeTruthy();
    token = (await adminLogin.json()).csrf_token;

    const membersRes = await request.get('/admin/members?search=E2E+Concurrency+Test+Member', {
      headers: { Accept: 'application/json' },
    });
    const member = (await membersRes.json()).members.data.find((m: any) => m.name === 'E2E Concurrency Test Member');
    expect(member, 'E2ETestSeeder must have created this fixture').toBeTruthy();
    expect(member.remaining_lessons, 'e2e:reset should have zeroed this fixture').toBe(0);

    const grantRes = await request.post(`/admin/members/${member.id}/credits`, {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { delta: 1 },
    });
    expect(grantRes.ok()).toBeTruthy();
    await request.post('/logout', { headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' } });

    // --- Log in as that member, fire two concurrent bookings for two
    // different trainers/times — same balance, no shared slot lock to save it ---
    token = await csrfMeta();
    const memberLogin = await request.post('/login/member', {
      headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
      data: { identifier: CONCURRENCY_TEST_MEMBER_PHONE, password: PASSWORD },
    });
    expect(memberLogin.ok()).toBeTruthy();
    token = (await memberLogin.json()).csrf_token;

    // The dedicated E2E Trainer, not whichever real seeded trainer happens to
    // be first/second in the roster — those aren't reset by e2e:reset, so an
    // arbitrary trainer/time pair here risks colliding with genuinely stale
    // 'busy' slot state left over from unrelated tests, a false 409 that has
    // nothing to do with the credit logic this test is actually checking.
    const trainers = await (await request.get('/trainer-options')).json();
    const trainer = trainers.trainers.find((t: any) => t.name === 'E2E Trainer');
    expect(trainer, 'E2ETestSeeder must have created the E2E trainer').toBeTruthy();
    const tomorrow = await tomorrowDateString();
    // Two clearly distinct, never-before-used times (off the fixed slot grid
    // on purpose) so this run can't collide with any prior run's leftovers.
    const suffix = Date.now() % 1000;
    const timeA = `0${1 + (suffix % 8)}:${String(suffix % 60).padStart(2, '0')}`;
    const timeB = `1${1 + (suffix % 8)}:${String((suffix + 1) % 60).padStart(2, '0')}`;

    const book = (time: string) =>
      request.post('/member/reservations', {
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        data: { trainer_id: trainer.id, date: tomorrow, time },
      });

    const [resA, resB] = await Promise.all([book(timeA), book(timeB)]);
    const statuses = [resA.status(), resB.status()].sort();

    // Exactly one booking succeeds (201), the other is correctly rejected for
    // insufficient credit (422) — never both succeeding (that would be the
    // oversell bug) and never both failing (that would mean the real one
    // never went through, a different regression).
    expect(statuses, `expected [201, 422], got [${statuses.join(', ')}]`).toEqual([201, 422]);

    const finalState = await (await request.get('/me')).json();
    expect(finalState.user.remaining_lessons, 'balance must never go negative or stay unspent').toBe(0);
    expect(finalState.user.pending_lessons).toBe(1);
  });
});

async function tomorrowDateString(): Promise<string> {
  const d = new Date(Date.now() + 24 * 60 * 60 * 1000);
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Istanbul' }).format(d);
}
