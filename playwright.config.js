import { defineConfig, devices } from '@playwright/test';

// End-to-end tests (tests/e2e): the real site in a real browser, served by PHP's built-in server in
// production mode, after `npm run build`. Run with `npm run test:e2e`.
// Chromium: CI installs Playwright's own (`npx playwright install --with-deps chromium`); elsewhere
// set CHROMIUM_PATH to use an installed one, e.g. CHROMIUM_PATH=/usr/bin/chromium in DDEV.
const port = 8123;
const baseURL = `http://127.0.0.1:${port}`;

export default defineConfig({
    testDir: 'tests/e2e',
    forbidOnly: !!process.env.CI,
    // In CI: annotations on the commit, plus an HTML report with traces uploaded when a test fails.
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: process.env.CI ? 'retain-on-failure' : 'off',
        launchOptions: process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH, args: ['--no-sandbox'] } : {},
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        // A clean cache first: production mode compiles content once, so a previous run's would be stale.
        command: `php bin/console cache:clear && php -S 127.0.0.1:${port} -t public tests/e2e/router.php`,
        url: baseURL,
        reuseExistingServer: !process.env.CI,
        stderr: 'ignore', // PHP's built-in server logs every request there
        env: {
            APP_URL: baseURL,
            APP_SECRET: 'e2e-tests-only-not-a-real-secret-0123456789abcdef',
            APP_DEBUG: '0',
            PHP_CLI_SERVER_WORKERS: '4', // Datastar requests run alongside page loads
            // Forms send through a transport that discards everything.
            MAILER_DSN: 'null://null',
            MAILER_FROM: 'site@example.test',
            CONTACT_TO: 'owner@example.test',
        },
    },
});
