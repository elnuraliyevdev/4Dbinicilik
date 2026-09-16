import { test as base, expect } from '@playwright/test';
import { execSync } from 'node:child_process';

export const MEMBER_PHONE = '+90 500 000 09 09';
export const TRAINER_PIN = '4242';
export const ADMIN_EMAIL = 'e2e-admin@test.local';
export const ADMIN_PIN = '4242';
export const PASSWORD = 'E2eTestPass123!';
export const UNCLAIMED_MEMBER_PHONE = '+90 500 000 09 10';
export const LOCKOUT_TEST_MEMBER_PHONE = '+90 500 000 09 11';
export const INACTIVE_TRAINER_PIN = '4242';

// Playwright always runs with cwd set to the config file's directory (the
// project root here), so this is reliable without __dirname (unavailable
// under package.json's "type": "module").
const projectRoot = process.cwd();

export const test = base;

/**
 * Date.toISOString() always renders in UTC — the app's timezone is
 * Europe/Istanbul (what reservation date/time strings represent), so during
 * the UTC-late/Istanbul-early-morning window (21:00-00:00 UTC) toISOString()
 * silently returns YESTERDAY's date from the server's point of view, and any
 * booking against it is correctly rejected as a past date. Compute the date
 * explicitly in Europe/Istanbul (not the test runner's own OS timezone,
 * which may not match — e.g. a UTC CI runner) so "today" always matches
 * what the server means.
 */
export function todayLocalDateString(): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Istanbul' }).format(new Date());
}

/**
 * A same-day HH:MM a few minutes from now, in Europe/Istanbul — for booking
 * "today" without hardcoding a fixed slot that's a coin flip on whether it's
 * already past depending on what time of day the suite happens to run.
 */
export function nearFutureTimeString(minutesFromNow = 5): string {
  const future = new Date(Date.now() + minutesFromNow * 60_000);
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Europe/Istanbul',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).formatToParts(future);
  const hour = parts.find((p) => p.type === 'hour')?.value ?? '00';
  const minute = parts.find((p) => p.type === 'minute')?.value ?? '00';
  return `${hour}:${minute}`;
}

// Every test starts from the exact same known-clean fixture state —
// regardless of whether the previous test in this run passed, failed, or
// left a half-finished booking behind. Cheap (no migrate:fresh) but
// reliable, and the command itself refuses to run outside local/testing.
test.beforeEach(async () => {
  execSync('php artisan e2e:reset', { cwd: projectRoot, stdio: 'pipe' });
});

export { expect };
