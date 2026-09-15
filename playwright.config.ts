import { defineConfig, devices } from '@playwright/test';

/**
 * E2E config for 4D Binicilik. Always targets a local dev server backed by
 * the E2ETestSeeder fixtures — never run this against production or any
 * environment holding real member data (bookings/cancellations here have
 * real side effects: credit deductions, audit log entries).
 */
export default defineConfig({
  testDir: './tests/e2e',
  timeout: 45_000,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'storage/playwright-report' }]],
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8123',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: {
    command: 'php artisan serve --port=8123',
    url: 'http://127.0.0.1:8123',
    reuseExistingServer: !process.env.CI,
    timeout: 30_000,
  },
});
