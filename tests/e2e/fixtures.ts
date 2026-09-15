import { test as base, expect } from '@playwright/test';
import { execSync } from 'node:child_process';

export const MEMBER_PHONE = '+90 500 000 09 09';
export const TRAINER_PIN = '4242';
export const ADMIN_EMAIL = 'e2e-admin@test.local';
export const ADMIN_PIN = '4242';
export const PASSWORD = 'E2eTestPass123!';

// Playwright always runs with cwd set to the config file's directory (the
// project root here), so this is reliable without __dirname (unavailable
// under package.json's "type": "module").
const projectRoot = process.cwd();

export const test = base;

// Every test starts from the exact same known-clean fixture state —
// regardless of whether the previous test in this run passed, failed, or
// left a half-finished booking behind. Cheap (no migrate:fresh) but
// reliable, and the command itself refuses to run outside local/testing.
test.beforeEach(async () => {
  execSync('php artisan e2e:reset', { cwd: projectRoot, stdio: 'pipe' });
});

export { expect };
